@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="{{ route('wallet.index') }}"
                    class="text-sm font-bold text-indigo-600 hover:text-indigo-500 flex items-center gap-2 mb-2">
                    <i class="fa-solid fa-arrow-left"></i> Back to Wallet
                </a>
                <h2 class="text-3xl font-black text-gray-800">Transaction History</h2>
            </div>
            <div class="bg-white px-6 py-3 rounded-2xl border border-gray-100 shadow-sm">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest text-center">Current balance</p>
                <p class="text-xl font-black text-indigo-700">{{ number_format((float) $wallet->balance, 2) }}
                    {{ $wallet->currency ?? 'KES' }}</p>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="table-container">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Date &
                                Reference</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Description
                            </th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">
                                Amount</th>
                            <th
                                class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-5">
                                    <p class="text-sm font-bold text-gray-800">{{ $transaction->created_at->format('M d, Y') }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 font-medium uppercase">{{ $transaction->reference }}</p>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg flex items-center justify-center {{ $transaction->type === 'credit' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }}">
                                            <i
                                                class="fa-solid {{ $transaction->type === 'credit' ? 'fa-plus' : 'fa-minus' }} text-xs"></i>
                                        </div>
                                        <p class="text-sm font-medium text-gray-700">{{ $transaction->description }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <p
                                        class="text-sm font-black {{ $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->type === 'credit' ? '+' : '-' }}
                                        {{ number_format((float) $transaction->amount, 2) }}
                                    </p>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $transaction->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fa-solid fa-folder-open text-gray-200 text-4xl mb-4"></i>
                                        <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">No records found
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection