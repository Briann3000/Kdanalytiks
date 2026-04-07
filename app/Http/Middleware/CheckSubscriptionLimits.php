<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimits
{
    protected $aiService;

    public function __construct(\App\Services\AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limitType): Response
    {
        $user = $request->user();
        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        // Strictly only check limits for organizations
        if ($role !== 'organization' || !$user->organization) {
            return $next($request);
        }

        $organization = $user->organization;

        $tier = $organization->subscriptionTier ?? \App\Models\SubscriptionTier::where('slug', 'free')->first();

        if ($limitType === 'surveys') {
            $currentCount = $organization->surveys()->count();
            if ($tier->max_surveys !== -1 && $currentCount >= $tier->max_surveys) {
                // If it's a GET request to create, we allow it (so they can see the builder with a warning)
                // If it's a POST request (store), we block it.
                if ($request->isMethod('get') && $request->routeIs('surveys.create')) {
                    return $next($request);
                }

                return redirect()->back()->with('error', "Upgrade Required: Your tier allows a maximum of {$tier->max_surveys} surveys.");
            }
        }

        if ($limitType === 'ai') {
            if (!$this->aiService->checkUsageLimit($organization)) {
                return response()->json(['error' => "Upgrade Required: Your monthly AI limit of {$tier->ai_limit_per_month} has been reached."], 403);
            }
        }

        return $next($request);
    }
}
