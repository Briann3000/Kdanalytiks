<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Organization;
use App\Models\Independent;

use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request, $role = 'respondent')
    {
        $redirect = $request->query('redirect');
        if ($role === 'researcher') {
            $role = 'independent';
        }
        return view('auth.register', compact('role', 'redirect'));
    }

    public function register(Request $request, $role = 'respondent')
    {
        if ($role === 'researcher') {
            $role = 'independent';
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:25',
            'institution' => 'nullable|string|max:255',
            'research_area' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'role' => $role,
            'status' => 'active',
            'locale' => app()->getLocale(),
        ]);

        event(new Registered($user));

        if ($role == 'organization') {
            Organization::create([
                'user_id' => $user->id,
                'name' => $request->organization_name ?? $request->name,
            ]);
        } elseif ($role == 'independent') {
            Independent::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'institution' => $request->institution,
                'research_area' => $request->research_area,
            ]);
        }

        auth()->login($user);

        // Handle post-register reward claiming
        if (session()->has('pending_claim_response_id')) {
            $pendingId = session('pending_claim_response_id');
            $pendingResponse = \App\Models\Response::find($pendingId);
            if ($pendingResponse && !$pendingResponse->respondent_id) {
                $pendingResponse->update(['respondent_id' => $user->id]);
                $survey = $pendingResponse->survey;
                if ($survey && $survey->is_paid && $survey->reward_per_response > 0) {
                    $alreadyRewarded = \App\Models\Transaction::where('wallet_id', $user->wallet?->id)
                        ->where('type', 'credit')
                        ->where('description', 'like', "%Survey ID: {$survey->id}%")
                        ->exists();

                    if (!$alreadyRewarded) {
                        $surveyLocked = \App\Models\Survey::where('id', $survey->id)->lockForUpdate()->first();
                        if ($surveyLocked && ($surveyLocked->current_reward_spent + (float) $surveyLocked->reward_per_response <= (float) $surveyLocked->reward_budget)) {
                            $surveyLocked->increment('current_reward_spent', (float) $surveyLocked->reward_per_response);
                            $wallet = $user->wallet ?: \App\Models\Wallet::create(['user_id' => $user->id, 'balance' => 0]);
                            if ($pendingResponse->is_flagged) {
                                \App\Models\Transaction::create([
                                    'wallet_id' => $wallet->id,
                                    'amount' => (float) $surveyLocked->reward_per_response,
                                    'type' => 'credit',
                                    'status' => 'pending',
                                    'reference' => 'REW-' . strtoupper(\Illuminate\Support\Str::random(10)),
                                    'description' => "Reward pending quality review for Survey ID: {$surveyLocked->id}"
                                ]);
                                session()->flash('success_reward', 'Your response has been claimed and is pending quality review!');
                            } else {
                                $wallet->increment('balance', (float) $surveyLocked->reward_per_response);
                                \App\Models\Transaction::create([
                                    'wallet_id' => $wallet->id,
                                    'amount' => (float) $surveyLocked->reward_per_response,
                                    'type' => 'credit',
                                    'status' => 'completed',
                                    'reference' => 'REW-' . strtoupper(\Illuminate\Support\Str::random(10)),
                                    'description' => "Reward for Survey ID: {$surveyLocked->id}"
                                ]);
                                session()->flash('success_reward', 'Your response has been successfully claimed! KES ' . number_format($surveyLocked->reward_per_response, 0) . ' has been added to your wallet.');
                            }
                        }
                    }
                }
            }
            session()->forget('pending_claim_response_id');
        }

        return redirect()->route('verification.notice')->with('success', 'Registration successful. A verification link has been sent to your email.');
    }
}