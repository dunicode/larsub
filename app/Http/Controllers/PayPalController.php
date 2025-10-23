<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Support\Str;

class PayPalController extends Controller
{   
    // Nuevos métodos para suscripciones
    public function createSubscription(Request $request, $planId)
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        
        $client = new \GuzzleHttp\Client();
        $url = env("PAYPAL_MODE") == 'sandbox' 
            ? 'https://api-m.sandbox.paypal.com/v1/billing/subscriptions'
            : 'https://api-m.paypal.com/v1/billing/subscriptions';

        try {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode(
                        env("PAYPAL_CLIENT_ID") . ':' . env("PAYPAL_SECRET_ID")
                    )
                ],
                'json' => [
                    'plan_id' => $plan->paypal_plan_id,
                    'start_time' => now()->addMinute()->toISOString(),
                    'subscriber' => [
                        'name' => [
                            'given_name' => auth()->user()->name,
                        ],
                        'email_address' => auth()->user()->email,
                    ],
                    'application_context' => [
                        'brand_name' => config('app.name'),
                        'locale' => 'es-ES',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'SUBSCRIBE_NOW',
                        'payment_method' => [
                            'payer_selected' => 'PAYPAL',
                            'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        ],
                        'return_url' => route('subscription.success'),
                        'cancel_url' => route('subscription.cancel'),
                    ]
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $webhookId = 'tu_webhook_id'; // Configurar en PayPal Developer
        $payload = $request->getContent();
        $headers = $request->headers->all();

        // Verificar la firma del webhook
        $verificationResponse = $this->verifyWebhookSignature($payload, $headers);
        
        if ($verificationResponse['verification_status'] === 'SUCCESS') {
            $event = json_decode($payload, true);
            $this->processWebhookEvent($event);
        }

        return response()->json(['status' => 'success']);
    }

    public function verifyWebhookSignature($payload, $headers)
    {
        $client = new \GuzzleHttp\Client();
        $url = env("PAYPAL_MODE") == 'sandbox'
            ? 'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature'
            : 'https://api-m.paypal.com/v1/notifications/verify-webhook-signature';

        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(
                    env("PAYPAL_CLIENT_ID") . ':' . env("PAYPAL_SECRET_ID")
                )
            ],
            'json' => [
                'transmission_id' => $headers['paypal-transmission-id'][0] ?? '',
                'transmission_time' => $headers['paypal-transmission-time'][0] ?? '',
                'cert_url' => $headers['paypal-cert-url'][0] ?? '',
                'auth_algo' => $headers['paypal-auth-algo'][0] ?? '',
                'transmission_sig' => $headers['paypal-transmission-sig'][0] ?? '',
                'webhook_id' => config('paypal.webhook_id'),
                'webhook_event' => json_decode($payload, true)
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    public function processWebhookEvent($event)
    {
        $eventType = $event['event_type'];
        $resource = $event['resource'];

        switch ($eventType) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                $this->activateSubscription($resource);
                break;
            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $this->cancelSubscription($resource);
                break;
            case 'BILLING.SUBSCRIPTION.EXPIRED':
                $this->expireSubscription($resource);
                break;
            case 'PAYMENT.SALE.COMPLETED':
                $this->handlePaymentCompleted($resource);
                break;
        }
    }

    public function activateSubscription($resource)
    {
        $subscription = Subscription::where('paypal_subscription_id', $resource['id'])->first();
        if ($subscription) {
            $subscription->update([
                'status' => 'ACTIVE',
                'starts_at' => now(),
                'ends_at' => $resource['billing_info']['next_billing_time'] ?? null,
            ]);
        }
    }

    public function subscriptionSuccess(Request $request)
    {
        $subscriptionId = $request->get('subscription_id');
        
        // Crear suscripción en estado pendiente
        Subscription::create([
            'user_id' => auth()->id(),
            'subscription_plan_id' => 1, // O obtener del contexto
            'paypal_subscription_id' => $subscriptionId,
            'status' => 'PENDING',
            'starts_at' => now(),
        ]);

        return view('subscriptions.success');
    }

    public function subscriptionCancel()
    {
        return view('subscriptions.cancel');
    }
}