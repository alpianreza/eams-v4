<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Bootstrap remains the paginator contract while legacy pages coexist
        // with prefixed Tailwind. This prevents intrinsic oversized SVG arrows.
        Paginator::useBootstrapFive();

        // Authorization expressed via Gates/Policies — never hard-coded in controllers.
        Gate::define('write', fn (User $user): bool => $user->hasWriteAccess());
        Gate::define('access-page', fn (User $user, string $page): bool => $user->canAccessPage($page));
        Gate::define('manage-master-data', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance'));
        Gate::define('manage-inventory', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance'));
        Gate::define('access-compliance-pdf', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance') || $user->canAccessPage('compliance'));
        Gate::define('manage-system', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-settings', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance'));
        Gate::define('access-print-center', fn (User $user): bool => $user->isAdmin() || $user->hasRole('compliance') || $user->hasRole('auditor'));
        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());

        // Browser component showcase is never registered in production.
        if ($this->app->environment(['local', 'testing'])) {
            Route::middleware(['web', 'auth'])
                ->get('/__qa/ui-components', fn () => view('qa.ui-components'))
                ->name('qa.ui-components');
        }
    }
}
