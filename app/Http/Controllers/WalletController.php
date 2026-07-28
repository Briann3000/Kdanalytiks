<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WalletController extends Controller
{

    /**
     * Display the respondent's wallet and balance.
     */
    public function index(Request $request, \App\Interfaces\PaymentGatewayInterface $gateway)
    {
        $user = auth()->user();
        $wallet = $user->wallet ?: \App\Models\Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        $trackingId = $request->input('tracking_id');
        if ($trackingId) {
            $result = $gateway->checkPaymentStatus($trackingId);
            if ($result['status'] === 'success') {
                $state = strtoupper($result['state']);
                $isComplete = ($state === 'COMPLETE' || $state === 'COMPLETED');
                $apiRef = $result['api_ref'] ?? null;

                $transaction = null;
                if ($apiRef) {
                    $transaction = \App\Models\Transaction::where('reference', $apiRef)->first();
                }
                if (!$transaction) {
                    $transaction = \App\Models\Transaction::where('reference', $trackingId)->first();
                }

                if ($transaction && $transaction->status !== 'completed') {
                    try {
                        \Illuminate\Support\Facades\DB::beginTransaction();

                        if ($isComplete) {
                            $transaction->update([
                                'status' => 'completed',
                                'external_reference' => $trackingId,
                                'description' => $transaction->description . ' (Confirmed on return)'
                            ]);

                            $wallet->increment('balance', $transaction->amount);
                            session()->flash('success', 'Wallet successfully topped up with KES ' . number_format($transaction->amount, 2));
                        } else {
                            $transaction->update([
                                'status' => 'failed',
                                'description' => 'Deposit failed: ' . $state
                            ]);
                            session()->flash('error', 'Payment status: ' . $state);
                        }

                        \Illuminate\Support\Facades\DB::commit();
                        $wallet = $wallet->fresh();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\DB::rollBack();
                        \Log::error('Wallet status check error: ' . $e->getMessage());
                    }
                }
            }
        }

        $transactions = $wallet->transactions()->latest()->take(10)->get();

        return view('wallet.index', compact('wallet', 'transactions'));
    }

    /**
     * Show full transaction history.
     */
    public function history()
    {
        $user = auth()->user();
        $wallet = $user->wallet ?: \App\Models\Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        $transactions = $wallet->transactions()->latest()->paginate(20);

        return view('wallet.history', compact('wallet', 'transactions'));
    }

    /**
     * Process a wallet deposit / top-up request.
     */
    public function deposit(Request $request, \App\Interfaces\PaymentGatewayInterface $gateway)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
            'phone_number' => 'nullable|string|min:10|max:15',
        ]);

        $user = auth()->user();
        $wallet = $user->wallet ?: \App\Models\Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        try {
            $result = $gateway->initiateDeposit($user, (float) $request->amount, $wallet->currency ?? 'KES');

            if ($result['status'] === 'success' && isset($result['checkout_url'])) {
                // Create a pending transaction record
                \App\Models\Transaction::create([
                    'wallet_id' => $wallet->id,
                    'amount' => $request->amount,
                    'type' => 'credit',
                    'status' => 'pending',
                    'reference' => $result['reference'],
                    'description' => 'Wallet deposit'
                ]);

                return redirect($result['checkout_url']);
            }

            throw new \Exception($result['message'] ?? 'Unable to generate checkout URL.');
        } catch (\Exception $e) {
            return back()->with('error', 'Deposit failed: ' . $e->getMessage());
        }
    }

    /**
     * Process a withdrawal request.
     */
    public function withdraw(Request $request, \App\Services\Payments\PaymentManager $paymentManager)
    {
        $minAmount = 50;
        $request->validate([
            'amount' => "required|numeric|min:$minAmount",
            'phone_number' => 'required|string|min:10|max:15',
        ], [
            'amount.min' => "The minimum withdrawal amount is KES $minAmount. Please withdraw $minAmount or more."
        ]);

        $user = auth()->user();

        // Update phone number if it's different or new
        if ($user->phone_number !== $request->phone_number) {
            $user->update(['phone_number' => $request->phone_number]);
        }

        $wallet = $user->wallet;

        if (!$wallet || $wallet->balance < $request->amount) {
            return back()->with('error', "You don't have enough money in your wallet to withdraw this amount.");
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 1. Create Transaction record (pending)
            // We create it first so we have a record of the attempt
            $transaction = \App\Models\Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'type' => 'debit',
                'status' => 'pending',
                'reference' => 'WD-' . strtoupper(\Illuminate\Support\Str::random(10)),
                'description' => 'Withdrawal request'
            ]);

            // 2. Deduct from wallet
            $wallet->decrement('balance', $request->amount);

            // 3. Mark as processing before calling gateway
            $transaction->update(['status' => 'processing']);

            // 4. Trigger Payment Gateway
            $result = $paymentManager->payout($user, (float) $request->amount, $wallet->currency ?? 'KES', $transaction->reference);

            if ($result['status'] === 'success') {
                $transaction->update([
                    'status' => 'completed',
                    'reference' => $result['reference'],
                    'description' => 'Withdrawal completed: ' . ($result['message'] ?? '')
                ]);
                \Illuminate\Support\Facades\DB::commit();
                return back()->with('success', 'Withdrawal successful: ' . $result['message']);
            } else {
                // Gateway returned an error status (not a hard exception)
                throw new \Exception($result['message'] ?? 'Payment gateway refused the request.');
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();

            // Log the error for admin review
            \Log::error('Withdrawal failed', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Withdrawal failed: ' . $e->getMessage());
        }
    }
}
