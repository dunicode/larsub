@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Suscripción Exitosa') }}</div>

                <div class="card-body">
                    <p>Tu suscripción ha sido activada correctamente.</p>
                    <a href="/dashboard">Ir al Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection