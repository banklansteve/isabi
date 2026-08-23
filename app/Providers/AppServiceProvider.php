<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Auth\SessionLifetime;
use App\Support\Seo;
use App\Support\Staff\AppSettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Seo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        try {
            if (Schema::hasTable('app_settings')) {
                app(AppSettingsService::class)->applyOverrides();
            }
        } catch (\Throwable) {
            // Database may not be ready during install / package discovery.
        }

        app(SessionLifetime::class)->primeHandlerLifetime();

        Gate::define('access-admin', fn (User $user) => $user->canDo('admin.access'));
        Gate::define('manage-users', fn (User $user) => $user->canDo('admin.users.manage'));
        Gate::define('view-users', fn (User $user) => $user->canDo('admin.users.view') || $user->canDo('admin.users.manage'));
        Gate::define('manage-content', fn (User $user) => $user->canDo('admin.content.manage'));
        Gate::define('manage-requests', fn (User $user) => $user->canDo('admin.requests.manage'));
        Gate::define('manage-billing', fn (User $user) => $user->canDo('admin.billing.manage'));
        Gate::define('manage-settings', fn (User $user) => $user->canDo('admin.settings.manage'));
        Gate::define('manage-roles', fn (User $user) => $user->canDo('admin.roles.manage'));
        Gate::define('invite-staff', fn (User $user) => $user->canDo('admin.staff.invite'));
        Gate::define('manage-support', fn (User $user) => $user->canDo('admin.support.manage'));
        Gate::define('manage-moderation', fn (User $user) => $user->canDo('admin.moderation.manage'));
        Gate::define('manage-patrol', fn (User $user) => $user->canDo('admin.patrol.manage'));

        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        // Convenience: allow checking role via Gate::forUser()->check('role:super_admin')
        foreach (UserRole::cases() as $role) {
            Gate::define('role:'.$role->value, fn (User $user) => $user->hasRole($role));
        }

        Route::bind('staff', function (string $value) {
            return User::query()->staff()->whereKey($value)->firstOrFail();
        });
    }
}
