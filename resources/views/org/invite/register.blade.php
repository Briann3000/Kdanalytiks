@extends('layouts.app')

@section('title', 'Create Account & Join ' . $invitation->organization->name)

@section('content')
    <div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white p-8 rounded-2xl border border-slate-200 shadow-lg space-y-6">

            <div class="text-center space-y-2">
                <h1 class="text-2xl font-extrabold text-slate-900 leading-snug">Create Your Account</h1>
                <p class="text-slate-600 text-sm">
                    Join <strong>{{ $invitation->organization->name }}</strong> as a <span
                        class="capitalize font-semibold text-indigo-600">{{ str_replace('_', ' ', $invitation->org_workspace_role) }}</span>.
                </p>
            </div>

            @if($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5"><svg class="w-4 h-4 text-rose-600 shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('org.invite.register.store', $invitation->token) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Email Address</label>
                    <input type="email" value="{{ $invitation->email }}" disabled
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-600 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Full Name</label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="John Doe"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Phone Number (Optional)</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="+254 700 000000"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Minimum 8 characters"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required placeholder="Re-enter password"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full inline-flex justify-center items-center px-4 py-3 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                        Create Account & Join Workspace
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection