<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckSubscriptionsStatus extends Command
{
    protected $signature = 'subscriptions:check-status';
    protected $description = 'Verificar y sincronizar el estado de todas las suscripciones con PayPal';

    public function handle()
    {
        $this->info('Iniciando verificación de estado de suscripciones...');

        // Suscripciones que necesitan verificación
        $subscriptions = Subscription::whereIn('status', ['ACTIVE', 'PENDING', 'SUSPENDED'])
            ->where('ends_at', '>', now())
            ->orWhereNull('ends_at')
            ->get();

        $this->info("Encontradas {$subscriptions->count()} suscripciones para verificar");

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->line("Verificando suscripción: {$subscription->paypal_subscription_id}");

                $currentStatus = $subscription->status;
                $newStatus = $this->syncSubscriptionStatus($subscription);

                if ($currentStatus !== $newStatus) {
                    $updatedCount++;
                    $this->info("✓ Suscripción {$subscription->id} actualizada: {$currentStatus} → {$newStatus}");
                } else {
                    $this->line("✓ Suscripción {$subscription->id} mantiene estado: {$currentStatus}");
                }

                // Pequeña pausa para no saturar la API de PayPal
                sleep(1);

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Error en suscripción {$subscription->id}: " . $e->getMessage());
                Log::error("Error verificando suscripción {$subscription->id}: " . $e->getMessage());
            }
        }

        $this->info("Verificación completada. Actualizadas: {$updatedCount}, Errores: {$errorCount}");

        // Verificar suscripciones expiradas localmente
        $this->checkExpiredSubscriptions();

        return Command::SUCCESS;
    }

    private function syncSubscriptionStatus(Subscription $subscription)
    {
        $client = new \GuzzleHttp\Client();
        $url = env("PAYPAL_MODE") == 'sandbox'
            ? "https://api-m.sandbox.paypal.com/v1/billing/subscriptions/{$subscription->paypal_subscription_id}"
            : "https://api-m.paypal.com/v1/billing/subscriptions/{$subscription->paypal_subscription_id}";

        try {
            $response = $client->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode(
                        env("PAYPAL_CLIENT_ID") . ':' . env("PAYPAL_SECRET_ID")
                    )
                ],
                'timeout' => 30
            ]);

            $data = json_decode($response->getBody(), true);
            
            $newStatus = $data['status'] ?? 'UNKNOWN';
            $nextBillingTime = isset($data['billing_info']['next_billing_time']) 
                ? Carbon::parse($data['billing_info']['next_billing_time'])
                : null;

            // Actualizar en base de datos
            $subscription->update([
                'status' => $newStatus,
                'ends_at' => $nextBillingTime,
                'last_sync_at' => now(),
            ]);

            // Si está activa pero no debería estarlo según nuestra lógica
            if ($newStatus === 'ACTIVE' && $subscription->ends_at && $subscription->ends_at->lt(now())) {
                $this->handleExpiredSubscription($subscription);
            }

            return $newStatus;

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            
            if ($statusCode === 404) {
                // Suscripción no encontrada en PayPal
                $subscription->update([
                    'status' => 'CANCELLED',
                    'ends_at' => now(),
                    'last_sync_at' => now(),
                ]);
                return 'CANCELLED';
            }
            
            throw $e;
        }
    }

    private function checkExpiredSubscriptions()
    {
        $expiredSubscriptions = Subscription::where('status', 'ACTIVE')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $this->handleExpiredSubscription($subscription);
        }

        if ($expiredSubscriptions->count() > 0) {
            $this->info("Marcadas {$expiredSubscriptions->count()} suscripciones como expiradas localmente");
        }
    }

    private function handleExpiredSubscription(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'EXPIRED',
            'ends_at' => now(),
        ]);

        // Aquí puedes agregar notificaciones, emails, etc.
        Log::info("Suscripción {$subscription->id} marcada como expirada");
    }
}