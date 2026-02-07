<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AttachJwtFromCookie;
use App\Http\Controllers\Admin\Auth\AdminAuthController as AdminViewAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;


Route::get('/', function () {
    $dbConnected = false;
    $dbName = 'Unknown';
    try {
        DB::connection()->getPdo();
        $dbConnected = true;
        $dbName = DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        $dbConnected = false;
    }

    $projectInfo = [
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'db_connected' => $dbConnected,
        'db_name' => $dbName,
        'server_time' => now()->toDateTimeString(),
    ];

    // AI Info
    $aiInfo = [
        'name' => 'zsi.ai',
        'tagline' => 'Advanced Agentic Intelligence for ZilMoney',
        'capabilities' => ['Autonomous Task Execution', 'Smart Code Refactoring', 'API Optimization'],
    ];

    return view('welcome', compact('projectInfo', 'aiInfo'));
});

Route::get('/db-check', function () {
    try {
        DB::connection()->getPdo(); // DB connection check
        $databaseName = DB::connection()->getDatabaseName();
        return "✅ Database connected successfully! Database name: " . $databaseName;
    } catch (\Exception $e) {
        return "❌ Database connection failed: " . $e->getMessage();
    }
});

Route::get('/run-migrate', function() {
    Artisan::call('migrate', ['--force' => true]);
    return "Migrations completed!";
});



// For web routes
Route::get('/clear-cache', [App\Http\Controllers\Common\SystemSettings\SystemSettingController::class, 'clearCache']);

Route::get('/payment/test-intent', function () {
    $stripeKey = config('services.stripe.key');
    return view('payment.stripe-intent', compact('stripeKey'));
});

// Admins Routes


// Admin Dashboard Views
Route::prefix('admin')->group(function () {
    
    // Login View
    Route::get('/login', [AdminViewAuthController::class, 'showLoginForm'])->name('admin.login.view');

    Route::middleware([AttachJwtFromCookie::class, AuthenticateAdmin::class])->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        
        // Settings View
        Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('admin.settings');
        
        // Allowed Origins View
        Route::get('/origins', function() {
            // Pass empty array, view will fetch via API
            return view('admin.origins.index', ['origins' => []]); 
        })->name('admin.origins.index');

        // Stripe Info View
        Route::get('/stripe/webhook', [AdminDashboardController::class, 'stripeInfo'])->name('admin.stripe.webhook');

        // Plans & Features Management Views
        Route::get('/plan/features', function() {
            return view('admin.plans.features');
        })->name('admin.plans.features');

        Route::get('/plans', function() {
            return view('admin.plans.index');
        })->name('admin.plans.index');

        // Coupons Management
        Route::get('/coupons', function() {
            return view('admin.coupons.index');
        })->name('admin.coupons.index');

        // Subscriptions & Payments
        Route::get('/subscriptions', function() {
            return view('admin.subscriptions.index');
        })->name('admin.subscriptions.index');

        Route::get('/payments', function() {
            return view('admin.payments.index');
        })->name('admin.payments.index');

        // User Management
        Route::get('/users', function() {
            return view('admin.users.index');
        })->name('admin.users.index');

    });
});
