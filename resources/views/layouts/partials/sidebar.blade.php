@php
    $user = auth()->user();
    $role = $user ? ($user->role instanceof \BackedEnum ? $user->role->value : $user->role) : 'guest';
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
    if (request()->routeIs('surveys.public'))
        $initialExpanded = 'public';

    $categories = [
        'academic' => ['icon' => 'fa-graduation-cap', 'label' => 'Academic'],
        'baseline' => ['icon' => 'fa-clipboard-list', 'label' => 'Baseline'],
        'feasibility' => ['icon' => 'fa-vial', 'label' => 'Feasibility'],
        'market_research' => ['icon' => 'fa-chart-line', 'label' => 'Market Research'],
        'others' => ['icon' => 'fa-folder-open', 'label' => 'Others'],
        'polls' => ['icon' => 'fa-square-poll-vertical', 'label' => 'Polls'],
    ];
@endphp

<div class="space-y-6 px-1 h-full" x-data="{ 
    expandedItem: '{{ $initialExpanded }}', 
    hoverItem: null,
    hubExpanded: {{ request()->filled('category') && (request()->routeIs('projects.*') || request()->routeIs('surveys.create')) ? 'true' : 'false' }},
    publicExpanded: {{ request()->routeIs('surveys.public') && request()->filled('category') ? 'true' : 'false' }}
}">
    <div>
        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">
            {{ $role === 'admin' ? 'Administration' : 'Workspace' }}
        </h4>
        <nav class="space-y-1">
            @if($role === 'admin')
                <div class="sidebar-item relative" @mouseenter="hoverItem = 'dashboard'" @mouseleave="hoverItem = null">
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->routeIs('admin.dashboard') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i
                            class="fa-solid fa-server mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        DASHBOARD
                    </a>
                </div>

                <!-- MANAGE USERS -->
                <div class="sidebar-item relative" @mouseenter="hoverItem = 'users'" @mouseleave="hoverItem = null">
                    <div class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->is('admin/users*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer"
                        @click="expandedItem = (expandedItem === 'users' ? null : 'users')">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-user-gear mr-3 {{ request()->is('admin/users*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            MANAGE USERS
                        </div>
                    </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                            x-show="hoverItem === 'users' && expandedItem !== 'users'">
                            <div class="mb-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Quick Actions</div>
                                <a href="{{ route('admin.users.index') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">All Users</a>
                                <a href="{{ route('admin.users.create') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Create User</a>
                            </div>
                            <div class="border-t border-gray-50 pt-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Filter Roles</div>
                                <div class="space-y-1">
                                    <a href="{{ route('admin.users.index', ['role' => 'independent']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">INDEPENDENT</a>
                                    <a href="{{ route('admin.users.index', ['role' => 'organization']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">ORGANIZATION</a>
                                    <a href="{{ route('admin.users.index', ['role' => 'respondent']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">RESPONDENT</a>
                                </div>
                            </div>
                        </div>

                    <!-- Accordion -->
                    <div x-show="expandedItem === 'users'" x-collapse class="sidebar-submenu" x-data="{ allUsersExpanded: false }">
                        <div class="mb-1">
                            <div @click="allUsersExpanded = !allUsersExpanded"
                                class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->routeIs('admin.users.index') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-users mr-2 opacity-50"></i>
                                    ALL USERS
                                </div>
                            </div>

                            <div x-show="allUsersExpanded" x-collapse
                                class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                <a href="{{ route('admin.users.index', ['role' => 'independent']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('role') === 'independent' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">INDEPENDENT</a>
                                <a href="{{ route('admin.users.index', ['role' => 'organization']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('role') === 'organization' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">ORGANIZATION</a>
                                <a href="{{ route('admin.users.index', ['role' => 'respondent']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('role') === 'respondent' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">RESPONDENT</a>
                            </div>
                        </div>

                        <a href="{{ route('admin.users.create') }}"
                            class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('admin.users.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">CREATE USER</a>
                    </div>
                </div>

                <!-- MANAGE SURVEYS -->
                <div class="sidebar-item relative" @mouseenter="hoverItem = 'surveys'" @mouseleave="hoverItem = null">
                    <div class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->is('admin/surveys*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer"
                        @click="expandedItem = (expandedItem === 'surveys' ? null : 'surveys')">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-list-check mr-3 {{ request()->is('admin/surveys*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            MANAGE SURVEYS
                        </div>
                    </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                            x-show="hoverItem === 'surveys' && expandedItem !== 'surveys'">
                            <div class="mb-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Inventory</div>
                                <a href="{{ route('admin.surveys.index') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">All Surveys</a>
                                <a href="{{ route('admin.surveys.index', ['status' => 'active']) }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Active Surveys</a>
                                <a href="{{ route('admin.surveys.index', ['status' => 'draft']) }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Drafts</a>
                                <a href="{{ route('surveys.create') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Create Survey</a>
                            </div>
                            <div class="border-t border-gray-50 pt-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Source</div>
                                <div class="space-y-1">
                                    <a href="{{ route('admin.surveys.index', ['source' => 'admin']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">ADMIN</a>
                                    <a href="{{ route('admin.surveys.index', ['source' => 'independent']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">INDEPENDENT</a>
                                    <a href="{{ route('admin.surveys.index', ['source' => 'organization']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">ORGANIZATION</a>
                                </div>
                            </div>
                        </div>

                    <!-- Accordion -->
                    <div x-show="expandedItem === 'surveys'" x-collapse class="sidebar-submenu" x-data="{ allSurveysExpanded: false }">
                        <div class="mb-1">
                            <div @click="allSurveysExpanded = !allSurveysExpanded"
                                class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->routeIs('admin.surveys.index') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-warehouse mr-2 opacity-50"></i>
                                    ALL SURVEYS
                                </div>
                            </div>

                            <div x-show="allSurveysExpanded" x-collapse
                                class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                <a href="{{ route('admin.surveys.index', ['source' => 'admin']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'admin' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">ADMIN</a>
                                <a href="{{ route('admin.surveys.index', ['source' => 'independent']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'independent' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">INDEPENDENT</a>
                                <a href="{{ route('admin.surveys.index', ['source' => 'organization']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'organization' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">ORGANIZATION</a>
                            </div>
                        </div>

                        <a href="{{ route('admin.surveys.index', ['status' => 'draft']) }}"
                            class="block py-1 text-xs font-bold uppercase tracking-wide {{ request('status') === 'draft' ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} mt-1">DRAFTS</a>

                        <a href="{{ route('surveys.create') }}"
                            class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('surveys.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">CREATE SURVEY</a>
                    </div>
                </div>

                <!-- Admin Library -->
                <div class="sidebar-item relative" @mouseenter="hoverItem = 'library'" @mouseleave="hoverItem = null">
                    <div @click="expandedItem = (expandedItem === 'library' ? null : 'library')"
                        class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->routeIs('projects.archived') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-book-bookmark mr-3 {{ request()->routeIs('projects.archived') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            LIBRARY
                        </div>
                    </div>

                    <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                        x-show="hoverItem === 'library' && expandedItem !== 'library'">
                        <a href="{{ route('projects.archived') }}"
                            class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Archived
                            Surveys</a>
                    </div>

                    <div x-show="expandedItem === 'library'" x-collapse class="sidebar-submenu">
                        <a href="{{ route('projects.archived') }}"
                            class="block py-1 text-xs font-bold {{ request()->routeIs('projects.archived') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Archived
                            Surveys</a>
                        <a href="{{ route('library.templates') }}"
                            class="block py-1 text-xs font-bold {{ request()->routeIs('library.templates') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Templates</a>
                    </div>
                </div>
            @else
                <a href="{{ $role === 'guest' ? route('home') : route($role . '.dashboard') }}"
                    class="flex items-center px-3 py-2 text-sm uppercase font-bold {{ ($role !== 'guest' && request()->routeIs($role . '.dashboard')) ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i
                        class="fa-solid fa-gauge-high mr-3 {{ ($role !== 'guest' && request()->routeIs($role . '.dashboard')) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    {{ $role === 'guest' ? 'HOME' : 'DASHBOARD' }}
                </a>

                @if(in_array($role, ['organization', 'independent']))
                    <!-- Projects Section -->
                    <div class="sidebar-item relative" @mouseenter="hoverItem = 'projects'" @mouseleave="hoverItem = null">
                        <div @click="expandedItem = (expandedItem === 'projects' ? null : 'projects')"
                            class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ (request()->routeIs('projects.active') || request()->routeIs('surveys.create') || request()->routeIs('projects.drafts')) ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                            <div class="flex items-center">
                                <i
                                    class="fa-solid fa-diagram-project mr-3 {{ (request()->routeIs(['projects.index', 'projects.active', 'surveys.create', 'projects.drafts'])) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                                MANAGE SURVEYS
                            </div>
                        </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                            x-show="hoverItem === 'projects' && expandedItem !== 'projects'">
                            <div class="mb-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Survey Hub
                                </div>
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
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Manage</div>
                                <a href="{{ route('projects.active') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Active Surveys</a>
                                <a href="{{ route('projects.drafts') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Drafts</a>
                                <a href="{{ route('surveys.create') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Create
                                    Survey</a>
                            </div>
                        </div>

                        <div x-show="expandedItem === 'projects'" x-collapse class="sidebar-submenu" x-data="{ manageExpanded: {{ request()->routeIs(['projects.active', 'projects.drafts']) && !request()->filled('category') ? 'true' : 'false' }} }">
                            <!-- Survey Hub Nested -->
                            <div class="mb-1">
                                <div @click="hubExpanded = !hubExpanded"
                                    class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->routeIs('projects.index') || request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-layer-group mr-2 opacity-50"></i>
                                        SURVEY HUB
                                    </div>
                                </div>

                                <div x-show="hubExpanded" x-collapse
                                    class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                    @foreach($categories as $key => $cat)
                                        <a href="{{ route('projects.active', ['category' => $key]) }}"
                                            class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('category') === $key ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">
                                            {{ $cat['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-1">
                                <div @click="manageExpanded = !manageExpanded"
                                    class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->routeIs(['projects.active', 'projects.drafts']) && !request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-list-check mr-2 opacity-50"></i>
                                        MANAGE SURVEYS
                                    </div>
                                </div>

                                <div x-show="manageExpanded" x-collapse
                                    class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                    <a href="{{ route('projects.active') }}"
                                        class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request()->routeIs('projects.active') && !request()->filled('category') ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">
                                        Active Surveys
                                    </a>
                                    <a href="{{ route('projects.drafts') }}"
                                        class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request()->routeIs('projects.drafts') ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">
                                        Drafts
                                    </a>
                                </div>
                            </div>

                            <a href="{{ route('surveys.create') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('surveys.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Create
                                Survey</a>
                        </div>
                    </div>

                    <!-- Library Section -->
                    <div class="sidebar-item relative" @mouseenter="hoverItem = 'library'" @mouseleave="hoverItem = null">
                        <div @click="expandedItem = (expandedItem === 'library' ? null : 'library')"
                            class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ request()->routeIs('projects.archived') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                            <div class="flex items-center">
                                <i
                                    class="fa-solid fa-book-bookmark mr-3 {{ request()->routeIs('projects.archived') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                                LIBRARY
                            </div>
                        </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                            x-show="hoverItem === 'library' && expandedItem !== 'library'">
                            <a href="{{ route('projects.archived') }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Archived
                                Projects</a>
                        </div>

                        <div x-show="expandedItem === 'library'" x-collapse class="sidebar-submenu">
                            <a href="{{ route('projects.archived') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('projects.archived') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Archived
                                Projects</a>
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
                        WRITE REPORT
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

            <!-- Public Surveys Section -->
            <div class="sidebar-item relative" @mouseenter="hoverItem = 'public'" @mouseleave="hoverItem = null">
                <div @click="expandedItem = (expandedItem === 'public' ? null : 'public')"
                    class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ request()->routeIs('surveys.public') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                    <div class="flex items-center">
                        <i
                            class="fa-solid fa-globe mr-3 {{ request()->routeIs('surveys.public') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        TAKE A SURVEY
                    </div>
                </div>

                <!-- Flyout -->
                <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                    x-show="hoverItem === 'public' && expandedItem !== 'public'">
                    <div class="mb-3">
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Browse
                            Categories</div>
                        <div class="space-y-1">
                            @foreach($categories as $key => $cat)
                                <a href="{{ route('surveys.public', ['category' => $key]) }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-indigo-50/50 rounded-lg transition-colors">
                                    <i class="fa-solid {{ $cat['icon'] }} mr-2 opacity-50 w-4 text-center"></i>
                                    {{ $cat['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="border-t border-gray-50 pt-3 mt-3">
                        <a href="{{ route('surveys.public') }}"
                            class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">View
                            All Public</a>
                    </div>
                </div>

                <!-- Accordion -->
                <div x-show="expandedItem === 'public'" x-collapse class="sidebar-submenu">
                    <div class="mb-1">
                        <div @click="publicExpanded = !publicExpanded"
                            class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->routeIs('surveys.public') && request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                            <div class="flex items-center">
                                <i class="fa-solid fa-layer-group mr-2 opacity-50"></i>
                                CATEGORIES
                            </div>
                        </div>

                        <div x-show="publicExpanded" x-collapse
                            class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                            @foreach($categories as $key => $cat)
                                <a href="{{ route('surveys.public', ['category' => $key]) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request()->is('surveys/public*') && request('category') === $key ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">
                                    {{ $cat['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ route('surveys.public') }}"
                        class="block py-1 text-xs font-bold {{ request()->routeIs('surveys.public') && !request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">All
                        Public Surveys</a>
                </div>
            </div>

            @if($role !== 'respondent')
                <div class="pt-6 border-t border-gray-100 px-3">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Quick Links</h4>
                    <div class="space-y-2">
                        @if($role === 'admin')
                            <a href="{{ route('surveys.create') }}"
                                class="block w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-lg text-center shadow-lg shadow-indigo-100 transition-all">
                                Create Survey
                            </a>
                        @else
                            <a href="{{ route('surveys.create') }}"
                                class="block w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-lg text-center shadow-lg shadow-indigo-100 transition-all">
                                Create Survey
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </nav>
    </div>
</div>