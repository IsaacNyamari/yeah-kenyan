<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes sign-ups when an administrator has switched registration off.
 *
 * Applied to Fortify's routes as a whole and narrowed by route name here, so
 * the POST endpoint is closed alongside the form. Blocking only the form would
 * leave the endpoint reachable by anyone who kept the page open.
 */
class EnsureRegistrationIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('register', 'register.store')) {
            return $next($request);
        }

        abort_unless(
            Setting::boolean('registration_enabled', true),
            403,
            'Registration is currently closed.',
        );

        return $next($request);
    }
}
