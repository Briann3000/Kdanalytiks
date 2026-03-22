<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SurveyController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public Routes
Route::get('/', function () {
    return view('welcome'); // Landing Page
})->name('home');

Route::get('/surveys/public', [SurveyController::class, 'publicIndex'])->name('surveys.public');

// Quick login for previewing purposes
Route::get('/login-preview/{role}', function ($role) {
    $enumRole = \App\Enums\UserRole::tryFrom($role);
    if (!$enumRole)
        abort(404);

    $user = User::firstOrCreate(
        ['email' => "{$role}@example.com"],
        [
            'name' => ucfirst($role) . ' User',
            'password' => bcrypt('password'),
            'role' => $enumRole->value,
            'status' => \App\Enums\UserStatus::Active->value
        ]
    );

    Auth::login($user);
    return redirect()->route($role . '.dashboard');
});
Route::post('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    return redirect('/');
})->name('logout');

Route::get('/login', function () {
    return view('auth.login-selection');
})->name('login');

Route::get('/login/{role}', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login.role');
Route::post('/login/{role}', [\App\Http\Controllers\Auth\LoginController::class, 'login']);

Route::get('/register/{role}', [\App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register/{role}', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);

// Password Reset Routes
Route::get('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('password.email');
Route::get('reset-password/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])
    ->name('password.update');

// Individual role login routes for redirects
Route::name('admin.')->prefix('admin')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
});
Route::name('organization.')->prefix('organization')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
});
Route::name('independent.')->prefix('independent')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
});
Route::name('respondent.')->prefix('respondent')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
});

// Email Verification Routes
Route::get('/email/verify', function () {
    return view('auth.verify');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('home');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.resend');

// Shared Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Survey Hub & Core Actions
    Route::get('/surveys/{survey}/responses', [SurveyController::class, 'showResponses'])->name('surveys.responses');
    Route::get('/surveys/{survey}/responses/{response}', [SurveyController::class, 'showResponseDetail'])->name('surveys.responses.show');
    Route::get('/surveys/{survey}/export', [SurveyController::class, 'exportResponses'])->name('surveys.export');
    Route::get('/surveys/{survey}/export-pdf', [SurveyController::class, 'exportPdf'])->name('surveys.export_pdf');
    Route::get('/surveys/{survey}/report', [SurveyController::class, 'report'])->name('surveys.report');
    
    // Core Survey CRUD (Except Index, Show, Destroy handled separately)
    Route::resource('surveys', \App\Http\Controllers\SurveyController::class)->except(['index', 'show', 'destroy']);
    Route::delete('/surveys/{survey}', [SurveyController::class, 'destroy'])->name('surveys.destroy');
    Route::post('/surveys/initialize', [SurveyController::class, 'initialize'])->name('surveys.initialize');
    
    // Quick-Access Project Lists
    // Projects & Library Overhaul (Kobo-style)
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/active', [SurveyController::class, 'index'])->name('active');
        Route::get('/archived', [SurveyController::class, 'archivedIndex'])->name('archived');
        Route::get('/drafts', [SurveyController::class, 'draftsIndex'])->name('drafts');
        
        // Use /projects as the hub
        Route::get('/', [SurveyController::class, 'hub'])->name('index');
        
        // Project Hub (Single Survey Management content)
        Route::prefix('{survey}')->group(function () {
            Route::get('/', [SurveyController::class, 'projectSummary'])->name('summary');
            Route::get('/data', [SurveyController::class, 'showResponses'])->name('data');
            Route::get('/reports', [SurveyController::class, 'report'])->name('reports');
            Route::get('/settings', [SurveyController::class, 'projectSettings'])->name('settings');
            Route::post('/settings', [SurveyController::class, 'updateProjectSettings'])->name('settings.update');
            Route::post('/collaborators', [SurveyController::class, 'addCollaborator'])->name('collaborators.add');
            Route::delete('/collaborators/{permission}', [SurveyController::class, 'removeCollaborator'])->name('collaborators.remove');
            Route::post('/publish', [SurveyController::class, 'publish'])->name('publish');
            Route::post('/archive', [SurveyController::class, 'archive'])->name('archive');
        });
    });


    Route::prefix('library')->name('library.')->group(function () {
        Route::get('/templates', [SurveyController::class, 'templatesIndex'])->name('templates');
        Route::get('/templates/{survey}/clone', [SurveyController::class, 'cloneTemplate'])->name('templates.clone');
        Route::get('/questions', [SurveyController::class, 'getLibraryQuestions'])->name('questions');
        Route::post('/questions', [SurveyController::class, 'saveToLibrary'])->name('questions.save');
    });

    // Research Proposal Studio
    Route::get('/research-proposal/history', [\App\Http\Controllers\ResearchProposalController::class, 'history'])->name('research-proposal.history');
    Route::post('/research-proposal/store', [\App\Http\Controllers\ResearchProposalController::class, 'storeProposal'])->name('research-proposal.store');
    Route::get('/research-proposal/export-proposal/{proposal}', [\App\Http\Controllers\ResearchProposalController::class, 'exportProposal'])->name('research-proposal.export-proposal');
    Route::post('/research-proposal/generate', [\App\Http\Controllers\ResearchProposalController::class, 'generate'])->name('research-proposal.generate');
    Route::get('/research-proposal/preview/{reportId}', [\App\Http\Controllers\ResearchProposalController::class, 'preview'])->name('research-proposal.preview');
    Route::post('/research-proposal/export/{reportId}', [\App\Http\Controllers\ResearchProposalController::class, 'export'])->name('research-proposal.export');
    
    Route::resource('research-proposal', \App\Http\Controllers\ResearchProposalController::class);
});

