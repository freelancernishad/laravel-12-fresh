<?php

use App\Http\Controllers\Gateways\EkpayController;
use Illuminate\Support\Facades\Route;

Route::post('/initiate', [EkpayController::class, 'initiate']);
Route::post('/webhook', [EkpayController::class, 'webhook']);

// Success/Fail/Cancel redirects
Route::get('/success', [EkpayController::class, 'success'])->name('ekpay.success');
Route::get('/fail', [EkpayController::class, 'fail'])->name('ekpay.fail');
Route::get('/cancel', [EkpayController::class, 'cancel'])->name('ekpay.cancel');

// Support POST for redirects if needed
Route::post('/success', [EkpayController::class, 'success']);
Route::post('/fail', [EkpayController::class, 'fail']);
Route::post('/cancel', [EkpayController::class, 'cancel']);

Route::get('/check-status', [EkpayController::class, 'checkStatus']);
Route::post('/check-status', [EkpayController::class, 'checkStatus']);
