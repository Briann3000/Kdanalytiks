@php
    $user = auth()->user();
    $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;
    $isActive = function ($routes) {
        return is_array($routes)
            ? collect($routes)->some(fn($route) => request()->routeIs($route))
            : request()->routeIs($routes);
    };

    // Determine default expanded section based on active route
    $initialExpanded = null;
    if (request()->routeIs(['projects.index', 'projects.active', 'projects.summary', 'projects.data', 'projects.reports', 'projects.settings', 'surveys.create', 'surveys.edit', 'projects.drafts']))
        $initialExpanded = 'projects';
    if (request()->routeIs(['projects.archived']))
        $initialExpanded = 'library';
    if (request()->routeIs('research-proposal.*'))
        $initialExpanded = 'studio';

    $categories = [
        'academic' => ['icon' => 'fa-graduation-cap', 'label' => 'Academic'],
        'polls' => ['icon' => 'fa-square-poll-vertical', 'label' => 'Polls'],
        'market_research' => ['icon' => 'fa-chart-line', 'label' => 'Market'],
        'feasibility' => ['icon' => 'fa-vial', 'label' => 'Feasibility'],
        'social' => ['icon' => 'fa-people-group', 'label' => 'Social'],
        'business' => ['icon' => 'fa-briefcase', 'label' => 'Business'],
    ];
@endphp

