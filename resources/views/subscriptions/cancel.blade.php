@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Suscripción Cancel') }}</div>

                <div class="card-body">
                    <p>Tu suscripción ha sido cancelada.</p>
                    <a href="/home">Ir al Home Page</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection