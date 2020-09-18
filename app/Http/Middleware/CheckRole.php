<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param $role
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        if (!in_array($role, $request->user()->roles()->pluck('code')->toArray(), true)) {
            abort(403, 'Forbidden Access.');
        }
        return $next($request);
    }

}
