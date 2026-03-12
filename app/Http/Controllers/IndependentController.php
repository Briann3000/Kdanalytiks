<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Survey;
use App\Models\Independent;

class IndependentController extends Controller
{
    public function dashboard()
    {
        $independent = auth()->user()->independent;
        $surveys = $independent ? $independent->surveys()->withCount('responses')->get() : collect();

        return view('independent.dashboard', compact('independent', 'surveys'));
    }

    public function surveys()
    {
        $independent = auth()->user()->independent;
        $surveys = $independent ? $independent->surveys()->withCount('responses')->get() : collect();

        return view('independent.surveys', compact('surveys'));
    }

    public function createSurvey()
    {
        return view('independent.create-survey');
    }

    public function storeSurvey(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:Marketing,Academic,Product,Political',
            'type' => 'required|in:public,invitation',
        ]);

        $independent = auth()->user()->independent;

        Survey::create([
            'independent_id' => $independent->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'type' => $request->type,
            'status' => 'draft',
        ]);

        return redirect()->route('independent.surveys')->with('success', 'Survey created successfully.');
    }
}
