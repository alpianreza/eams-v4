<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Authorization expressed via Gates/Policies — never hard-coded in controllers.
        Gate::define('write', fn (User $user): bool => $user->hasWriteAccess());
        Gate::define('access-page', fn (User $user, string $page): bool => $user->canAccessPage($page));
        // Master data management: admin / compliance (write permission enforced globally by the write-guard).
        Gate::define('manage-master-data', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance'));
        // Q-008: Compliance PDF is for admin + users with Compliance access only.
        Gate::define('access-compliance-pdf', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance') || $user->canAccessPage('compliance'));
    }
}
