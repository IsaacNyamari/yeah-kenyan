<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use App\Support\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        $this->configureGates();
    }

    /**
     * Authorisation that depends on more than a permission alone.
     */
    protected function configureGates(): void
    {
        /*
         * Posting is both a permission and a site-wide switch. Administrators
         * stay exempt from the switch, so turning it off to pause submissions
         * never leaves the site with nobody able to publish.
         */
        Gate::define('post-content', fn (User $user): bool => $user->can(Permission::ManageNews->value)
            && (Setting::boolean('posting_enabled', true) || $user->isAdministrator()));

        Gate::define('moderate-content', fn (User $user): bool => $user->can(Permission::ModeratePosts->value));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

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
