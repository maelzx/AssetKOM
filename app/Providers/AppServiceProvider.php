<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Default the dev server to APP_PORT (see config/app.php) and let
        // "php artisan serve" honour it without passing --port manually.
        $this->app->bind(
            ServeCommand::class,
            \App\Console\Commands\ServeCommand::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Administrators implicitly pass every authorization check.
        Gate::before(function (User $user) {
            return $user->isAdmin() ? true : null;
        });

        Gate::define('manage-settings', fn (User $user) => $user->hasRole(Role::Admin, Role::Manager));
        Gate::define('view-reports', fn (User $user) => $user->hasRole(Role::Admin, Role::Manager));
        Gate::define('manage-assets', fn (User $user) => $user->hasRole(Role::Admin, Role::Manager));
        Gate::define('manage-catalog', fn (User $user) => $user->hasRole(Role::Admin, Role::Manager));
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
    }
}
