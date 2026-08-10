@extends('layouts.app')

@section('title', 'Fieldwork Management - ' . $org->name)

@section('content')
    <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8" x-data="fieldworkManager()">
        <div class="max-w-7xl mx-auto space-y-8">

            <!-- Top Header Bar -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between bg-white p-6 rounded-2xl border border-slate-200 shadow-sm gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ __('Fieldwork & Enumerator Coordination') }}</h1>
                    <p class="text-xs text-slate-500">{{ __('Assign surveys to field enumerators in') }}
                        <strong>{{ $org->name }}</strong>, {{ __('assign geographic zones, and monitor quotas.') }}</p>
                </div>
                <button @click="openAssignModal = true"
                    class="inline-flex items-center px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ __('Assign Survey to Enumerator') }}
                </button>
            </div>

            <!-- Session Messages -->
            @if(session('success'))
                <div
                    class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Active Field Assignments Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">Active Field Assignments ({{ $assignments->count() }})</h2>
                    <span class="text-xs text-slate-500 font-medium">Real-time enumerator submission tracking</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead
                            class="bg-slate-50 text-slate-500 font-semibold text-xs uppercase tracking-wider border-y border-slate-200">
                            <tr>
                                <th class="px-6 py-4">Enumerator</th>
                                <th class="px-6 py-4">Survey</th>
                                <th class="px-6 py-4">Zone / Region</th>
                                <th class="px-6 py-4">Progress / Quota</th>
                                <th class="px-6 py-4">Completion Status</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($assignments as $a)
                                @php
                                    $quota = $a->response_quota;
                                    $collected = $a->collected_count;
                                    $pct = $quota ? min(100, round(($collected / $quota) * 100)) : 100;
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-6 py-4 font-semibold text-slate-900">
                                        <div class="flex items-center space-x-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs">
                                                {{ strtoupper(substr($a->user->name ?? 'E', 0, 2)) }}
                                            </div>
                                            <div>
                                                <div>{{ $a->user->name ?? 'Enumerator' }}</div>
                                                <div class="text-[10px] text-slate-400 font-normal">{{ $a->user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        {{ $a->survey->title ?? 'Deleted Survey' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            {{ $a->zone_label ?: 'All Zones' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-1 w-40">
                                            <div class="flex justify-between text-xs font-bold text-slate-700">
                                                <span>{{ $collected }} collected</span>
                                                <span>{{ $quota ? "/ {$quota}" : '(No Cap)' }}</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-indigo-600' }}"
                                                    style="width: {{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($quota && $collected >= $quota)
                                            <span
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Quota Met
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span> Collecting
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form action="{{ route('organization.fieldwork.unassign', $a->id) }}" method="POST"
                                            class="inline-block" onsubmit="return confirm('Remove assignment?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition"
                                                title="Remove Assignment">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                        No field assignments created yet. Click "Assign Survey to Enumerator" above to get
                                        started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Assign Modal -->
        <div x-show="openAssignModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl space-y-6"
                @click.away="openAssignModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-slate-900">Assign Survey to Enumerator</h3>
                    <button @click="openAssignModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form action="{{ $surveys->first() ? route('organization.fieldwork.assign', $surveys->first()->id) : '#' }}"
                    method="POST" id="assignForm" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Select
                            Survey</label>
                        <select name="survey_id"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            @change="updateFormAction($event.target.value)">
                            @foreach($surveys as $s)
                                <option value="{{ $s->id }}">{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Select Field
                            Enumerator</label>
                        <select name="user_id"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            required>
                            @forelse($enumerators as $e)
                                <option value="{{ $e->user_id }}">{{ $e->user->name }} ({{ $e->user->email }})</option>
                            @empty
                                <option value="" disabled selected>No active field enumerators in workspace</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Target
                                Quota</label>
                            <input type="number" name="response_quota" placeholder="e.g. 50" min="1"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Zone / Area
                                Label</label>
                            <input type="text" name="zone_label" placeholder="e.g. Sector 4B"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="button" @click="openAssignModal = false"
                            class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold">Cancel</button>
                        <button type="submit"
                            class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-md">Assign
                            Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function fieldworkManager() {
            return {
                openAssignModal: false,
                updateFormAction(surveyId) {
                    const form = document.getElementById('assignForm');
                    form.action = "/organization/fieldwork/surveys/" + surveyId + "/assign";
                }
            }
        }
    </script>
@endsection