<?php

declare(strict_types=1);

namespace JonasPardon\Pulse\Classes;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pulse's own middleware throws an AuthorizationException, which Winter's
 * error page renders as a 500 before Laravel can turn it into a 403.
 */
class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Gate::allows('viewPulse'), 403);

        return $next($request);
    }
}
