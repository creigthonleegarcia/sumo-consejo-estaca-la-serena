<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gate para acceso de administrador
        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });

        // Gate para acceso de presidencia (incluye admin)
        Gate::define('presidencia', function (User $user) {
            return $user->isAdmin() || $user->isPresidencia();
        });
    }
}
