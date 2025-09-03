<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\Api\Webhook\StripeWebhookController;
use App\Http\Controllers\User\Plan\Stripe\PlanSubscriptionController;


Route::prefix('/user')->group(function () {
    Route::middleware(AuthenticateUser::class)->group(function () { // Applying user middleware


    Route::post('/plans/purchase', [PlanSubscriptionController::class, 'PurchasePlan']);


});
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