<div class="space-y-6 px-1 h-full" x-data="{ 
    expandedItem: '{{ $initialExpanded }}', 
    hoverItem: null,
    hubExpanded: {{ request()->filled('category') ? 'true' : 'false' }}
}">
    <div>
        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">
            {{ $role === 'admin' ? 'Administration' : 'Workspace' }}
        </h4>
        <nav class="space-y-1">
            @if($role === 'admin')
                <div class="sidebar-item relative" @mouseenter="hoverItem = 'admin'" @mouseleave="hoverItem = null">
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.dashboard') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i
                            class="fa-solid fa-server mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        System Overview
                    </a>
                </div>

                <div class="sidebar-item relative" @mouseenter="hoverItem = 'users'" @mouseleave="hoverItem = null">
                    <div class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.users.*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer"
                        @click="expandedItem = (expandedItem === 'users' ? null : 'users')">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-user-gear mr-3 {{ request()->routeIs('admin.users.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            User Directory
                        </div>
                        <i class="fa-solid fa-angle-down text-[10px] transition-transform"
                            :class="expandedItem === 'users' ? 'rotate-180' : ''"></i>
                    </div>

                    <!-- Flyout -->
                    <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                        x-show="hoverItem === 'users' && expandedItem !== 'users'">
                        <a href="{{ route('admin.users.index') }}"
                            class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">View
                            All Users</a>
                    </div>

                    <!-- Accordion -->
                    <div x-show="expandedItem === 'users'" x-collapse class="sidebar-submenu">
                        <a href="{{ route('admin.users.index') }}"
                            class="block py-1 text-xs font-bold {{ request()->routeIs('admin.users.index') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">List
                            View</a>
                    </div>
                </div>

                <div class="sidebar-item relative" @mouseenter="hoverItem = 'surveys'" @mouseleave="hoverItem = null">
                    <a href="{{ route('admin.surveys.index') }}"
                        class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('admin.surveys.*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i
                            class="fa-solid fa-list-check mr-3 {{ request()->routeIs('admin.surveys.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        Survey Inventory
                    </a>
                </div>
            @else
                <a href="{{ route($role . '.dashboard') }}"
                    class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs($role . '.dashboard') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i
                        class="fa-solid fa-gauge-high mr-3 {{ request()->routeIs($role . '.dashboard') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    Dashboard
                </a>

                @if(in_array($role, ['organization', 'independent']))
                    <!-- Projects Section -->
                    <div class="sidebar-item relative" @mouseenter="hoverItem = 'projects'" @mouseleave="hoverItem = null">
                        <div @click="expandedItem = (expandedItem === 'projects' ? null : 'projects')"
                            class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ (request()->routeIs('projects.active') || request()->routeIs('surveys.create') || request()->routeIs('projects.drafts')) ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                            <div class="flex items-center">
                                <i
                                    class="fa-solid fa-diagram-project mr-3 {{ (request()->routeIs(['projects.index', 'projects.active', 'surveys.create', 'projects.drafts'])) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                                Projects
                            </div>
                            <i class="fa-solid fa-angle-down text-[10px] transition-transform"
                                :class="expandedItem === 'projects' ? 'rotate-180' : ''"></i>
                        </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                            x-show="hoverItem === 'projects' && expandedItem !== 'projects'">
                            <div class="mb-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Project Hub</div>
                                <div class="space-y-1">
                                    @foreach($categories as $key => $cat)
                                        <a href="{{ route('projects.active', ['category' => $key]) }}"
                                            class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-indigo-50/50 rounded-lg transition-colors">
                                            <i class="fa-solid {{ $cat['icon'] }} mr-2 opacity-50 w-4 text-center"></i>
                                            {{ $cat['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-50 pt-3 mt-3">
                                <a href="{{ route('surveys.create') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Create Project</a>
                                <a href="{{ route('projects.active') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">All Projects</a>
                                <a href="{{ route('projects.drafts') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Drafts</a>
                            </div>
                        </div>

                        <div x-show="expandedItem === 'projects'" x-collapse class="sidebar-submenu">
                            <!-- Project Hub Nested -->
                            <div class="mb-1">
                                <div @click="hubExpanded = !hubExpanded" 
                                     class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->routeIs('projects.index') || request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-layer-group mr-2 opacity-50"></i>
                                        Project Hub
                                    </div>
                                    <i class="fa-solid fa-angle-right text-[8px] transition-transform" :class="hubExpanded ? 'rotate-90' : ''"></i>
                                </div>
                                
                                <div x-show="hubExpanded" x-collapse class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                    @foreach($categories as $key => $cat)
                                        <a href="{{ route('projects.active', ['category' => $key]) }}"
                                           class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('category') === $key ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">
                                            {{ $cat['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <a href="{{ route('surveys.create') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('surveys.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Create Project</a>
                            <a href="{{ route('projects.active') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('projects.active') && !request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">All Projects</a>
                            <a href="{{ route('projects.drafts') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('projects.drafts') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Drafts</a>
                        </div>
                    </div>

                    <!-- Library Section -->
                    <div class="sidebar-item relative" @mouseenter="hoverItem = 'library'" @mouseleave="hoverItem = null">
                        <div @click="expandedItem = (expandedItem === 'library' ? null : 'library')"
                            class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ request()->routeIs('projects.archived') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                            <div class="flex items-center">
                                <i
                                    class="fa-solid fa-book-bookmark mr-3 {{ request()->routeIs('projects.archived') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                                Library
                            </div>
                            <i class="fa-solid fa-angle-down text-[10px] transition-transform"
                                :class="expandedItem === 'library' ? 'rotate-180' : ''"></i>
                        </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                            x-show="hoverItem === 'library' && expandedItem !== 'library'">
                            <a href="{{ route('projects.archived') }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Archived Projects</a>
                        </div>

                        <div x-show="expandedItem === 'library'" x-collapse class="sidebar-submenu">
                            <a href="{{ route('projects.archived') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('projects.archived') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Archived Projects</a>
                        </div>
                    </div>
                @endif
            @endif

            <div class="sidebar-item relative" @mouseenter="hoverItem = 'studio'" @mouseleave="hoverItem = null">
                <div @click="expandedItem = (expandedItem === 'studio' ? null : 'studio')"
                    class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ request()->routeIs('research-proposal.*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                    <div class="flex items-center">
                        <i
                            class="fa-solid fa-graduation-cap mr-3 {{ request()->routeIs('research-proposal.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        Write Report
                    </div>
                </div>

                <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                    x-show="hoverItem === 'studio' && expandedItem !== 'studio'">
                    <a href="{{ route('research-proposal.index') }}"
                        class="block px-3 py-1.5 text-xs font-bold font-bold {{ request()->routeIs('research-proposal.index') ? 'text-indigo-700 bg-gray-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg">Report
                        Generator</a>
                    <a href="{{ route('research-proposal.create') }}"
                        class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('research-proposal.create') ? 'text-indigo-700 bg-gray-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg">Draft
                        Reports</a>
                    <a href="{{ route('research-proposal.history') }}"
                        class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('research-proposal.history') ? 'text-indigo-700 bg-gray-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg">Reports
                        History</a>
                </div>

                <div x-show="expandedItem === 'studio'" x-collapse class="sidebar-submenu">
                    <a href="{{ route('research-proposal.index') }}"
                        class="block py-1 text-xs font-bold {{ request()->routeIs('research-proposal.index') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Report
                        Generator</a>
                    <a href="{{ route('research-proposal.create') }}"
                        class="block py-1 text-xs font-bold {{ request()->routeIs('research-proposal.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Draft
                        Reports</a>
                    <a href="{{ route('research-proposal.history') }}"
                        class="block py-1 text-xs font-bold {{ request()->routeIs('research-proposal.history') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Reports
                        History</a>
                </div>
            </div>

            <a href="{{ route('surveys.public') }}"
                class="flex items-center px-3 py-2 text-sm font-bold {{ request()->routeIs('surveys.public') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                <i
                    class="fa-solid fa-globe mr-3 {{ request()->routeIs('surveys.public') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                Social Research
            </a>

            <!-- Quick Create Button -->
            <div class="pt-4 mt-4 border-t border-gray-100 px-3">
                <a href="{{ route('surveys.create') }}" 
                   class="flex items-center justify-center w-full px-4 py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 hover:shadow-indigo-200 transition-all group">
                    <i class="fa-solid fa-plus-circle mr-2 group-hover:rotate-90 transition-transform"></i>
                    Create Project
                </a>
            </div>
        </nav>
    </div>
</div>