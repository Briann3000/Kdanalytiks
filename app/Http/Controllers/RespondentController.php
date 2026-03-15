<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Response;
use App\Models\Survey;

class RespondentController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $responses = Response::where('respondent_id', $user->id)
            ->with('survey')
            ->orderBy('created_at', 'desc')
            ->get();

        $availableSurveys = Survey::where('type', \App\Enums\SurveyType::Public)
            ->where('status', \App\Enums\SurveyStatus::Active)
            ->whereDoesntHave('responses', function ($query) use ($user) {
                $query->where('respondent_id', $user->id);
            })
            ->limit(10)
            ->get();

        return view('respondent.dashboard', compact('responses', 'availableSurveys'));
    }

    public function history()
    {
        $user = auth()->user();
        $responses = Response::where('respondent_id', $user->id)
            ->with('survey')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('respondent.history', compact('responses'));
    }
}
