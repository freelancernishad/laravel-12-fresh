<?php

use App\Http\Controllers\Gateways\StripeWebhookController;

// Webhook route - must be outside auth middleware
Route::post('/webhook', [StripeWebhookController::class, 'handle']);

Route::middleware([AuthenticateUser::class])->group(function () {
    Route::post('/checkout', [StripeController::class, 'checkout']);
    Route::post('/subscribe', [StripeController::class, 'subscribe']);
    Route::post('/payment-intent', [StripeController::class, 'paymentIntent']);
    Route::post('/save-card', [StripeController::class, 'saveCard']);
    Route::get('/cards', [StripeController::class, 'getCards']);
    Route::delete('/cards/{id}', [StripeController::class, 'deleteCard']);
    Route::get('/payment-details/{session_id}', [StripeController::class, 'getPaymentDetails']);
});
