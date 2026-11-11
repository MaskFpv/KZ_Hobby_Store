<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Automatically finding the Policies
        Gate::guessPolicyNamesUsing(function ($modelClass) {
            return 'App\\Policies\\' . class_basename($modelClass) . 'Policy';
        });

        $this->registerPolicies();

        // Implicitly grant "Super Admin" role all permission checks using can()
        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Permission khusus (di luar CRUD), lihat PermissionsSeeder::SPECIAL
        Gate::define('view-dashboard', fn ($user) => $user->hasPermissionTo('view dashboard'));
        Gate::define('view-reports', fn ($user) => $user->hasPermissionTo('view reports'));
        Gate::define('view-profit', fn ($user) => $user->hasPermissionTo('view profit'));
        Gate::define('handover-orders', fn ($user) => $user->hasPermissionTo('handover orders'));
    }
}
