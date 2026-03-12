<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SurveyController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

// Shared Authenticated Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/surveys', [SurveyController::class, 'store'])->name('surveys.store');
    Route::get('/surveys/{survey}/responses', [SurveyController::class, 'showResponses'])->name('surveys.responses');
    Route::get('/surveys/{survey}/responses/{response}', [SurveyController::class, 'showResponseDetail'])->name('surveys.responses.show');
    Route::get('/surveys/{survey}/export', [SurveyController::class, 'exportResponses'])->name('surveys.export');
    Route::get('/surveys/{survey}/export-pdf', [SurveyController::class, 'exportPdf'])->name('surveys.export_pdf');
    Route::get('/surveys/{survey}/report', [SurveyController::class, 'report'])->name('surveys.report');
    Route::post('/surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
    Route::post('/surveys/{survey}/invite', [SurveyController::class, 'sendInvitation'])->name('surveys.invite');
    Route::get('/surveys/{survey}/edit', [SurveyController::class, 'edit'])->name('surveys.edit');
    Route::put('/surveys/{survey}', [SurveyController::class, 'update'])->name('surveys.update');
    Route::delete('/surveys/{survey}', [SurveyController::class, 'destroy'])->name('surveys.destroy');
});

// Public Survey Views and Submission
Route::get('/surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
Route::post('/surveys/{survey}/submit', [SurveyController::class, 'submit'])->middleware('throttle:10,1')->name('surveys.submit');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users.index');
    Route::post('/users/{user}/status', [\App\Http\Controllers\AdminController::class, 'updateUserStatus'])->name('users.status');
    Route::get('/surveys', [\App\Http\Controllers\AdminController::class, 'surveys'])->name('surveys.index');
    Route::get('/surveys/create', [SurveyController::class, 'create'])->name('surveys.create');
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
    Route::get('/surveys/create', [SurveyController::class, 'create'])->name('surveys.create');
    Route::get('/responses', [SurveyController::class, 'responsesIndex'])->name('responses.index');
    Route::get('/reports', [SurveyController::class, 'reportsIndex'])->name('reports.index');
});

// Independent Researcher Routes
Route::middleware(['auth', 'role:independent'])->prefix('independent')->name('independent.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');
    Route::get('/surveys/create', [SurveyController::class, 'create'])->name('surveys.create');
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
});
