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

        // Capability tiers:
        //  hub-view   — panel access + read + fulfillment (orders/issuance/problems). All roles.
        //  hub-create-account — add inventory without exposing the account base. All roles.
        //  hub-delivery-links — generate/download auto-delivery links. All roles.
        //  hub-supply — inspect/edit accounts, delete link batches, edit instructions. Admin + manager.
        //  hub-manage — system config & user management. Admin only.
        Gate::define('hub-view', static fn ($user): bool => $user->canAccessHub());
        Gate::define('hub-create-account', static fn ($user): bool => $user->canCreateAccounts());
        Gate::define('hub-delivery-links', static fn ($user): bool => $user->canManageDeliveryLinks());
        Gate::define('hub-supply', static fn ($user): bool => $user->canSupply());
        Gate::define('hub-manage', static fn ($user): bool => $user->isAdmin());
    }
}
