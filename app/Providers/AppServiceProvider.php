<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            // Check DB connection
            DB::connection()->getPdo();
            $databaseName = DB::connection()->getDatabaseName();

            Log::info("✅ Database connected successfully: " . $databaseName);
            // Or for console: echo "Database connected: $databaseName\n";

            // Load system settings
            // $settings = SystemSetting::all()->pluck('value', 'key');
            // foreach ($settings as $key => $value) {
            //     Config::set($key, $value);
            //     $_ENV[$key] = $value; // optional
            // }

            // // Configure mail
            // $this->configureMailSettings($settings);

        } catch (\Exception $e) {
            Log::error("❌ Database connection failed or system settings not loaded: " . $e->getMessage());
            // Optionally, you can also echo for dev: echo "Database connection failed: " . $e->getMessage();
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register any application services.
    }

    /**
     * Configure mail settings dynamically.
     *
     * @param \Illuminate\Support\Collection $settings
     * @return void
     */
    protected function configureMailSettings($settings)
    {
        Config::set('mail.mailer', $settings->get('MAIL_MAILER', 'smtp'));
        Config::set('mail.host', $settings->get('MAIL_HOST'));
        Config::set('mail.port', $settings->get('MAIL_PORT'));
        Config::set('mail.username', $settings->get('MAIL_USERNAME'));
        Config::set('mail.password', $settings->get('MAIL_PASSWORD'));
        Config::set('mail.encryption', $settings->get('MAIL_ENCRYPTION'));
        Config::set('mail.from.address', $settings->get('MAIL_FROM_ADDRESS'));
        Config::set('mail.from.name', $settings->get('MAIL_FROM_NAME'));
    }
}
