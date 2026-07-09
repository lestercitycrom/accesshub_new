<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Legacy alias — still used by config-only components (settings, telegram
        // users, server) and the EnsureAdmin middleware. Equivalent to hub-manage.
        Gate::define('admin', static fn ($user): bool => $user->isAdmin());

        // Capability tiers (view ⊂ operate ⊂ manage).
        Gate::define('hub-view', static fn ($user): bool => $user->canAccessHub());
        Gate::define('hub-operate', static fn ($user): bool => $user->canOperate());
        Gate::define('hub-manage', static fn ($user): bool => $user->isAdmin());
    }
}