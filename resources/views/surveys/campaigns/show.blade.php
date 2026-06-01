@extends('surveys.hub')

@section('survey-content')
    <div class="max-w-6xl space-y-6">
        <!-- Back Link -->
        <div>
            <a href="{{ route('surveys.campaigns.index', $survey) }}"
                class="text-xs font-bold text-gray-600 hover:text-indigo-600 transition-colors">
                <i class="fa-solid fa-arrow-left mr-1"></i> {{ __('Back to Campaigns') }}
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-100 rounded-2xl">
                <p class="text-xs text-green-700 font-bold uppercase tracking-widest">
                    <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                </p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-red-50 border border-red-100 rounded-2xl">
                <p class="text-xs text-red-700 font-bold uppercase tracking-widest">
                    <i class="fa-solid fa-circle-xmark mr-2"></i> {{ session('error') }}
                </p>
            </div>
        @endif

        <!-- Campaign Stats Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Campaign Info -->
            <div
                class="md:col-span-2 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider
                                @if($campaign->status === 'completed') bg-emerald-100 text-emerald-600 border border-emerald-200
                                @elseif($campaign->status === 'scheduled') bg-amber-100 text-amber-600 border border-amber-200
                                @elseif($campaign->status === 'cancelled') bg-rose-100 text-rose-600 border border-rose-200
                                @else bg-indigo-100 text-indigo-600 border border-indigo-200
                                @endif">
                        @if($campaign->status === 'completed')
                            {{ __('Completed') }}
                        @elseif($campaign->status === 'scheduled')
                            {{ __('Scheduled') }}
                        @elseif($campaign->status === 'cancelled')
                            {{ __('Cancelled') }}
                        @elseif($campaign->status === 'sending')
                            {{ __('Sending') }}
                        @elseif($campaign->status === 'draft')
                            {{ __('Draft') }}
                        @else
                            {{ ucfirst($campaign->status) }}
                        @endif
                    </span>
                    <h3 class="text-lg font-black text-gray-900 mt-2 tracking-tight">{{ $campaign->name }}</h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">
                        {{ __('Created on') }} {{ $campaign->created_at->format('F d, Y g:i A') }}
                    </p>
                </div>
                <div
                    class="pt-4 border-t border-gray-50 flex flex-wrap gap-x-6 gap-y-2 mt-4 text-[10px] text-gray-500 font-semibold">
                    @if($campaign->scheduled_at)
                        <div>
                            <span
                                class="block text-gray-400 font-medium uppercase tracking-wider text-[8px]">{{ __('Scheduled') }}</span>
                            <span>{{ $campaign->scheduled_at->format('M d, Y g:i A') }}</span>
                        </div>
                    @endif
                    @if($campaign->completed_at)
                        <div>
                            <span
                                class="block text-gray-400 font-medium uppercase tracking-wider text-[8px]">{{ __('Completed') }}</span>
                            <span>{{ $campaign->completed_at->format('M d, Y g:i A') }}</span>
                        </div>
                    @endif
                    <div>
                        <span
                            class="block text-gray-400 font-medium uppercase tracking-wider text-[8px]">{{ __('Auto Reminders') }}</span>
                        <span>{{ $campaign->auto_reminders ? __('Every :days days', ['days' => $campaign->reminder_interval_days]) : __('Disabled') }}</span>
                    </div>
                </div>
            </div>

            <!-- Response Rate Card -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <div>
                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Response Rate') }}
                    </h4>
                    @php
                        $rate = $campaign->total_recipients > 0 ? round(($campaign->total_responded / $campaign->total_recipients) * 100) : 0;
                    @endphp
                    <div class="text-3xl font-black text-indigo-600 mt-2 tracking-tight">{{ $rate }}%</div>
                </div>
                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden mt-4">
                    <div class="bg-indigo-600 h-full rounded-full" style="width: {{ $rate }}%"></div>
                </div>
            </div>

            <!-- Stats Breakout Card -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-3">
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Campaign Metrics') }}</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Recipients') }}</span>
                        <span class="text-sm font-black text-gray-800">{{ $campaign->total_recipients }}</span>
                    </div>
                    <div>
                        <span
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Sent') }}</span>
                        <span class="text-sm font-black text-gray-800">{{ $campaign->total_sent }}</span>
                    </div>
                    <div>
                        <span
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Responded') }}</span>
                        <span class="text-sm font-black text-gray-800">{{ $campaign->total_responded }}</span>
                    </div>
                    <div>
                        <span
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Opened') }}</span>
                        <span class="text-sm font-black text-gray-800">{{ $campaign->total_opened }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recipients List Card -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ __('Recipients List') }}</h3>
                    <p class="text-xs text-gray-400 font-medium mt-1">
                        {{ __('Monitor delivery and response status for all recipient invitations in this campaign.') }}
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    @if($campaign->status === 'completed' || $campaign->status === 'sending')
                        <form action="{{ route('surveys.campaigns.remind', [$survey, $campaign]) }}" method="POST"
                            onsubmit="return confirm('{{ __('Are you sure you want to send manual reminder emails to all pending non-respondents right now?') }}')">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-100 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">
                                <i class="fa-solid fa-bell mr-2"></i> {{ __('Send Reminders') }}
                            </button>
                        </form>
                    @endif
                    @if(in_array($campaign->status, ['scheduled', 'sending']))
                        <form action="{{ route('surveys.campaigns.cancel', [$survey, $campaign]) }}" method="POST"
                            onsubmit="return confirm('{{ __('Are you sure you want to cancel this campaign?') }}')">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all">
                                <i class="fa-solid fa-ban mr-2"></i> {{ __('Cancel Campaign') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if($recipients->isEmpty())
                <div class="p-16 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 bg-gray-50 text-gray-400 rounded-xl flex items-center justify-center mb-4">
                        <i class="fa-solid fa-users text-lg"></i>
                    </div>
                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">{{ __('No Recipients Found') }}</h4>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-50 bg-gray-50/50">
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Recipient') }}
                                </th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Status') }}
                                </th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Sent At') }}
                                </th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Responded At') }}
                                </th>
                                <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    {{ __('Reminders') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($recipients as $recipient)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6">
                                        @if($recipient->name)
                                            <p class="text-xs font-bold text-gray-900 leading-tight">{{ $recipient->name }}</p>
                                            <p class="text-[10px] text-gray-400 font-semibold mt-0.5">{{ $recipient->email }}</p>
                                        @else
                                            <p class="text-xs font-bold text-gray-900 leading-tight">{{ $recipient->email }}</p>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider
                                                                    @if($recipient->status === 'responded') bg-emerald-100 text-emerald-600 border border-emerald-200
                                                                    @elseif($recipient->status === 'sent') bg-blue-100 text-blue-600 border border-blue-200
                                                                    @elseif($recipient->status === 'opened') bg-indigo-100 text-indigo-600 border border-indigo-200
                                                                    @elseif($recipient->status === 'bounced') bg-rose-100 text-rose-600 border border-rose-200
                                                                    @else bg-gray-100 text-gray-500 border border-gray-200
                                                                    @endif">
                                            @if($recipient->status === 'responded')
                                                {{ __('Responded') }}
                                            @elseif($recipient->status === 'sent')
                                                {{ __('Sent') }}
                                            @elseif($recipient->status === 'opened')
                                                {{ __('Opened') }}
                                            @elseif($recipient->status === 'bounced')
                                                {{ __('Bounced') }}
                                            @else
                                                {{ ucfirst($recipient->status) }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-500 font-semibold">
                                        {{ $recipient->sent_at ? $recipient->sent_at->format('M d, Y g:i A') : __('Not Sent') }}
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-500 font-semibold">
                                        {{ $recipient->responded_at ? $recipient->responded_at->format('M d, Y g:i A') : '-' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-500 font-semibold">
                                        <span>{{ __(':count sent', ['count' => $recipient->reminder_count]) }}</span>
                                        @if($recipient->last_reminder_at)
                                            <span class="block text-[8px] text-gray-400 font-medium mt-0.5">
                                                {{ __('Last: ') }} {{ $recipient->last_reminder_at->format('M d, g:i A') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($recipients->hasPages())
                    <div class="p-6 border-t border-gray-50 bg-gray-50/20">
                        {{ $recipients->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection