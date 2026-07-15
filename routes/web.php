<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SociusChatController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\InsightController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


// Admin redirect shortcut
Route::get('/admin', function () {
    if (auth()->check() && (auth()->user()->role === 'admin' || (optional(auth()->user()->role)->value === 'admin'))) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('admin.login');
});

// Individual role login routes for redirects

// Public Routes
Route::get('/', function () {
    return view('welcome'); // Landing Page (Always accessible)
})->name('home');

Route::get('/lang/{locale}', [\App\Http\Controllers\LocaleController::class, 'switch'])
    ->name('locale.switch');

Route::get('/privacy-policy', function () {
    return view('privacy');
})->name('privacy');

Route::get('/terms-and-conditions', function () {
    return view('terms');
})->name('terms');

Route::get('/surveys/public', [SurveyController::class, 'publicIndex'])->name('surveys.public');

Route::post('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    return redirect('/');
})->name('logout');

Route::middleware('guest')->group(function () {
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
});

// Email Verification Routes
Route::get('/email/verify', function () {
    if (auth()->user()->hasVerifiedEmail()) {
        $role = auth()->user()->role;
        $roleName = $role instanceof \App\Enums\UserRole ? $role->value : $role;
        return redirect()->route($roleName . '.dashboard');
    }
    return view('auth.verify');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // 1. Verify the URL signature is still valid and not expired
    if (!$request->hasValidSignature()) {
        abort(403, 'This verification link is invalid or has expired.');
    }

    $user = User::findOrFail($id);

    // 2. Security check: Ensure the hash matches the user's email
    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Invalid verification hash.');
    }

    // 3. User Handling: If not logged in as this user, log them in
    if (Auth::id() != $id) {
        Auth::login($user);
    }

    // 4. Mark as verified if needed
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new \Illuminate\Auth\Events\Verified($user));
    }

    $role = $user->role;
    $roleName = $role instanceof \App\Enums\UserRole ? $role->value : $role;

    return redirect()->route($roleName . '.dashboard')->with('success', 'Your email has been successfully verified!');
})->middleware(['signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.resend');

// Shared Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Survey Hub & Core Actions
    Route::get('/surveys/{survey}/responses', [SurveyController::class, 'showResponses'])->name('surveys.responses');
    Route::get('/surveys/{survey_id}/responses/{response_id}', [SurveyController::class, 'showResponseDetail'])->name('surveys.responses.show');
    Route::post('/surveys/{survey}/responses/{response}/transcribe', [SurveyController::class, 'transcribeMedia'])->name('surveys.responses.transcribe');
    Route::get('/surveys/{survey}/export', [SurveyController::class, 'exportResponses'])->name('surveys.export');
    Route::get('/surveys/{survey}/export-xlsx', [SurveyController::class, 'exportXlsx'])->name('surveys.export_xlsx');
    Route::get('/surveys/{survey}/export-json', [SurveyController::class, 'exportJson'])->name('surveys.export_json');
    Route::get('/surveys/{survey}/export-xml', [SurveyController::class, 'exportXml'])->name('surveys.export_xml');
    Route::get('/surveys/{survey}/export-spss', [SurveyController::class, 'exportSpss'])->name('surveys.export_spss');
    Route::get('/surveys/{survey}/export-google-sheets', [SurveyController::class, 'exportGoogleSheets'])->name('surveys.export_google_sheets');
    Route::get('/auth/google', [\App\Http\Controllers\GoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleController::class, 'handleGoogleCallback']);
    Route::get('/surveys/{survey}/export-pdf', [SurveyController::class, 'exportPdf'])->name('surveys.export_pdf');
    Route::get('/surveys/{survey}/export-docx', [SurveyController::class, 'exportDocx'])->name('surveys.export_docx');
    Route::get('/surveys/{survey}/responses/{response}/export-pdf', [SurveyController::class, 'exportSinglePdf'])->name('surveys.responses.export_pdf');
    Route::get('/surveys/{survey}/responses/{response}/export-docx', [SurveyController::class, 'exportSingleDocx'])->name('surveys.responses.export_docx');
    Route::get('/surveys/{survey}/report', [SurveyController::class, 'report'])->name('surveys.report');

    // Core Survey CRUD
    Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');
    Route::middleware(['subscribed:surveys'])->group(function () {
        Route::get('/surveys/create', [SurveyController::class, 'create'])->name('surveys.create');
        Route::post('/surveys', [SurveyController::class, 'store'])->name('surveys.store');
    });
    Route::resource('surveys', SurveyController::class)->except(['index', 'show', 'destroy', 'create', 'store']);

    Route::delete('/surveys/{survey}', [SurveyController::class, 'destroy'])->name('surveys.destroy');
    Route::post('/surveys/bulk-destroy', [SurveyController::class, 'bulkDestroy'])->name('surveys.bulk-destroy');
    Route::post('/surveys/initialize', [SurveyController::class, 'initialize'])->name('surveys.initialize');
    Route::post('/surveys/export-schema-docx', [SurveyController::class, 'exportSchemaDocx'])->name('surveys.export-schema-docx');
    Route::post('/surveys/import-docx', [SurveyController::class, 'importDocx'])->name('surveys.import-docx');

    // Legacy Project Redirects
    Route::redirect('/projects/active', '/surveys?status=active', 301);
    Route::redirect('/projects/archived', '/surveys?status=archived', 301);
    Route::redirect('/projects/drafts', '/surveys?status=draft', 301);
    Route::redirect('/projects', '/surveys', 301);

    // Project Management Hub (Unified under surveys)
    Route::prefix('surveys/{survey}')->name('surveys.')->group(function () {
        Route::get('/summary', [SurveyController::class, 'projectSummary'])->name('summary');
        Route::get('/data', [SurveyController::class, 'showResponses'])->name('data');
        Route::get('/reports', [SurveyController::class, 'report'])->name('reports');
        Route::get('/gallery', [SurveyController::class, 'showGallery'])->name('gallery');
        Route::get('/downloads', [SurveyController::class, 'showDownloads'])->name('downloads');
        Route::get('/downloads/history', [SurveyController::class, 'downloadsHistory'])->name('downloads.history');
        Route::delete('/downloads/{filename}', [SurveyController::class, 'deleteDownload'])->name('downloads.delete');
        Route::get('/settings', [SurveyController::class, 'projectSettings'])->name('settings');
        Route::post('/settings', [SurveyController::class, 'updateProjectSettings'])->name('settings.update');
        Route::get('/branding-logo', [SurveyController::class, 'serveBrandingLogo'])->name('branding.logo');
        Route::post('/collaborators', [SurveyController::class, 'addCollaborator'])->name('collaborators.add');
        Route::delete('/collaborators/{permission}', [SurveyController::class, 'removeCollaborator'])->name('collaborators.remove');
        Route::post('/groups', [SurveyController::class, 'createGroup'])->name('groups.create');
        Route::delete('/groups/{group}', [SurveyController::class, 'deleteGroup'])->name('groups.destroy');
        Route::get('/group-join/{token}', [SurveyController::class, 'joinGroup'])->name('groups.join');
        Route::post('/publish', [SurveyController::class, 'publish'])->name('publish');
        Route::post('/archive', [SurveyController::class, 'archive'])->name('archive');
        Route::post('/toggle-shared-report', [SurveyController::class, 'toggleSharedReport'])->name('reports.toggle-shared');
        Route::get('/crosstab', [SurveyController::class, 'crosstab'])->name('reports.crosstab');
        Route::get('/inferential-analysis', [SurveyController::class, 'inferentialAnalysis'])->name('reports.inferential');

        // Versioning routes
        Route::get('/versions', [\App\Http\Controllers\SurveyVersionController::class, 'index'])->name('versions');
        Route::get('/versions/{version}', [\App\Http\Controllers\SurveyVersionController::class, 'show'])->name('versions.show');
        Route::post('/versions/{version}/restore', [\App\Http\Controllers\SurveyVersionController::class, 'restore'])->name('versions.restore');

        // Quality Override routes
        Route::post('/responses/{response}/quality-override', [\App\Http\Controllers\SurveyResponseQualityController::class, 'qualityOverride'])->name('responses.quality-override');
        Route::post('/responses/bulk-quality-override', [\App\Http\Controllers\SurveyResponseQualityController::class, 'bulkQualityOverride'])->name('responses.bulk-quality-override');

        // Campaign routes
        Route::get('/campaigns', [\App\Http\Controllers\InviteCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [\App\Http\Controllers\InviteCampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [\App\Http\Controllers\InviteCampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign}', [\App\Http\Controllers\InviteCampaignController::class, 'show'])->name('campaigns.show');
        Route::post('/campaigns/{campaign}/remind', [\App\Http\Controllers\InviteCampaignController::class, 'sendReminders'])->name('campaigns.remind');
        Route::post('/campaigns/{campaign}/cancel', [\App\Http\Controllers\InviteCampaignController::class, 'cancel'])->name('campaigns.cancel');

        // Dashboard Builder routes
        Route::middleware(['subscribed:dashboard'])->group(function () {
            Route::get('/dashboard-builder', [\App\Http\Controllers\DashboardBuilderController::class, 'dashboardBuilder'])->name('dashboard-builder');
            Route::post('/dashboard-layout', [\App\Http\Controllers\DashboardBuilderController::class, 'saveDashboardLayout'])->name('dashboard-layout.save');
        });
    });

    Route::middleware(['throttle:60,1'])->prefix('surveys/{survey}/analyse')->name('surveys.analyse.')->group(function () {
        Route::post('/image/generate', [SociusChatController::class, 'generateImage'])->name('image.generate');
        Route::get('/threads', [SociusChatController::class, 'index'])->name('threads.index');
        Route::post('/threads', [SociusChatController::class, 'store'])->name('threads.store');
        Route::get('/threads/{thread}', [SociusChatController::class, 'show'])->name('threads.show');
        Route::post('/threads/{thread}/messages/stream', [SociusChatController::class, 'stream'])->name('threads.stream');
        Route::patch('/threads/{thread}', [SociusChatController::class, 'update'])->name('threads.update');
        Route::post('/threads/{thread}/pin-toggle', [SociusChatController::class, 'togglePin'])->name('threads.pin_toggle');
        Route::get('/threads/{thread}/export', [SociusChatController::class, 'export'])->name('threads.export');
        Route::delete('/threads/{thread}', [SociusChatController::class, 'destroy'])->name('threads.destroy');
    });

    Route::get('/socius/knowledge-base', [\App\Http\Controllers\SociusKnowledgeBaseController::class, 'index'])->name('socius.knowledge-base.index');
    Route::post('/socius/knowledge-base', [\App\Http\Controllers\SociusKnowledgeBaseController::class, 'store'])->name('socius.knowledge-base.store');
    Route::patch('/socius/knowledge-base/{knowledgeBase}', [\App\Http\Controllers\SociusKnowledgeBaseController::class, 'update'])->name('socius.knowledge-base.update');
    Route::delete('/socius/knowledge-base/{knowledgeBase}', [\App\Http\Controllers\SociusKnowledgeBaseController::class, 'destroy'])->name('socius.knowledge-base.destroy');


    Route::prefix('library')->name('library.')->group(function () {
        Route::get('/templates', [SurveyController::class, 'templatesIndex'])->name('templates');
        Route::get('/templates/{survey}/clone', [SurveyController::class, 'cloneTemplate'])->name('templates.clone');
        Route::get('/questions', [SurveyController::class, 'getLibraryQuestions'])->name('questions');
        Route::post('/questions', [SurveyController::class, 'saveToLibrary'])->name('questions.save');
    });

    // Research Proposal Studio
    Route::get('/research-proposal/history', [\App\Http\Controllers\ResearchProposalController::class, 'history'])->name('research-proposal.history');
    Route::post('/research-proposal/store', [\App\Http\Controllers\ResearchProposalController::class, 'storeProposal'])->name('research-proposal.store');
    Route::get('/research-proposal/export-proposal/{id}', [\App\Http\Controllers\ResearchProposalController::class, 'exportProposal'])->name('research-proposal.export-proposal');
    Route::post('/research-proposal/generate', [\App\Http\Controllers\ResearchProposalController::class, 'generate'])->name('research-proposal.generate');
    Route::get('/research-proposal/preview/{reportId}', [\App\Http\Controllers\ResearchProposalController::class, 'preview'])->name('research-proposal.preview');
    Route::post('/research-proposal/translate/{reportId}', [\App\Http\Controllers\ResearchProposalController::class, 'translate'])->name('research-proposal.translate');
    Route::post('/research-proposal/export/{reportId}', [\App\Http\Controllers\ResearchProposalController::class, 'export'])->name('research-proposal.export');

    Route::resource('research-proposal', \App\Http\Controllers\ResearchProposalController::class)->except(['store']);

    // Unified Account Settings
    Route::get('/account/settings', [\App\Http\Controllers\AccountController::class, 'index'])->name('account.settings');
    Route::post('/account/settings/profile', [\App\Http\Controllers\AccountController::class, 'updateProfile'])->name('account.settings.profile');
    Route::post('/account/settings/branding', [\App\Http\Controllers\AccountController::class, 'updateBranding'])->name('account.settings.branding');
});

// Public Survey Views
Route::get('/surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
Route::post('/surveys/{survey}/submit', [SurveyController::class, 'submit'])->middleware('throttle:10,1')->name('surveys.submit');
Route::get('/surveys/{survey}/claim', [SurveyController::class, 'claimRewardPrompt'])->name('surveys.claim');
Route::get('/reports/shared/{token}', [SurveyController::class, 'sharedReport'])->name('surveys.reports.shared');
Route::get('/surveys/{survey}/dashboard-preview', [\App\Http\Controllers\DashboardBuilderController::class, 'dashboardPreview'])->name('surveys.dashboard-preview');

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
    Route::post('/surveys/bulk-destroy', [\App\Http\Controllers\AdminController::class, 'bulkDestroy'])->name('surveys.bulk-destroy');
});

// Organization Routes
Route::middleware(['auth', 'verified', 'role:organization'])->prefix('organization')->name('organization.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/responses', [SurveyController::class, 'responsesIndex'])->name('responses.index');
    Route::get('/reports', [SurveyController::class, 'reportsIndex'])->name('reports.index');
});

