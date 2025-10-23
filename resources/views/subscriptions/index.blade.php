@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Elige tu Plan de Suscripción') }}</div>

                <div class="card-body">
                    @foreach($plans as $plan)
                        <div class="plan-card">
                            <h3>{{ $plan->name }}</h3>
                            <p>{{ $plan->description }}</p>
                            <div class="price">${{ $plan->price }}/{{ $plan->billing_cycle }}</div>
                            <div id="paypal-button-container-{{ $plan->id }}"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section("footer")
<script src="https://www.paypal.com/sdk/js?client-id={{ env('PAYPAL_CLIENT_ID') }}&vault=true&intent=subscription"></script>
<script>
    @foreach($plans as $plan)
    paypal.Buttons({
        createSubscription: function(data, actions) {
            return fetch('/paypal/create-subscription/{{ $plan->id }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                return data.id; // Subscription ID
            });
        },
        onApprove: function(data, actions) {
            // Redirigir a página de éxito con el ID de suscripción
            window.location.href = '/subscription/success?subscription_id=' + data.subscriptionID;
        },
        onError: function (err) {
            console.error('Error en la suscripción:', err);
            alert('Ocurrió un error durante el proceso de suscripción');
        }
    }).render('#paypal-button-container-{{ $plan->id }}');
    @endforeach
</script>
@endsection