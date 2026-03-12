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
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Safely extract the role value whether it's cast to an Enum or remaining as a string
        $userRoleValue = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        // Check against the requested role string
        if ($userRoleValue !== $role) {
            abort(403, 'Unauthorized. You do not have the required role to access this resource.');
        }

        return $next($request);
    }
}
