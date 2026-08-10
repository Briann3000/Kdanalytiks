<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\OrgResourcePool;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrgResourceController extends Controller
{
    public function usage(Request $request): JsonResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $pool = $org->ensureResourcePool();

        return response()->json([
            'organization_id' => $org->id,
            'organization_name' => $org->name,
            'max_seats' => $org->max_seats,
            'active_members_count' => $org->activeMembers()->count(),
            'ai_analyses' => [
                'limit' => $pool->ai_analyses_limit,
                'used' => $pool->ai_analyses_used,
                'pct' => $pool->aiUsagePct(),
                'can_use' => $pool->canUseAiAnalysis(),
            ],
            'transcription' => [
                'limit' => $pool->transcription_minutes_limit,
                'used' => $pool->transcription_minutes_used,
                'can_use' => $pool->canTranscribe(),
            ],
            'socius' => [
                'limit' => $pool->socius_chat_sessions_limit,
                'used' => $pool->socius_chat_sessions_used,
                'can_use' => $pool->canUseSocius(),
            ],
            'reports' => [
                'limit' => $pool->report_exports_limit,
                'used' => $pool->report_exports_used,
            ],
            'surveys' => [
                'limit' => $pool->survey_limit,
                'current' => $org->surveys()->count(),
                'has_reached' => $org->hasReachedSurveyLimit(),
            ],
            'reset_at' => $pool->reset_at ? $pool->reset_at->toIso8601String() : null,
        ]);
    }
}
