<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\User\Plan\Stripe\PlanSubscriptionController;
use App\Http\Controllers\Common\Gateways\Stripe\StripeWebhookController;


Route::prefix('/user')->group(function () {
    Route::middleware(AuthenticateUser::class)->group(function () { // Applying user middleware


    Route::post('/plans/purchase', [PlanSubscriptionController::class, 'PurchasePlan']);


});
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
