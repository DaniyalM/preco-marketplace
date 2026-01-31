<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
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
        // Admin can do everything
        Gate::before(function ($user, $ability) {
            if (isset($user['roles']) && in_array('admin', $user['roles'])) {
                return true;
            }
            return null;
        });

        // Vendor Gates
        Gate::define('manage-vendor', function (array $user, Vendor $vendor) {
            return $user['id'] === $vendor->keycloak_user_id;
        });

        Gate::define('create-product', function (array $user) {
            return in_array('vendor', $user['roles'] ?? []);
        });

        Gate::define('manage-product', function (array $user, Product $product) {
            if (in_array('admin', $user['roles'] ?? [])) {
                return true;
            }
            
            if (!in_array('vendor', $user['roles'] ?? [])) {
                return false;
            }

            return $product->vendor && $product->vendor->keycloak_user_id === $user['id'];
        });

        Gate::define('fulfill-order-item', function (array $user, OrderItem $item) {
            if (in_array('admin', $user['roles'] ?? [])) {
                return true;
            }
            
            if (!in_array('vendor', $user['roles'] ?? [])) {
                return false;
            }

            return $item->vendor && $item->vendor->keycloak_user_id === $user['id'];
        });

        // Customer Gates
        Gate::define('view-own-orders', function (array $user, Order $order) {
            return $user['id'] === $order->keycloak_user_id;
        });

        Gate::define('cancel-order', function (array $user, Order $order) {
            return $user['id'] === $order->keycloak_user_id && $order->canBeCancelled();
        });

        // Admin-only Gates
        Gate::define('approve-vendor', function (array $user) {
            return in_array('admin', $user['roles'] ?? []);
        });

        Gate::define('manage-categories', function (array $user) {
            return in_array('admin', $user['roles'] ?? []);
        });

        Gate::define('view-all-orders', function (array $user) {
            return in_array('admin', $user['roles'] ?? []);
        });

        Gate::define('manage-users', function (array $user) {
            return in_array('admin', $user['roles'] ?? []);
        });
    }
}