// Independent Researcher Routes
Route::middleware(['auth', 'verified', 'role:independent'])->prefix('independent')->name('independent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/responses', [SurveyController::class, 'responsesIndex'])->name('responses.index');
    Route::get('/reports', [SurveyController::class, 'reportsIndex'])->name('reports.index');
});

// Organization, Independent & Respondent Subscription Routes
Route::middleware(['auth', 'verified', 'role:organization,independent,respondent'])->group(function () {
    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('subscriptions.checkout');
    Route::post('/subscriptions/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
});

// Webhook (Public)
Route::post('/webhook/payment', [\App\Http\Controllers\SubscriptionController::class, 'webhook'])->name('webhook.payment');

// Mock Payment Simulator (Development Only)

// Respondent & Wallet Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/wallet', [\App\Http\Controllers\WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/history', [\App\Http\Controllers\WalletController::class, 'history'])->name('wallet.history');
    Route::post('/wallet/withdraw', [\App\Http\Controllers\WalletController::class, 'withdraw'])->name('wallet.withdraw');
});

// Respondent Routes
Route::middleware(['auth', 'verified', 'role:respondent'])->prefix('respondent')->name('respondent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/history', [\App\Http\Controllers\RespondentController::class, 'history'])->name('history');
    Route::get('/reports', [SurveyController::class, 'reportsIndex'])->name('reports.index');
});

