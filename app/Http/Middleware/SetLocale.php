<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('app.locale');

        if (auth()->check()) {
            $locale = auth()->user()->locale;
        } elseif (session()->has('locale')) {
            $locale = session()->get('locale');
        }

        if ($locale) {
            \Illuminate\Support\Facades\App::setLocale($locale);
        }

        return $next($request);
    }
}
