<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\Organization;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function dashboard()
    {
        $organization = auth()->user()->organization;
        $surveys = $organization ? $organization->surveys : collect();

        return view('organization.dashboard', compact('organization', 'surveys'));
    }

    public function surveys()
    {
        $organization = auth()->user()->organization;
        $surveys = $organization ? $organization->surveys()->withCount('responses')->get() : collect();

        return view('organization.surveys', compact('surveys'));
    }

    public function createSurvey()
    {
        return view('organization.create-survey');
    }

    public function storeSurvey(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:Marketing,Academic,Product,Political',
            'type' => 'required|in:public,invitation',
        ]);

        $organization = auth()->user()->organization;

        Survey::create([
            'organization_id' => $organization->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'type' => $request->type,
            'status' => 'draft',
        ]);

        return redirect()->route('organization.surveys')->with('success', 'Survey created successfully.');
    }

    public function editSurvey(Survey $survey)
    {
        $organization = auth()->user()->organization;
        if (!$organization || $survey->organization_id !== $organization->id) {
            abort(403, 'Unauthorized');
        }

        return view('organization.edit-survey', compact('survey'));
    }

    public function updateSurvey(Request $request, Survey $survey)
    {
        $organization = auth()->user()->organization;
        if (!$organization || $survey->organization_id !== $organization->id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:Marketing,Academic,Product,Political',
            'type' => 'required|in:public,invitation',
            'status' => 'required|in:draft,active,closed',
        ]);

        $survey->update($request->only(['title', 'description', 'category', 'type', 'status']));

        return redirect()->route('organization.surveys')->with('success', 'Survey updated successfully.');
    }

    public function deleteSurvey(Survey $survey)
    {
        $organization = auth()->user()->organization;
        if (!$organization || $survey->organization_id !== $organization->id) {
            abort(403, 'Unauthorized');
        }

        $survey->delete();

        return redirect()->route('organization.surveys')->with('success', 'Survey deleted successfully.');
    }

    public function questions(Survey $survey)
    {
        $organization = auth()->user()->organization;
        if (!$organization || $survey->organization_id !== $organization->id) {
            abort(403, 'Unauthorized');
        }

        $survey->load('questions');

        return view('organization.questions', compact('survey'));
    }

    public function responses(Survey $survey)
    {
        $organization = auth()->user()->organization;
        if (!$organization || $survey->organization_id !== $organization->id) {
            abort(403, 'Unauthorized');
        }

        $survey->load('responses.respondent', 'responses.answers.question');

        return view('organization.responses', compact('survey'));
    }
}
