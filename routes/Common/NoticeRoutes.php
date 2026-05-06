<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\NoticeController;

Route::apiResource('notices', NoticeController::class);
