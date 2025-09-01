<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\User\UserManagement\UserController;



Route::prefix('/user')->group(function () {
    Route::middleware(AuthenticateUser::class)->group(function () { // Applying user middleware
        // Get logged-in user profile
        Route::get('profile', [UserController::class, 'profile']);

        // Update logged-in user profile
        Route::put('profile', [UserController::class, 'update']);

        // Reset/change password
        Route::post('reset-password', [UserController::class, 'resetPassword']);
    });
});

