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
                // $_ENV[$key] = $value; // We don't need to manually set $_ENV as Laravel uses config() for most things
            }

            // Explicitly configure email settings if present
            if ($settings->isNotEmpty()) {
                $this->configureMailSettings($settings);
                $this->configureStripeSettings($settings);
            }

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
}
