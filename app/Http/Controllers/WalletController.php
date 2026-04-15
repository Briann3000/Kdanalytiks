<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WalletController extends Controller
{

    /**
     * Display the respondent's wallet and balance.
     */
    public function index()
    {
        $user = auth()->user();
        $wallet = $user->wallet ?: \App\Models\Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
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
     * Process a withdrawal request.
     */
    public function withdraw(Request $request, \App\Services\Payments\PaymentManager $paymentManager)
    {
        $minAmount = (config('app.env') === 'local' || config('app.env') === 'testing' || config('app.debug')) ? 5 : 50;
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
            $result = $paymentManager->payout($user, (float) $request->amount, $wallet->currency ?? 'KES');

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
