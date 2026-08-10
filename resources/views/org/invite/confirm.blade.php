@extends('layouts.app')

@section('title', 'Confirm Invitation - ' . $invitation->organization->name)

@section('content')
    <div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white p-8 rounded-2xl border border-slate-200 shadow-lg space-y-6 text-center">

            <!-- Logo / Icon -->
            <div
                class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-800 text-white flex items-center justify-center text-2xl font-bold mx-auto shadow-md">
                @if($invitation->organization->logo_url)
                    <img src="{{ $invitation->organization->logo_url }}" alt="{{ $invitation->organization->name }}"
                        class="w-full h-full object-cover rounded-2xl">
                @else
                    {{ strtoupper(substr($invitation->organization->name, 0, 2)) }}
                @endif
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-extrabold text-slate-900 leading-snug">Accept Invitation</h1>
                <p class="text-slate-600 text-sm">
                    You are currently logged in as <strong>{{ auth()->user()->name }}</strong>
                    ({{ auth()->user()->email }}).
                </p>
            </div>

            <div class="p-4 rounded-xl bg-indigo-50/70 border border-indigo-100 text-xs text-indigo-950 space-y-1">
                <p class="font-semibold">Workspace: {{ $invitation->organization->name }}</p>
                <p>Assigned Role: <span
                        class="capitalize font-bold text-indigo-700">{{ str_replace('_', ' ', $invitation->org_workspace_role) }}</span>
                </p>
                <p>Invited by: {{ $invitation->invitedBy->name }}</p>
            </div>

            <form action="{{ route('org.invite.accept', $invitation->token) }}" method="POST" class="pt-2">
                @csrf
                <button type="submit"
                    class="w-full inline-flex justify-center items-center px-4 py-3 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                    Accept & Join Workspace
                </button>
            </form>
        </div>
    </div>
@endsection