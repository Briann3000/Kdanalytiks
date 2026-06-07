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
        if (!$user)
            return $next($request);

        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        // Determine if they are in a subscription-bound role
        $entity = null;
        if ($role === 'organization') {
            $entity = $user->organization;
        } elseif ($role === 'independent') {
            $entity = $user->independent;
        }

        if (!$entity) {
            return $next($request);
        }

        $tier = $entity->subscriptionTier ?? \App\Models\SubscriptionTier::where('slug', 'free')->first();

        if ($limitType === 'surveys') {
            $currentCount = $entity->surveys()->count();
            if ($tier->max_surveys !== -1 && $currentCount >= $tier->max_surveys) {
                // If it's a GET request to create, we allow it (so they can see the builder with a warning)
                if ($request->isMethod('get') && ($request->routeIs('surveys.create') || $request->routeIs('surveys.initialize'))) {
                    return $next($request);
                }

                $limit = $tier->max_surveys === -1 ? 'Unlimited' : $tier->max_surveys;
                return redirect()->back()->with('error', "Upgrade Required: Your current tier allows a maximum of {$limit} surveys.");
            }
        }

        if ($limitType === 'ai') {
            if (!$this->aiService->checkUsageLimit($entity)) {
                $limitVal = (int) $tier->ai_limit_per_month;
                $limit = $limitVal === -1 ? 'Unlimited' : $limitVal;
                return response()->json(['error' => "Upgrade Required: Your monthly AI limit of {$limit} has been reached."], 403);
            }
        }

        if ($limitType === 'dashboard') {
            if ($tier->slug === 'free') {
                return redirect()->back()->with('error', "Upgrade Required: The Interactive Dashboard Builder is only available on Pro & Enterprise tiers.");
            }
        }

        return $next($request);
    }
}
