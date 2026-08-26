<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to administrators.
 *
 * Signed-in non-admins get a 403 rather than a redirect, so the failure is
 * explicit instead of looking like a broken link.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdministrator(), 403, 'This area is restricted to administrators.');

        return $next($request);
    }
}
