<?php

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn (): ImageManager => new ImageManager(new Driver));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Render dates in the site's timezone while keeping storage in UTC.
     *
     * Moving app.timezone off UTC would leave rows written before the change
     * three hours behind rows written after it, with nothing recording which
     * is which. Converting at display time avoids that entirely.
     */
    protected function registerDisplayTimezone(): void
    {
        // config() only substitutes the default for a missing key, not a null one.
        $macro = fn () => $this->setTimezone(config('site.timezone') ?: 'UTC');

        Carbon::macro('siteTime', $macro);
        CarbonImmutable::macro('siteTime', $macro);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        $this->registerDisplayTimezone();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
