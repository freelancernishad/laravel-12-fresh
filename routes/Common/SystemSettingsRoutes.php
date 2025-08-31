<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Common\SystemSettings\SystemSettingController;



Route::prefix('admin')->group(function () {
    Route::middleware(AuthenticateAdmin::class)->group(function () { // Applying admin middleware
        Route::post('/system-setting', [SystemSettingController::class, 'storeOrUpdate']);
    });
});
