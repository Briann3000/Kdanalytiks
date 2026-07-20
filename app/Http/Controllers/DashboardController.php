<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Survey;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user) {
            \App\Models\Survey::cleanupEmptyDrafts($user->id);
        }

        // Safely extract role whether it's cast to Enum or string
        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        $totalSurveys = 0;
        $totalResponses = 0;
        $pendingSurveys = 0;
        $reportsGenerated = 0;

        if ($role === 'organization') {
            $orgId = $user->organization?->id;
            $totalSurveys = Survey::where('organization_id', $orgId)->count();
            $pendingSurveys = Survey::where('organization_id', $orgId)
                ->where('status', \App\Enums\SurveyStatus::Draft->value)->count();

            $totalResponses = \App\Models\Response::whereIn('survey_id', function ($query) use ($orgId) {
                $query->select('id')->from('surveys')->where('organization_id', $orgId);
            })->count();

            $reportsGenerated = \App\Models\ResearchProposal::where('user_id', $user->id)->count();

            $recentActivity = Survey::where('organization_id', $orgId)
                ->latest()
                ->take(5)
                ->get();

        } elseif ($role === 'independent') {
            $indId = $user->independent?->id;
            $totalSurveys = Survey::where('independent_id', $indId)->count();
            $pendingSurveys = Survey::where('independent_id', $indId)
                ->where('status', \App\Enums\SurveyStatus::Draft->value)->count();

            $totalResponses = \App\Models\Response::whereIn('survey_id', function ($query) use ($indId) {
                $query->select('id')->from('surveys')->where('independent_id', $indId);
            })->count();

            $reportsGenerated = \App\Models\ResearchProposal::where('user_id', $user->id)->count();

            $recentActivity = Survey::where('independent_id', $indId)
                ->latest()
                ->take(5)
                ->get();

        } elseif ($role === 'respondent') {
            $totalSurveys = Survey::where('is_template', false)
                ->where('status', \App\Enums\SurveyStatus::Active)
                ->where('type', \App\Enums\SurveyType::Public)->count();
            $totalResponses = \App\Models\Response::where('respondent_id', $user->id)->count();
            $pendingSurveys = 0; // Invitations removed

            $reportsGenerated = \App\Models\ResearchProposal::where('user_id', $user->id)->count();

            $recentPublicSurveys = Survey::where('is_template', false)
                ->where('status', \App\Enums\SurveyStatus::Active)
                ->where('type', \App\Enums\SurveyType::Public)
                ->withCount('responses')
                ->latest()
                ->take(5)
                ->get();
        }

        if ($role === 'respondent') {
            $responses = \App\Models\Response::where('respondent_id', $user->id)
                ->with('survey')
                ->latest()
                ->get();

            $availableSurveys = Survey::where('is_template', false)
                ->where('type', \App\Enums\SurveyType::Public)
                ->where('status', \App\Enums\SurveyStatus::Active)
                ->whereDoesntHave('responses', function ($query) use ($user) {
                    $query->where('respondent_id', $user->id);
                })
                ->get();

            $wallet = $user->wallet ?: \App\Models\Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            return view('respondent.dashboard', compact('responses', 'availableSurveys', 'wallet', 'reportsGenerated'));
        }

        $displayName = $user->organization?->name ?? $user->independent?->name ?? $user->name;

        // Resolve subscription tier for org/independent users
        $entity = $user->organization ?? $user->independent ?? null;
        $subscriptionTier = ($user->hasActiveSubscription() && $entity)
            ? $entity->subscriptionTier
            : \App\Models\SubscriptionTier::where('slug', 'free')->first();

        return view('dashboard', array_merge(compact(
            'role',
            'displayName',
            'totalSurveys',
            'totalResponses',
            'pendingSurveys',
            'reportsGenerated',
            'subscriptionTier'
        ), [
            'recentPublicSurveys' => $recentPublicSurveys ?? collect(),
            'recentActivity' => $recentActivity ?? collect()
        ]));
    }
}
