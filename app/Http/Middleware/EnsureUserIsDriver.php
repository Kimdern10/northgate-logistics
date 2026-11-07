<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDriver
{
    /**
     * Only drivers (and staff, who may cover a route) open the driver page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_driver || $request->user()?->is_admin, Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
