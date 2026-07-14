<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm(Request $request, $role = null)
    {
        if (!$role) {
            $role = explode('.', $request->route()->getName())[0];
        }
        if ($role === 'researcher') {
            $role = 'independent';
        }
        return view('auth.login', compact('role'));
    }

    public function login(Request $request, $role = null)
    {
        if (!$role) {
            $role = explode('.', $request->route()->getName())[0];
        }
        if ($role === 'researcher') {
            $role = 'independent';
        }
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->where('role', $role)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->status === \App\Enums\UserStatus::Active) {
                Auth::login($user, $request->has('remember'));

                // Handle post-login reward claiming
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
                                        session()->flash('success', 'Your response has been claimed and is pending quality review!');
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
                                        session()->flash('success', 'Your response has been successfully claimed! KES ' . number_format($surveyLocked->reward_per_response, 0) . ' has been added to your wallet.');
                                    }
                                }
                            }
                        }
                    }
                    session()->forget('pending_claim_response_id');
                }

                $redirect = $request->input('redirect') ?: session()->pull('url.intended', route($user->role->value . '.dashboard'));
                return redirect($redirect);
            } else {
                return back()->withErrors(['status' => 'Your account is not active']);
            }
        }

        return back()->withErrors(['credentials' => 'Invalid email or password']);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}