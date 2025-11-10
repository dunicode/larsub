@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Subscription Cancel') }}</div>

                <div class="card-body">
                    @if(count($subscriptions) > 0)
                        @foreach ($subscriptions as $item)
                            <p>STATUS: {{$item->status}} - START AT: {{$item->starts_at}}  
                            @if($item->status != 'CANCELLED')
                                - <button class="btn btn-sm btn-danger" id="btn-{{ $item->paypal_subscription_id }}" onclick="cancelSubscription(this, '{{ $item->paypal_subscription_id }}')">Cancel</button>
                            @endif
                            </p>
                        @endforeach
                    @else
                        <p>There is not subscriptions active</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
    async function cancelSubscription(button, subscriptionId) {
        // Deshabilitar el botón inmediatamente
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cancelando...';
        
        const result = await Swal.fire({
            title: 'You are sure?',
            text: "This action will cancel your subscription immediately.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, cancel',
            cancelButtonText: 'No, keep'
        });

        // Si el usuario cancela, volver a habilitar el botón
        if (!result.isConfirmed) {
            button.disabled = false;
            button.innerHTML = 'Cancel';
            return;
        }

        try {
            const response = await fetch(`/paypal/cancel-subscription/${subscriptionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    reason: 'User requested cancellation from the panel'
                })
            });

            const data = await response.json();

            if (data.success) {
                button.innerHTML = '<i class="fas fa-check"></i> Cancelada';
                button.classList.remove('btn-danger');
                button.classList.add('btn-secondary');
                
                Swal.fire('¡Cancelled!', data.message, 'success');
                setTimeout(() => {
                    // Opcional: recargar la página o actualizar la UI
                    location.reload();
                }, 2000);
            } else {
                // Rehabilitar el botón en caso de error
                button.disabled = false;
                button.innerHTML = 'Cancel';
                Swal.fire('Error', data.error, 'error');
            }
        } catch (error) {
            // Rehabilitar el botón en caso de error de red
            button.disabled = false;
            button.innerHTML = 'Cancel';
            Swal.fire('Error', 'Connection error', 'error');
        }
    }
</script>
@endsection