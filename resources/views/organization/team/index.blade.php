@extends('layouts.app')

@section('title', 'Team Management - ' . $org->name)

@section('content')
<div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8" x-data="{ showInviteModal: false, editMember: null }">
    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Header Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ __('Team & Workspace Members') }}</h1>
                <p class="text-slate-500 text-sm mt-1">{{ __('Manage seats, roles and permissions for') }} {{ $org->name }}</p>
            </div>
            @if($currentMember && ($currentMember->isOwner() || $currentMember->isAdmin()))
                @if($seatStats['has_reached'])
                    <button type="button" disabled class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-300 text-slate-500 font-semibold text-sm rounded-xl cursor-not-allowed shadow-sm" title="{{ __('Seat Limit Reached — Upgrade Plan') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        {{ __('Seat Limit Reached') }}
                    </button>
                @else
                    <button @click="showInviteModal = true" class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl transition shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        {{ __('Invite Team Member') }}
                    </button>
                @endif
            @endif
        </div>

        @if($seatStats['has_reached'])
            <div class="p-4 rounded-xl bg-rose-50 text-rose-900 border border-rose-200 text-sm flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ __('Workspace seat limit reached') }} ({{ $seatStats['used'] + $seatStats['pending'] }}/{{ $seatStats['max'] }}). {{ __('Upgrade your subscription tier to invite more team members.') }}</span>
                </div>
                <a href="{{ route('organization.dashboard') }}" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs transition shrink-0">
                    {{ __('Upgrade Plan') }}
                </a>
            </div>
        @endif

        <!-- Session Flash Alerts -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('seat_warning'))
            <div class="p-4 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('seat_warning') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <p class="flex items-center gap-1.5"><svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Seat Allocation Summary Widget -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold  tracking-wider text-slate-400">Active Members</span>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $seatStats['used'] }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold  tracking-wider text-slate-400">Pending Invites</span>
                <p class="text-2xl font-bold text-slate-600 mt-1">{{ $seatStats['pending'] }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs font-semibold  tracking-wider text-slate-400">Seat Limit</span>
                <p class="text-2xl font-bold text-slate-600 mt-1">
                    {{ $seatStats['max'] === -1 ? 'Unlimited' : $seatStats['max'] }}
                </p>
            </div>
        </div>

        <!-- Members Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Active Workspace Members ({{ $members->count() }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs font-semibold tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-6">Member</th>
                            <th class="py-3.5 px-6">Role</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6">Joined</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($members as $member)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs">
                                            {{ strtoupper(substr($member->user->name ?? $member->user->email, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900">{{ $member->user->name }}</div>
                                            <div class="text-xs text-slate-500">{{ $member->user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    @php
                                        $badgeStyle = match($member->org_workspace_role) {
                                            'owner' => 'bg-purple-100 text-purple-800 border border-purple-200',
                                            'admin' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                                            'lead_researcher' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                            'analyst' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                            'field_enumerator' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                            'guest_collaborator' => 'bg-slate-100 text-slate-700 border border-slate-300',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize {{ $badgeStyle }}">
                                        {{ str_replace('_', ' ', $member->org_workspace_role) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    @if($member->status === 'active')
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-50 px-2.5 py-1 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Suspended
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-slate-500 text-xs">
                                    {{ $member->joined_at ? $member->joined_at->format('M j, Y') : '-' }}
                                </td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    @if(!$member->isOwner() && ($currentMember->isAdmin() || $currentMember->isOwner()))
                                        <!-- Role Edit Form Inline Dropdown -->
                                        <form action="{{ route('organization.team.members.role', $member->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <select name="org_workspace_role" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="admin" {{ $member->org_workspace_role === 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="lead_researcher" {{ $member->org_workspace_role === 'lead_researcher' ? 'selected' : '' }}>Lead Researcher</option>
                                                <option value="field_enumerator" {{ $member->org_workspace_role === 'field_enumerator' ? 'selected' : '' }}>Field Enumerator</option>
                                                <option value="analyst" {{ $member->org_workspace_role === 'analyst' ? 'selected' : '' }}>Analyst</option>
                                                <option value="guest_collaborator" {{ $member->org_workspace_role === 'guest_collaborator' ? 'selected' : '' }}>Guest Collaborator</option>
                                            </select>
                                        </form>

                                        <!-- Suspend / Reactivate -->
                                        <form action="{{ route('organization.team.members.suspend', $member->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs text-slate-600 hover:text-slate-900 font-medium px-2 py-1 border border-slate-200 rounded-lg hover:bg-slate-100 transition">
                                                {{ $member->status === 'active' ? 'Suspend' : 'Reactivate' }}
                                            </button>
                                        </form>

                                        <!-- Remove -->
                                        <form action="{{ route('organization.team.members.remove', $member->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to remove this member?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium px-2 py-1 border border-rose-200 rounded-lg hover:bg-rose-50 transition">
                                                Remove
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Invitations Section (Admins & Owners Only) -->
        @if($currentMember && ($currentMember->isOwner() || $currentMember->isAdmin()) && $invitations->count() > 0)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900">Pending Invitations ({{ $invitations->count() }})</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs font-semibold  tracking-wider border-b border-slate-100">
                                <th class="py-3.5 px-6">Email</th>
                                <th class="py-3.5 px-6">Invited Role</th>
                                <th class="py-3.5 px-6">Invited By</th>
                                <th class="py-3.5 px-6">Expires</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($invitations as $invitation)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-4 px-6 font-medium text-slate-900">{{ $invitation->email }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800 capitalize">
                                            {{ str_replace('_', ' ', $invitation->org_workspace_role) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-slate-500 text-xs">{{ $invitation->invitedBy->name ?? 'Admin' }}</td>
                                    <td class="py-4 px-6 text-slate-500 text-xs">{{ $invitation->expires_at ? $invitation->expires_at->format('M j, Y') : '-' }}</td>
                                    <td class="py-4 px-6 text-right space-x-2">
                                        <button type="button" 
                                            onclick="navigator.clipboard.writeText('{{ route('org.invite.show', $invitation->token) }}'); alert('Invitation URL copied to clipboard!');" 
                                            class="text-xs text-emerald-700 hover:text-emerald-900 font-medium px-2.5 py-1 border border-emerald-300 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition">
                                            📋 Copy Link
                                        </button>
                                        <form action="{{ route('organization.team.invitations.resend', $invitation->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium px-2.5 py-1 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition">
                                                Resend
                                            </button>
                                        </form>
                                        <form action="{{ route('organization.team.invitations.revoke', $invitation->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Revoke this invitation?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium px-2.5 py-1 border border-rose-200 rounded-lg hover:bg-rose-50 transition">
                                                Revoke
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Invite Member Modal -->
    <div x-show="showInviteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div @click.away="showInviteModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-xl border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <h3 class="text-lg font-bold text-slate-900">Invite New Team Member</h3>
                <button @click="showInviteModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form action="{{ route('organization.team.invite') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold  text-slate-600 mb-1">Email Address</label>
                    <input type="email" name="email" required placeholder="colleague@organization.org" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold  text-slate-600 mb-1">Workspace Role</label>
                    <select name="org_workspace_role" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="lead_researcher">Lead Researcher (Create surveys, view reports)</option>
                        <option value="admin">Admin (Manage team, surveys & settings)</option>
                        <option value="field_enumerator">Field Enumerator (Data collection only)</option>
                        <option value="analyst">Analyst (Read-only reports & Socius AI)</option>
                        <option value="guest_collaborator">Guest Collaborator (Limited access)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold  text-slate-600 mb-1">Personal Message (Optional)</label>
                    <textarea name="message" rows="3" placeholder="Add a welcome message..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" @click="showInviteModal = false" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
