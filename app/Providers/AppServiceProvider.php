<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use App\Models\SystemSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use App\Events\StripePaymentEvent;
use App\Listeners\CheckStripePaymentStatus;

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
            // Load all system settings into config with caching
            $settings = \Illuminate\Support\Facades\Cache::rememberForever('system_settings', function () {
                return SystemSetting::all()->pluck('value', 'key');
            });

            foreach ($settings as $key => $value) {
                Config::set($key, $value);
            }

            // Explicitly configure settings if present
            if ($settings->isNotEmpty()) {
                $this->configureMailSettings($settings);
                $this->configureStripeSettings($settings);
                $this->configureAwsSettings($settings);
                $this->configureJwtSettings($settings);
                $this->configureTwilioSettings($settings);
            }

            // Configure Allowed Origins (CORS)
            $this->configureCorsSettings();

        } catch (QueryException $e) {
            \Log::error('Error loading system settings: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Unexpected error loading system settings: ' . $e->getMessage());
        }

        // Register Stripe Event Listener
        Event::listen(
            StripePaymentEvent::class,
            [CheckStripePaymentStatus::class, 'handle']
        );
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
        $mailer = $settings->get('MAIL_MAILER', 'smtp');
        Config::set('mail.default', $mailer);

        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.host', $settings->get('MAIL_HOST'));
            Config::set('mail.mailers.smtp.port', $settings->get('MAIL_PORT'));
            Config::set('mail.mailers.smtp.username', $settings->get('MAIL_USERNAME'));
            Config::set('mail.mailers.smtp.password', $settings->get('MAIL_PASSWORD'));
            Config::set('mail.mailers.smtp.encryption', $settings->get('MAIL_ENCRYPTION'));
        }

        Config::set('mail.from.address', $settings->get('MAIL_FROM_ADDRESS'));
        Config::set('mail.from.name', $settings->get('MAIL_FROM_NAME'));
    }

    /**
     * Configure Stripe settings dynamically.
     *
     * @param \Illuminate\Support\Collection $settings
     * @return void
     */
    protected function configureStripeSettings($settings)
    {
        Config::set('services.stripe.key', $settings->get('STRIPE_KEY'));
        Config::set('services.stripe.secret', $settings->get('STRIPE_SECRET'));
        Config::set('services.stripe.webhook', $settings->get('STRIPE_WEBHOOK_SECRET'));
    }

    /**
     * Configure AWS settings dynamically.
     *
     * @param \Illuminate\Support\Collection $settings
     * @return void
     */
    protected function configureAwsSettings($settings)
    {
        Config::set('filesystems.disks.s3.key', $settings->get('AWS_ACCESS_KEY_ID'));
        Config::set('filesystems.disks.s3.secret', $settings->get('AWS_SECRET_ACCESS_KEY'));
        Config::set('filesystems.disks.s3.region', $settings->get('AWS_DEFAULT_REGION'));
        Config::set('filesystems.disks.s3.bucket', $settings->get('AWS_BUCKET'));
        Config::set('filesystems.disks.s3.use_path_style_endpoint', filter_var($settings->get('AWS_USE_PATH_STYLE_ENDPOINT', false), FILTER_VALIDATE_BOOLEAN));
        // Note: AWS_FILE_LOAD_BASE is often used in custom logic, usually corresponding to filesystems.disks.s3.url or similar
        if ($settings->has('AWS_FILE_LOAD_BASE')) {
            Config::set('filesystems.disks.s3.url', $settings->get('AWS_FILE_LOAD_BASE'));
        }
    }

    /**
     * Configure JWT settings dynamically.
     *
     * @param \Illuminate\Support\Collection $settings
     * @return void
     */
    protected function configureJwtSettings($settings)
    {
        if ($settings->has('JWT_TTL')) {
            Config::set('jwt.ttl', (int) $settings->get('JWT_TTL'));
        }
        if ($settings->has('JWT_REFRESH_TTL')) {
            Config::set('jwt.refresh_ttl', (int) $settings->get('JWT_REFRESH_TTL'));
        }
        if ($settings->has('JWT_BLACKLIST_ENABLED')) {
            Config::set('jwt.blacklist_enabled', filter_var($settings->get('JWT_BLACKLIST_ENABLED'), FILTER_VALIDATE_BOOLEAN));
        }
    }

    /**
     * Configure Twilio settings dynamically.
     *
     * @param \Illuminate\Support\Collection $settings
     * @return void
     */
    protected function configureTwilioSettings($settings)
    {
        Config::set('services.twilio.sid', $settings->get('TWILIO_SID'));
        Config::set('services.twilio.token', $settings->get('TWILIO_AUTH_TOKEN'));
        Config::set('services.twilio.from', $settings->get('TWILIO_PHONE_NUMBER'));
    }

    /**
     * Configure CORS settings from database.
     */
    protected function configureCorsSettings()
    {
        try {
            $origins = \Illuminate\Support\Facades\Cache::rememberForever('allowed_origins', function () {
                return \App\Models\AllowedOrigin::pluck('origin_url')->toArray();
            });

            if (!empty($origins)) {
                Config::set('cors.allowed_origins', $origins);
            }
        } catch (\Exception $e) {
            \Log::error('Error loading allowed origins: ' . $e->getMessage());
        }
    }
}
