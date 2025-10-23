<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Nuevas rutas para suscripciones
Route::get('/subscriptions', [App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscriptions.index');
Route::post('/paypal/create-subscription/{planId}', [App\Http\Controllers\PayPalController::class, 'createSubscription'])->name('subscription.create');
Route::get('/subscription/success', [App\Http\Controllers\PayPalController::class, 'subscriptionSuccess'])->name('subscription.success');
Route::get('/subscription/cancel', [App\Http\Controllers\PayPalController::class, 'subscriptionCancel'])->name('subscription.cancel');

// Webhook para PayPal
Route::post('/paypal/webhook', [App\Http\Controllers\PayPalController::class, 'handleWebhook'])->withoutMiddleware(['web', 'csrf']);