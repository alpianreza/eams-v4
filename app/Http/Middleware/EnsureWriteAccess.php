<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global write-guard (BR-42, WriteFilter equivalent).
 *
 * Any non-GET/HEAD/OPTIONS request from an authenticated user whose
 * permission is 'read' is rejected with 403 — except whitelisted public paths.
 */
class EnsureWriteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasWriteAccess() && $this->isMutation($request) && ! $this->isWhitelisted($request)) {
            abort(403, 'Akses hanya-baca.');
        }

        return $next($request);
    }

    protected function isMutation(Request $request): bool
    {
        return ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    protected function isWhitelisted(Request $request): bool
    {
        foreach ((array) config('eams.write_whitelist', []) as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
