@extends('layouts.app')

@section('title', 'Select Organization Workspace - KDAnalytiks')

@section('content')
<div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header -->
        <div class="text-center space-y-2">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Organization Workspaces</h1>
            <p class="text-slate-600 text-sm max-w-md mx-auto">Select an organization workspace to access its team surveys, shared Socius AI memory, and fieldwork resources.</p>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Workspaces Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($memberships as $membership)
                @php
                    $org = $membership->organization;
                    $isActive = $activeOrg && $activeOrg->id === $org->id;
                    $roleLabel = str_replace('_', ' ', ucfirst($membership->org_workspace_role));
                @endphp
                <div class="bg-white rounded-2xl p-6 border transition-all duration-200 shadow-sm hover:shadow-md relative flex flex-col justify-between {{ $isActive ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-slate-200 hover:border-slate-300' }}">
                    <div class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-800 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                                    @if($org->logo_url)
                                        <img src="{{ $org->logo_url }}" alt="{{ $org->name }}" class="w-full h-full object-cover rounded-xl">
                                    @else
                                        {{ strtoupper(substr($org->name, 0, 2)) }}
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ $org->name }}</h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 capitalize mt-0.5">
                                        {{ $roleLabel }}
                                    </span>
                                </div>
                            </div>
                            @if($isActive)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @endif
                        </div>

                        <div class="text-xs text-slate-500 space-y-1 pt-2 border-t border-slate-100">
                            <div class="flex justify-between">
                                <span>Surveys:</span>
                                <span class="font-medium text-slate-700">{{ $org->surveys()->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Team Members:</span>
                                <span class="font-medium text-slate-700">{{ $org->activeMembers()->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6">
                        @if($isActive)
                            <a href="{{ route('organization.dashboard') }}" class="w-full inline-flex justify-center items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm">
                                Go to Workspace Dashboard
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @else
                            <form action="{{ route('organization.switcher.activate') }}" method="POST">
                                @csrf
                                <input type="hidden" name="organization_id" value="{{ $org->id }}">
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition-colors">
                                    Switch to this Workspace
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl p-12 text-center border border-slate-200 space-y-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900">No Organization Workspaces Found</h3>
                        <p class="text-sm text-slate-500">You are not currently a member of any organization workspace.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
