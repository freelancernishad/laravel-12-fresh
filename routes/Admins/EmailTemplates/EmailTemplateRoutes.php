<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admins\EmailTemplateController;
use App\Http\Controllers\Admins\EmailSenderController;
use App\Http\Controllers\Admins\EmailLogController;

/*
|--------------------------------------------------------------------------
| Email Templates Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    // Email Templates Management
    Route::resource('email-templates', EmailTemplateController::class)->names([
        'index' => 'admin.email-templates.index',
        'create' => 'admin.email-templates.create',
        'store' => 'admin.email-templates.store',
        'edit' => 'admin.email-templates.edit',
        'update' => 'admin.email-templates.update',
        'destroy' => 'admin.email-templates.destroy',
    ]);

    // Email Sender
    Route::get('/email-sender', [EmailSenderController::class, 'index'])->name('admin.email-sender.index');
    Route::post('/email-sender/send', [EmailSenderController::class, 'send'])->name('admin.email-sender.send');
    Route::post('/email-sender/test', [EmailSenderController::class, 'sendTest'])->name('admin.email-sender.test');

    // Email Logs / History
    Route::get('/email-history', [EmailLogController::class, 'index'])->name('admin.email-logs.index');
    Route::delete('/email-history/{emailLog}', [EmailLogController::class, 'destroy'])->name('admin.email-logs.destroy');
});
