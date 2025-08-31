<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Common\Media\MediaController;



Route::prefix('admin')->group(function () {
    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::post('/media/upload', [MediaController::class, 'upload']);
    });
});


Route::prefix('media')->group(function () {
    Route::get('/', [MediaController::class, 'index']);
    Route::get('{id}', [MediaController::class, 'show']);
});