@extends('layouts.app')

@section('title', 'Workspace Audit Logs - ' . $org->name)

@section('content')
    <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-8">

            <!-- Top Nav Header -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between bg-white p-6 rounded-2xl border border-slate-200 shadow-sm gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ __('Workspace Compliance & Audit Trail') }}</h1>
                    <p class="text-xs text-slate-500">{{ __('Immutable security and operational log for') }}
                        <strong>{{ $org->name }}</strong>.</p>
                </div>
                <a href="{{ route('organization.audit.export') }}"
                    class="px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition shadow-sm inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('Export CSV Log') }}
                </a>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <form action="{{ route('organization.audit.index') }}" method="GET"
                    class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <select name="category" onchange="this.form.submit()"
                        class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 bg-white font-medium text-slate-700">
                        <option value="" {{ request('category') === '' ? 'selected' : '' }}>{{ __('All Activity Types') }}
                        </option>
                        <option value="team" {{ request('category') === 'team' ? 'selected' : '' }}>👥
                            {{ __('Team & Member Management') }}</option>
                        <option value="surveys" {{ request('category') === 'surveys' ? 'selected' : '' }}>📋
                            {{ __('Surveys & Approvals') }}</option>
                        <option value="fieldwork" {{ request('category') === 'fieldwork' ? 'selected' : '' }}>🗺️
                            {{ __('Fieldwork & Quotas') }}</option>
                        <option value="settings" {{ request('category') === 'settings' ? 'selected' : '' }}>⚙️
                            {{ __('Settings & Governance') }}</option>
                    </select>
                    <a href="{{ route('organization.audit.index') }}"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-semibold text-center transition">
                        {{ __('Reset Filters') }}
                    </a>
                </form>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead
                            class="bg-slate-50 text-slate-500 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4">{{ __('Timestamp') }}</th>
                                <th class="px-6 py-4">{{ __('User') }}</th>
                                <th class="px-6 py-4">{{ __('Activity Action') }}</th>
                                <th class="px-6 py-4">{{ __('Details & Metadata') }}</th>
                                <th class="px-6 py-4">{{ __('IP Address') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($logs as $log)
                                @php
                                    $actionClean = ucwords(str_replace(['.', '_'], ' ', $log->action));
                                    $metaText = is_array($log->metadata) ? collect($log->metadata)->map(fn($v, $k) => "$k: " . (is_array($v) ? json_encode($v) : $v))->implode(', ') : $log->metadata;
                                @endphp
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                        {{ $log->created_at ? $log->created_at->format('M j, Y H:i:s') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-slate-900 whitespace-nowrap">
                                        {{ $log->user->name ?? __('System / Automatic') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full font-semibold text-[11px] bg-indigo-50 text-indigo-800 border border-indigo-200">
                                            {{ __($actionClean) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-700 max-w-sm">
                                        <div class="font-medium text-slate-800">
                                            {{ $log->target_type ? "{$log->target_type} #{$log->target_id}" : '' }}</div>
                                        <div class="text-[11px] text-slate-500 truncate mt-0.5" title="{{ $metaText }}">
                                            {{ $metaText ?: '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 font-mono text-[11px]">
                                        {{ $log->ip_address ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-sans">
                                        {{ __('No audit log entries recorded yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection