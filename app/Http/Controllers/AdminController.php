<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Organization;
use App\Models\Independent;
use App\Models\Survey;
use App\Models\Payment;
use App\Models\Response;

class AdminController extends Controller
{
    public function dashboard()
    {
        Survey::cleanupEmptyDrafts();
        $stats = [
            'totalUsers' => User::count(),
            'totalResponses' => Response::count(),
            'totalOrganizations' => Organization::count(),
            'totalResearchers' => Independent::count(),
            'totalSurveys' => Survey::where('is_template', false)->count(),
            'draftSurveys' => Survey::where('is_template', false)->where('status', \App\Enums\SurveyStatus::Draft)->count(),
        ];

        $publicStats = [
            'count' => Survey::where('is_template', false)->where('type', \App\Enums\SurveyType::Public)->count(),
            'responses' => Response::whereHas('survey', function ($q) {
                $q->where('is_template', false)->where('type', \App\Enums\SurveyType::Public);
            })->count(),
        ];

        $publicStats['average'] = $publicStats['count'] > 0
            ? round($publicStats['responses'] / $publicStats['count'], 2)
            : 0;

        $recentPublicSurveys = Survey::where('is_template', false)
            ->where('type', \App\Enums\SurveyType::Public)
            ->withCount('responses')
            ->latest()
            ->take(3)
            ->get();

        $latestUsers = User::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'publicStats', 'recentPublicSurveys', 'latestUsers'));
    }

    public function analytics()
    {
        $totalSurveys = Survey::where('is_template', false)->count();
        $totalResponses = Response::count();
        $totalOrganizations = Organization::count();
        $totalRespondents = User::where('role', \App\Enums\UserRole::Respondent->value)
            ->orWhere('role', \App\Enums\UserRole::Respondent)
            ->count();

        $categoryStats = Survey::where('is_template', false)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->get();

        $responseTrends = Response::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.analytics', compact(
            'totalSurveys',
            'totalResponses',
            'totalOrganizations',
            'totalRespondents',
            'categoryStats',
            'responseTrends'
        ));
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status !== 'all') {
                $query->where('status', $request->status);
            }
        } else {
            $query->where('status', 'active');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $status = \App\Enums\UserStatus::tryFrom($request->status) ?? \App\Enums\UserStatus::Active;
        $user->update(['status' => $status]);
        return back()->with('success', "User {$user->name} status updated to {$status->value}.");
    }

    public function createUser()
    {
        $roles = \App\Enums\UserRole::cases();
        return view('admin.users_create', compact('roles'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => bcrypt($request->password),
            'status' => \App\Enums\UserStatus::Active->value,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function surveys(Request $request)
    {
        Survey::cleanupEmptyDrafts();
        $query = Survey::where('is_template', false)->with(['organization', 'independent'])->withCount('responses');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            if ($request->source === 'organization')
                $query->whereNotNull('organization_id');
            elseif ($request->source === 'independent')
                $query->whereNotNull('independent_id');
            elseif ($request->source === 'admin')
                $query->whereNull('organization_id')->whereNull('independent_id');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('organization', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('independent', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        $validSorts = ['title', 'type', 'status', 'responses_count', 'created_at', 'created_by'];
        if (!in_array($sortBy, $validSorts))
            $sortBy = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc']))
            $sortDir = 'desc';

        $surveys = $query->orderBy($sortBy, $sortDir)->paginate(20)->withQueryString();

        return view('admin.surveys', compact('surveys'));
    }

    public function reports()
    {
        $totalUsers = User::count();
        $totalSurveys = Survey::where('is_template', false)->count();
        $totalResponses = Response::count();
        $usersByRole = User::selectRaw('role, count(*) as count')->groupBy('role')->pluck('count', 'role');
        $surveysByStatus = Survey::where('is_template', false)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        return view('admin.reports', compact('totalUsers', 'totalSurveys', 'totalResponses', 'usersByRole', 'surveysByStatus'));
    }
    public function approve(Survey $survey)
    {
        $survey->update(['status' => \App\Enums\SurveyStatus::Active]);
        return back()->with('success', "Survey '{$survey->title}' has been approved and is now active.");
    }

    public function deactivate(Survey $survey)
    {
        $survey->update(['status' => \App\Enums\SurveyStatus::Closed]);
        return back()->with('success', "Survey '{$survey->title}' has been deactivated.");
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'survey_ids' => 'required|array',
            'survey_ids.*' => 'exists:surveys,id'
        ]);

        $count = \App\Models\Survey::whereIn('id', $request->survey_ids)->delete();

        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} surveys from inventory."
            ]);
        }

        return back()->with('success', "Successfully deleted {$count} surveys.");
    }
}