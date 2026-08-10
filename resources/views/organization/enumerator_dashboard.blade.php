@extends('layouts.app')

@section('title', 'Fieldwork Dashboard - ' . $organization->name)

@section('content')
    <div class="min-h-screen bg-slate-50 py-6 px-4 sm:px-6">
        <div class="max-w-2xl mx-auto space-y-6">

            <!-- Mobile Header -->
            <div class="bg-indigo-900 rounded-3xl p-6 text-white shadow-xl space-y-3">
                <div class="flex items-center justify-between">
                    <span class="px-3 py-1 rounded-full bg-white/10 text-indigo-200 text-xs font-semibold">Field
                        Enumerator</span>
                    <span class="text-xs text-indigo-300 font-medium">{{ $organization->name }}</span>
                </div>
                <h1 class="text-2xl font-black">Your Assigned Fieldwork Tasks</h1>
                <p class="text-indigo-200 text-xs leading-relaxed">
                    Collect survey data for your assigned geographic zones. Track your submission quotas in real time.
                </p>
            </div>

            <!-- Assignments List -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider px-1">Assigned Surveys
                    ({{ $assignments->count() }})</h2>

                @forelse($assignments as $a)
                    @php
                        $quota = $a->response_quota;
                        $collected = $a->collected_count;
                        $pct = $quota ? min(100, round(($collected / $quota) * 100)) : 100;
                    @endphp
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">{{ $a->survey->title ?? 'Survey' }}</h3>
                                <span class="text-xs text-slate-400 font-medium">Assigned by
                                    {{ $a->assignedBy->name ?? 'Organization' }}</span>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                {{ $a->zone_label ?: 'All Zones' }}
                            </span>
                        </div>

                        <!-- Quota Progress Bar -->
                        <div class="space-y-1.5 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div class="flex justify-between text-xs font-bold text-slate-700">
                                <span>Submissions Collected: {{ $collected }}</span>
                                <span>Target Quota: {{ $quota ? $quota : 'Unlimited' }}</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                <div class="h-2.5 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-indigo-600' }}"
                                    style="width: {{ $pct }}%"></div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        @if($a->survey)
                            <a href="{{ route('surveys.show', $a->survey->id) }}" target="_blank"
                                class="w-full py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition shadow-sm flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Open Data Collection Form</span>
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="bg-white p-12 rounded-3xl border border-slate-200 text-center space-y-3">
                        <div
                            class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-slate-900 text-base">No Surveys Assigned Yet</h3>
                        <p class="text-xs text-slate-500">Your organization lead or admin will assign field collection tasks to
                            your account here.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection