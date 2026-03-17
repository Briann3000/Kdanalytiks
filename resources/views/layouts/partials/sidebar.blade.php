@php
    $user = auth()->user();
    $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;
@endphp

<div class="space-y-6">
    <div>
        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">
            {{ $role === 'admin' ? 'Administration' : 'Workspace' }}
        </h4>
        <nav class="space-y-1">
            @if($role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.dashboard') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i class="fa-solid fa-server mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    System Overview
                </a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.users.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i class="fa-solid fa-user-gear mr-3 {{ request()->routeIs('admin.users.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    User Directory
                </a>
                <a href="{{ route('admin.surveys.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.surveys.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i class="fa-solid fa-list-check mr-3 {{ request()->routeIs('admin.surveys.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    Survey Inventory
                </a>
                <a href="{{ route('admin.analytics.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.analytics.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i class="fa-solid fa-chart-line mr-3 {{ request()->routeIs('admin.analytics.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    Platform Analytics
                </a>
                <a href="{{ route('research-proposal.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('research-proposal.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i class="fa-solid fa-graduation-cap mr-3 {{ request()->routeIs('research-proposal.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    Research Studio
                </a>
            @else
                <a href="{{ route($role . '.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs($role . '.dashboard') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i class="fa-solid fa-gauge-high mr-3 {{ request()->routeIs($role . '.dashboard') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    Overview
                </a>
                @if(in_array($role, ['organization', 'independent']))
                    <a href="{{ route($role . '.surveys.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs($role . '.surveys.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i class="fa-solid fa-list-check mr-3 {{ request()->routeIs($role . '.surveys.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        My Surveys
                    </a>
                    <a href="{{ route($role . '.responses.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs($role . '.responses.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i class="fa-solid fa-reply-all mr-3 {{ request()->routeIs($role . '.responses.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        Responses
                    </a>
                    <a href="{{ route($role . '.reports.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs($role . '.reports.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i class="fa-solid fa-chart-pie mr-3 {{ request()->routeIs($role . '.reports.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        Reports
                    </a>
                    {{-- Research Studio for non-admins --}}
                    <a href="{{ route('research-proposal.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('research-proposal.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i class="fa-solid fa-graduation-cap mr-3 {{ request()->routeIs('research-proposal.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        Research Studio
                    </a>
                @else
                    <a href="{{ route('respondent.history') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('respondent.history') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i class="fa-solid fa-clock-rotate-left mr-3 {{ request()->routeIs('respondent.history') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        My History
                    </a>
                    <a href="{{ route('research-proposal.index') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('research-proposal.*') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i class="fa-solid fa-graduation-cap mr-3 {{ request()->routeIs('research-proposal.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        Research Studio
                    </a>
                @endif
                <a href="{{ route('surveys.public') }}" class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('surveys.public') ? 'text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i class="fa-solid fa-globe mr-3 {{ request()->routeIs('surveys.public') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    Public Surveys
                </a>
            @endif
        </nav>
    </div>

    @if($role !== 'respondent')
    <div class="pt-6 border-t border-gray-100">
        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Quick Links</h4>
        <div class="space-y-2">
            @if($role === 'admin')
                <a href="{{ route('admin.surveys.create') }}" class="block w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-lg text-center shadow-lg shadow-indigo-100 transition-all">
                    System Survey
                </a>
            @else
                <a href="{{ route($role . '.surveys.create') }}" class="block w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-lg text-center shadow-lg shadow-indigo-100 transition-all">
                    New Survey
                </a>
            @endif
        </div>
    </div>
    @endif
</div>
