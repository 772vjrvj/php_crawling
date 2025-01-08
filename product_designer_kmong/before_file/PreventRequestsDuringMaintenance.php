<?php

namespace Illuminate\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;

class PreventRequestsDuringMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if (app()->isDownForMaintenance()) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
