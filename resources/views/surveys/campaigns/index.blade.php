@extends('surveys.hub')

@section('survey-content')
    <div class="max-w-6xl">
        <!-- Campaign Header -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden mb-6">
            <div class="p-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Invite Campaigns') }}</h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">
                        {{ __('Manage your survey invitation campaigns, schedule emails, track response rates, and send reminders.') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('surveys.campaigns.create', $survey) }}"
                        class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 transition-all transform hover:-translate-y-1">
                        <i class="fa-solid fa-plus mr-2"></i> {{ __('Create Campaign') }}
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mx-8 mb-8 p-4 bg-green-50 border border-green-100 rounded-2xl">
                    <p class="text-xs text-green-700 font-bold uppercase tracking-widest">
                        <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                    </p>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-8 mb-8 p-4 bg-red-50 border border-red-100 rounded-2xl">
                    <p class="text-xs text-red-700 font-bold uppercase tracking-widest">
                        <i class="fa-solid fa-circle-xmark mr-2"></i> {{ session('error') }}
                    </p>
                </div>
            @endif

            @if($campaigns->isEmpty())
                <div class="p-16 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center mb-4">
                        <i class="fa-solid fa-paper-plane text-2xl"></i>
                    </div>
                    <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ __('No Campaigns Created') }}</h4>
                    <p class="text-xs text-gray-400 font-medium mt-1 max-w-sm">
                        {{ __('Start sending out invitations by creating your first campaign.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-50 bg-gray-50/50">
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Campaign Name') }}</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Status') }}</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Recipients') }}</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Progress & Responses') }}</th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">
                                    {{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($campaigns as $campaign)
                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="py-4 px-6">
                                        <p class="text-xs font-bold text-gray-900 leading-tight">
                                            {{ $campaign->name }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">
                                            {{ __('Created on') }} {{ $campaign->created_at->format('M d, Y') }}
                                        </p>
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($campaign->status === 'draft')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-gray-100 text-gray-500 border border-gray-200">
                                                {{ __('Draft') }}
                                            </span>
                                        @elseif($campaign->status === 'scheduled')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-100 text-amber-600 border border-amber-200">
                                                {{ __('Scheduled') }}
                                            </span>
                                            <span class="block text-[8px] text-gray-400 font-medium mt-1">
                                                {{ $campaign->scheduled_at->format('M d, Y g:i A') }}
                                            </span>
                                        @elseif($campaign->status === 'sending')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-600 border border-indigo-200">
                                                {{ __('Sending') }}
                                            </span>
                                        @elseif($campaign->status === 'completed')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-600 border border-emerald-200">
                                                {{ __('Completed') }}
                                            </span>
                                        @elseif($campaign->status === 'cancelled')
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-100 text-rose-600 border border-rose-200">
                                                {{ __('Cancelled') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 font-bold">
                                        {{ $campaign->recipients_count }}
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="w-48">
                                            <div class="flex items-center justify-between text-[9px] font-bold text-gray-500 mb-1">
                                                <span>{{ __('Responses: :count', ['count' => $campaign->total_responded]) }}
                                                    ({{ $campaign->recipients_count > 0 ? round(($campaign->total_responded / $campaign->recipients_count) * 100) : 0 }}%)</span>
                                            </div>
                                            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                                @php
                                                    $percent = $campaign->recipients_count > 0 ? ($campaign->total_responded / $campaign->recipients_count) * 100 : 0;
                                                @endphp
                                                <div class="bg-indigo-600 h-full rounded-full" style="width: {{ $percent }}%"></div>
                                            </div>
                                            <span class="block text-[8px] text-gray-400 font-medium mt-1">
                                                {{ __('Sent: :sent | Opened: :opened', ['sent' => $campaign->total_sent, 'opened' => $campaign->total_opened]) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('surveys.campaigns.show', [$survey, $campaign]) }}"
                                                class="px-3 py-1.5 bg-white border border-gray-200 text-gray-600 hover:text-indigo-600 hover:border-indigo-100 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors">
                                                <i class="fa-solid fa-eye mr-1"></i> {{ __('View') }}
                                            </a>

                                            @if(in_array($campaign->status, ['scheduled', 'sending']))
                                                <form action="{{ route('surveys.campaigns.cancel', [$survey, $campaign]) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('{{ __('Are you sure you want to cancel this campaign? Pending invitations will not be sent.') }}')">
                                                    @csrf
                                                    <button type="submit"
                                                        class="px-3 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:border-rose-200 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors">
                                                        <i class="fa-solid fa-ban mr-1"></i> {{ __('Cancel') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($campaigns->hasPages())
                    <div class="p-6 border-t border-gray-50 bg-gray-50/20">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection