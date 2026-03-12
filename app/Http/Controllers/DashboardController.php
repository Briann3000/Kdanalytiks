<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Survey;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

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

            $reportsGenerated = Survey::where('organization_id', $orgId)
                ->whereHas('responses')
                ->count();

        } elseif ($role === 'independent') {
            $indId = $user->independent?->id;
            $totalSurveys = Survey::where('independent_id', $indId)->count();
            $pendingSurveys = Survey::where('independent_id', $indId)
                ->where('status', \App\Enums\SurveyStatus::Draft->value)->count();

            $totalResponses = \App\Models\Response::whereIn('survey_id', function ($query) use ($indId) {
                $query->select('id')->from('surveys')->where('independent_id', $indId);
            })->count();

            $reportsGenerated = Survey::where('independent_id', $indId)
                ->whereHas('responses')
                ->count();

        } elseif ($role === 'respondent') {
            $totalSurveys = Survey::where('status', \App\Enums\SurveyStatus::Active)
                ->where('type', \App\Enums\SurveyType::Public)->count();
            $totalResponses = \App\Models\Response::where('respondent_id', $user->id)->count();
            $pendingSurveys = 0; // Placeholder for invitations

            // For respondents, reports generated could be surveys they completed that have analytics
            $reportsGenerated = Survey::whereHas('responses', function ($q) use ($user) {
                $q->where('respondent_id', $user->id);
            })->count();

            $recentPublicSurveys = Survey::where('status', \App\Enums\SurveyStatus::Active)
                ->where('type', \App\Enums\SurveyType::Public)
                ->withCount('responses')
                ->latest()
                ->take(5)
                ->get();
        }

        $displayName = $user->organization?->name ?? $user->independent?->name ?? $user->name;

        return view('dashboard', array_merge(compact(
            'role',
            'displayName',
            'totalSurveys',
            'totalResponses',
            'pendingSurveys',
            'reportsGenerated'
        ), [
            'recentPublicSurveys' => $recentPublicSurveys ?? collect()
        ]));
    }
}
