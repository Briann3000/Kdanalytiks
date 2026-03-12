<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SurveyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('surveys')->group(function () {
    Route::get('/{survey}', [SurveyController::class, 'show'])->name('api.surveys.show');
    Route::post('/{survey}/submit', [SurveyController::class, 'submit'])->name('api.surveys.submit');
    
    // Protected endpoint for saving/updating the schema
    Route::middleware('auth:sanctum')->post('/{survey}', [SurveyController::class, 'update'])->name('api.surveys.update');
});