// AI Integration Routes
Route::middleware(['auth', 'verified', 'subscribed:ai', 'throttle:5,1'])->group(function () {
    Route::post('/ai/generate-survey', [\App\Http\Controllers\AiController::class, 'generateSchema'])->name('ai.generate');
});

Route::middleware(['auth', 'verified', 'throttle:10,1'])->group(function () {
    Route::get('/ai/insights/question/{questionId}', [InsightController::class, 'generateQuestionInsight'])->name('ai.insights.question');
    Route::get('/ai/insights/quantitative/{questionId}', [InsightController::class, 'generateQuantitativeInsight'])->name('ai.insights.quantitative');
    Route::post('/ai/insights/quantitative/{questionId}/refine', [InsightController::class, 'refineQuantitativeInsight'])->name('ai.insights.quantitative.refine');
    Route::post('/ai/insights/crosstab', [InsightController::class, 'analyzeCrosstab'])->name('ai.insights.crosstab');
    Route::post('/ai/insights/inferential', [InsightController::class, 'analyzeInferential'])->name('ai.insights.inferential');

    // Qualitative Reports
    Route::get('/surveys/{survey}/qualitative-report', [\App\Http\Controllers\InsightController::class, 'showQualitativeReport'])->name('surveys.qualitative');
    Route::get('/surveys/{survey}/analyze/{question_id}', [\App\Http\Controllers\InsightController::class, 'analyze'])->name('surveys.analyze');
});

Route::middleware(['throttle:10,1'])->post('/api/agent/chat', [\App\Http\Controllers\AgentController::class, 'chat'])->name('api.agent.chat');
