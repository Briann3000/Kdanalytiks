<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Safely extract the role value
        $userRoleValue = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        if (!in_array($userRoleValue, $roles)) {
            abort(403, 'Unauthorized. You do not have the required role to access this resource.');
        }

        return $next($request);
    }
}