// Public Survey Views
Route::get('/surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
Route::post('/surveys/{survey}/submit', [SurveyController::class, 'submit'])->middleware('throttle:10,1')->name('surveys.submit');

Route::post('/surveys/{survey}/invite', [SurveyController::class, 'invite'])->name('surveys.invite');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users.index');
    Route::get('/users/create', [\App\Http\Controllers\AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users/store', [\App\Http\Controllers\AdminController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{user}/status', [\App\Http\Controllers\AdminController::class, 'updateUserStatus'])->name('users.status');
    Route::get('/surveys', [\App\Http\Controllers\AdminController::class, 'surveys'])->name('surveys.index');

    Route::get('/reports-summary', [\App\Http\Controllers\AdminController::class, 'reports'])->name('reports.summary');
    Route::get('/reports', [SurveyController::class, 'reportsIndex'])->name('reports.index');
    Route::get('/analytics', [\App\Http\Controllers\AdminController::class, 'analytics'])->name('analytics.index');
    Route::post('/surveys/{survey}/approve', [\App\Http\Controllers\AdminController::class, 'approve'])->name('surveys.approve');
    Route::post('/surveys/{survey}/deactivate', [\App\Http\Controllers\AdminController::class, 'deactivate'])->name('surveys.deactivate');
});

// Organization Routes
Route::middleware(['auth', 'role:organization'])->prefix('organization')->name('organization.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');

    Route::get('/responses', [SurveyController::class, 'responsesIndex'])->name('responses.index');
    Route::get('/reports', [SurveyController::class, 'reportsIndex'])->name('reports.index');
});

// Independent Researcher Routes
Route::middleware(['auth', 'role:independent'])->prefix('independent')->name('independent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');

    Route::get('/responses', [SurveyController::class, 'responsesIndex'])->name('responses.index');
    Route::get('/reports', [SurveyController::class, 'reportsIndex'])->name('reports.index');
});

// Respondent Routes
Route::middleware(['auth', 'role:respondent'])->prefix('respondent')->name('respondent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/history', [\App\Http\Controllers\RespondentController::class, 'history'])->name('history');
    Route::get('/reports', [SurveyController::class, 'reportsIndex'])->name('reports.index');
});

// AI Integration Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/ai/generate-survey', [\App\Http\Controllers\AiController::class, 'generateSchema'])->name('ai.generate');
    Route::get('/ai/insights/question/{question}', [\App\Http\Controllers\InsightController::class, 'generateQuestionInsight'])->name('ai.insights.question');
    
    // Qualitative Reports
    Route::get('/surveys/{survey}/qualitative-report', [\App\Http\Controllers\InsightController::class, 'showQualitativeReport'])->name('surveys.qualitative');
    Route::get('/surveys/{survey}/analyze/{question_id}', [\App\Http\Controllers\InsightController::class, 'analyze'])->name('surveys.analyze');
});
Route::post('/api/agent/chat', [\App\Http\Controllers\AgentController::class, 'chat'])->name('api.agent.chat');
