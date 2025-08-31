<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\SystemSettings\SystemSettingController;

Route::get('/', function () {
    return view('welcome');
});

// For web routes
Route::get('/clear-cache', [SystemSettingController::class, 'clearCache']);
