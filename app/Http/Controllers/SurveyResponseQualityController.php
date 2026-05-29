<?php

namespace App\Http\Controllers;

use App\Models\Response;
use App\Models\Survey;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SurveyResponseQualityController extends Controller
{
    /**
     * Override quality score: manually approve or reject a flagged response.
     */
    public function qualityOverride(Request $request, Survey $survey, Response $response)
    {
        Gate::authorize('update', $survey);

        if ((int) $response->survey_id !== (int) $survey->id) {
            abort(404);
        }

        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject'
        ]);

        $action = $validated['action'];

        try {
            DB::beginTransaction();

            if ($action === 'approve') {
                if ($response->is_flagged) {
                    $response->is_flagged = false;
                    $response->save();

                    // Find and complete the pending transaction for the reward
                    if ($response->respondent_id) {
                        $respondent = $response->respondent;
                        $wallet = $respondent->wallet;

                        if ($wallet) {
                            $transaction = Transaction::where('wallet_id', $wallet->id)
                                ->where('type', 'credit')
                                ->where('status', 'pending')
                                ->where('description', 'like', "%Survey ID: {$survey->id}%")
                                ->first();

                            if ($transaction) {
                                $transaction->update([
                                    'status' => 'completed',
                                    'description' => "Reward for completing Survey ID: {$survey->id} (Approved after review)"
                                ]);
                                $wallet->increment('balance', $transaction->amount);
                            }
                        }
                    }
                }

                DB::commit();
                return back()->with('success', 'Response approved and reward released successfully.');
            } else {
                // Reject response: Delete response/answers and fail pending transaction
                if ($response->respondent_id) {
                    $respondent = $response->respondent;
                    $wallet = $respondent->wallet;

                    if ($wallet) {
                        $transaction = Transaction::where('wallet_id', $wallet->id)
                            ->where('type', 'credit')
                            ->where('status', 'pending')
                            ->where('description', 'like', "%Survey ID: {$survey->id}%")
                            ->first();

                        if ($transaction) {
                            $transaction->update([
                                'status' => 'failed',
                                'description' => "Reward rejected for Survey ID: {$survey->id}"
                            ]);
                        }
                    }
                }

                $response->answers()->delete();
                $response->delete();

                DB::commit();
                return back()->with('success', 'Response rejected and deleted successfully.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Quality Override Error: " . $e->getMessage());
            return back()->with('error', 'An error occurred while performing quality override: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve or reject selected responses.
     */
    public function bulkQualityOverride(Request $request, Survey $survey)
    {
        Gate::authorize('update', $survey);

        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject',
            'response_ids' => 'required|array',
            'response_ids.*' => 'exists:responses,id'
        ]);

        $action = $validated['action'];
        $responseIds = $validated['response_ids'];
        $count = 0;

        try {
            DB::beginTransaction();

            foreach ($responseIds as $id) {
                $response = Response::where('id', $id)->where('survey_id', $survey->id)->first();
                if (!$response)
                    continue;

                if ($action === 'approve') {
                    if ($response->is_flagged) {
                        $response->is_flagged = false;
                        $response->save();

                        if ($response->respondent_id) {
                            $respondent = $response->respondent;
                            $wallet = $respondent->wallet;

                            if ($wallet) {
                                $transaction = Transaction::where('wallet_id', $wallet->id)
                                    ->where('type', 'credit')
                                    ->where('status', 'pending')
                                    ->where('description', 'like', "%Survey ID: {$survey->id}%")
                                    ->first();

                                if ($transaction) {
                                    $transaction->update([
                                        'status' => 'completed',
                                        'description' => "Reward for completing Survey ID: {$survey->id} (Approved after review)"
                                    ]);
                                    $wallet->increment('balance', $transaction->amount);
                                }
                            }
                        }
                        $count++;
                    }
                } else {
                    if ($response->respondent_id) {
                        $respondent = $response->respondent;
                        $wallet = $respondent->wallet;

                        if ($wallet) {
                            $transaction = Transaction::where('wallet_id', $wallet->id)
                                ->where('type', 'credit')
                                ->where('status', 'pending')
                                ->where('description', 'like', "%Survey ID: {$survey->id}%")
                                ->first();

                            if ($transaction) {
                                $transaction->update([
                                    'status' => 'failed',
                                    'description' => "Reward rejected for Survey ID: {$survey->id}"
                                ]);
                            }
                        }
                    }

                    $response->answers()->delete();
                    $response->delete();
                    $count++;
                }
            }

            DB::commit();

            $message = $action === 'approve'
                ? "Successfully approved {$count} response(s) and released rewards."
                : "Successfully rejected and deleted {$count} response(s).";

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Bulk Quality Override Error: " . $e->getMessage());
            return back()->with('error', 'An error occurred while performing bulk override: ' . $e->getMessage());
        }
    }
}
