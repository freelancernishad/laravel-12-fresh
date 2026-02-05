<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Common\SystemSettings\SystemSettingController;

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
Route::get('/clear-cache', [SystemSettingController::class, 'clearCache']);
