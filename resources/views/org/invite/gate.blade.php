@extends('layouts.app')

@section('title', 'Accept Invitation to ' . $invitation->organization->name)

@section('content')
    <div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white p-8 rounded-2xl border border-slate-200 shadow-lg space-y-6 text-center">

            <!-- Logo / Icon -->
            <div
                class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl font-bold mx-auto shadow-md">
                @if($invitation->organization->logo_url)
                    <img src="{{ $invitation->organization->logo_url }}" alt="{{ $invitation->organization->name }}"
                        class="w-full h-full object-cover rounded-2xl">
                @else
                    {{ strtoupper(substr($invitation->organization->name, 0, 2)) }}
                @endif
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-extrabold text-slate-900 leading-snug">Join {{ $invitation->organization->name }}
                </h1>
                <p class="text-slate-600 text-sm">
                    <strong>{{ $invitation->invitedBy->name }}</strong> invited you to join as a <span
                        class="font-semibold text-indigo-600 capitalize">{{ str_replace('_', ' ', $invitation->org_workspace_role) }}</span>.
                </p>
            </div>

            @if($invitation->message)
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs italic text-slate-600">
                    "{{ $invitation->message }}"
                </div>
            @endif

            <div class="pt-4 space-y-3">
                @if($existingUser)
                    <div class="p-3.5 rounded-xl bg-indigo-50 text-indigo-900 text-xs font-medium border border-indigo-100">
                        An account registered under <strong>{{ $invitation->email }}</strong> exists. Log in to accept this
                        invitation.
                    </div>
                    <a href="{{ route('organization.login', ['redirect' => route('org.invite.show', $invitation->token)]) }}"
                        class="w-full inline-flex justify-center items-center px-4 py-3 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                        Log In to Accept Invitation
                    </a>
                @else
                    <a href="{{ route('org.invite.register', $invitation->token) }}"
                        class="w-full inline-flex justify-center items-center px-4 py-3 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                        Create Account & Accept
                    </a>
                    <div class="relative py-2">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-200"></div>
                        </div>
                        <div class="relative flex justify-center text-xs text-slate-400 uppercase bg-white px-2">or</div>
                    </div>
                    <a href="{{ route('organization.login', ['redirect' => route('org.invite.show', $invitation->token)]) }}"
                        class="w-full inline-flex justify-center items-center px-4 py-3 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        Log In with Existing Account
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection