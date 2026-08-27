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
        Gate::define('manage-master-data', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance'));
        Gate::define('manage-inventory', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance'));
        // Q-008: Compliance PDF is for admin + users with Compliance access only.
        Gate::define('access-compliance-pdf', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance') || $user->canAccessPage('compliance'));
        // Admin-only system tools (audit logs, login sessions, backups).
        Gate::define('manage-system', fn (User $user): bool => $user->isAdmin());
        // System settings (admin or compliance).
        Gate::define('manage-settings', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance'));
        // Print center (admin, compliance, auditor).
        Gate::define('access-print-center', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance') || $user->hasRole('auditor'));
        // User management (admin-only).
        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());
    }
}
