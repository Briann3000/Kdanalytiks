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
    mobileMenuOpen: false,
    hubExpanded: {{ request()->filled('category') && (request()->routeIs('projects.*') || request()->routeIs('surveys.create')) ? 'true' : 'false' }},
    publicExpanded: {{ request()->routeIs('surveys.public') && request()->filled('category') ? 'true' : 'false' }},
    flyoutTop: 0,
    flyoutLeft: 0,
    setFlyout(e, type) {
        if(!e) return;
        this.hoverItem = type;
        const r = e.getBoundingClientRect();
        this.flyoutTop = r.top;
        this.flyoutLeft = r.right;
    },
    clearFlyout() {
        this.hoverItem = null;
    }
}">
    <div>
        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">
            {{ $role === 'admin' ? 'ADMINISTRATION' : ($role === 'guest' ? 'NAVIGATION' : 'WORKSPACE') }}
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
                <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'users')" @mouseleave="clearFlyout()">
                    <div class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->is('admin/users*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer"
                        @click="expandedItem = (expandedItem === 'users' ? null : 'users')">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-user-gear mr-3 {{ request()->is('admin/users*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            MANAGE USERS
                        </div>
                    </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                             x-show="hoverItem === 'users' && expandedItem !== 'users'" 
                             :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" 
                             style="display: none;"
                             @mouseenter="hoverItem = 'users'"
                             @mouseleave="clearFlyout()">
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
                    <div x-show="expandedItem === 'users'" x-collapse class="sidebar-submenu" x-data="{ allUsersExpanded: {{ request()->has('role') ? 'true' : 'false' }} }">
                        <div class="mb-1">
                            <div @click="allUsersExpanded = !allUsersExpanded" x-data="{ nestSub: false, nestTop: 0, nestLeft: 0 }" @mouseenter="const r = $el.getBoundingClientRect(); nestTop = r.top; nestLeft = r.right + 15; nestSub = true" @mouseleave="nestSub = false"
                                class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->routeIs('admin.users.index') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors relative">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-users mr-2 opacity-50"></i>
                                    ALL USERS
                                </div>
                                
                                <!-- Submenu Flyout for Roles -->
                                <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]" style="border-radius: 0.75rem; display: none;" x-show="nestSub" :style="{ top: nestTop + 'px', left: nestLeft + 'px' }">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">Filter Roles</div>
                                    <div class="space-y-1">
                                        <a href="{{ route('admin.users.index', ['role' => 'independent']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">INDEPENDENT</a>
                                        <a href="{{ route('admin.users.index', ['role' => 'organization']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">ORGANIZATION</a>
                                        <a href="{{ route('admin.users.index', ['role' => 'respondent']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">RESPONDENT</a>
                                    </div>
                                </div>
                            </div>

                            <div x-show="allUsersExpanded" x-collapse
                                class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1 font-bold">
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
                <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'surveys')" @mouseleave="clearFlyout()">
                    <div class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->is('admin/surveys*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer"
                        @click="expandedItem = (expandedItem === 'surveys' ? null : 'surveys')">
                        <div class="flex items-center">
                            <i class="fa-solid fa-list-check mr-3 {{ request()->is('admin/surveys*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            MANAGE SURVEYS
                        </div>
                    </div>

                    <!-- Flyout for Manage Surveys -->
                    <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                        x-show="hoverItem === 'surveys' && expandedItem !== 'surveys'" 
                        :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" 
                        style="display: none;"
                        @mouseenter="hoverItem = 'surveys'"
                        @mouseleave="clearFlyout()">
                        <div class="mb-3">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Inventory</div>
                            <div class="relative" x-data="{ n1: false, n1Top: 0, n1Left: 0 }" @mouseenter="const r = $el.getBoundingClientRect(); n1Top = r.top; n1Left = r.right + 15; n1 = true" @mouseleave="n1 = false">
                                <a href="{{ route('admin.surveys.index') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">All Surveys</a>
                                <!-- Nested Flyout -->
                                <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]" style="border-radius: 0.75rem; display: none;" x-show="n1" :style="{ top: n1Top + 'px', left: n1Left + 'px' }">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">By Role</div>
                                    <div class="space-y-1">
                                        <a href="{{ route('admin.surveys.index', ['source' => 'admin']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">ADMIN</a>
                                        <a href="{{ route('admin.surveys.index', ['source' => 'organization']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">ORGANIZATION</a>
                                        <a href="{{ route('admin.surveys.index', ['source' => 'independent']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">INDEPENDENT</a>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('admin.surveys.index', ['status' => 'active']) }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Active Surveys</a>
                            {{-- Removed Pending Approval --}}
                            <a href="{{ route('admin.surveys.index', ['status' => 'draft']) }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Drafts</a>
                        </div>
                        <div class="border-t border-gray-50 pt-3">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">Actions</div>
                            <div class="relative" x-data="{ n2: false, n2Top: 0, n2Left: 0 }" @mouseenter="const r = $el.getBoundingClientRect(); n2Top = r.top; n2Left = r.right + 15; n2 = true" @mouseleave="n2 = false">
                                <a href="{{ route('surveys.create') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Create Survey</a>
                                <!-- Nested Flyout -->
                                <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]" style="border-radius: 0.75rem; display: none;" x-show="n2" :style="{ top: n2Top + 'px', left: n2Left + 'px' }">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">Method</div>
                                    <div class="space-y-1">
                                        <a href="{{ route('surveys.create') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">BLANK</a>
                                        <a href="{{ route('library.templates') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">TEMPLATE</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion -->
                    <div x-show="expandedItem === 'surveys'" x-collapse class="sidebar-submenu" x-data="{ roleFiltersExpanded: {{ request()->has('source') ? 'true' : 'false' }} }">
                        <div class="relative" x-data="{ n3: false, n3Top: 0, n3Left: 0 }" @mouseenter="const r = $el.getBoundingClientRect(); n3Top = r.top - 8; n3Left = r.right + 15; n3 = true" @mouseleave="n3 = false">
                            <a href="{{ route('admin.surveys.index') }}"
                                class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('admin.surveys.index') && !request()->hasAny(['status', 'source']) ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">ALL SURVEYS</a>
                            <!-- Nested Flyout -->
                            <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]" style="border-radius: 0.75rem; display: none;" x-show="n3" :style="{ top: n3Top + 'px', left: n3Left + 'px' }">
                                <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">By Role</div>
                                <div class="space-y-1">
                                    <a href="{{ route('admin.surveys.index', ['source' => 'admin']) }}"
                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Admin</a>
                                    <a href="{{ route('admin.surveys.index', ['source' => 'organization']) }}"
                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Org</a>
                                    <a href="{{ route('admin.surveys.index', ['source' => 'independent']) }}"
                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Independent</a>
                                </div>
                            </div>
                        </div>
                            
                        <a href="{{ route('admin.surveys.index', ['status' => 'active']) }}"
                            class="block py-1 text-xs font-bold uppercase tracking-wide {{ request('status') === 'active' ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} mt-1">ACTIVE</a>

                        {{-- Removed PENDING --}}

                        <a href="{{ route('admin.surveys.index', ['status' => 'draft']) }}"
                            class="block py-1 text-xs font-bold uppercase tracking-wide {{ request('status') === 'draft' ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} mt-1">DRAFTS</a>

                        <!-- Filters Section -->
                        <div class="mb-1 mt-1">
                            <div @click="roleFiltersExpanded = !roleFiltersExpanded"
                                class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->has('source') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-filter mr-2 opacity-50"></i>
                                    ROLES
                                </div>
                            </div>

                            <div x-show="roleFiltersExpanded" x-collapse
                                class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                <a href="{{ route('admin.surveys.index', ['source' => 'admin']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'admin' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">ADMIN</a>
                                <a href="{{ route('admin.surveys.index', ['source' => 'organization']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'organization' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">ORG</a>
                                <a href="{{ route('admin.surveys.index', ['source' => 'independent']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'independent' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">INDEPENDENT</a>
                            </div>
                        </div>

                        <div class="relative mt-1" x-data="{ n4: false, n4Top: 0, n4Left: 0 }" @mouseenter="const r = $el.getBoundingClientRect(); n4Top = r.top - 8; n4Left = r.right + 15; n4 = true" @mouseleave="n4 = false">
                            <a href="{{ route('surveys.create') }}"
                                class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('surveys.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">CREATE SURVEY</a>
                            <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]" style="border-radius: 0.75rem; display: none;" x-show="n4" :style="{ top: n4Top + 'px', left: n4Left + 'px' }">
                                <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">Method</div>
                                <div class="space-y-1">
                                    <a href="{{ route('surveys.create') }}"
                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Blank</a>
                                    <a href="{{ route('library.templates') }}"
                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Template</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Library -->
                <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'library')" @mouseleave="clearFlyout()">
                    <div @click="expandedItem = (expandedItem === 'library' ? null : 'library')"
                        class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->routeIs('projects.archived') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-book-bookmark mr-3 {{ request()->routeIs('projects.archived') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            LIBRARY
                        </div>
                    </div>

                    <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                        x-show="hoverItem === 'library' && expandedItem !== 'library'" 
                        :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" 
                        style="display: none;"
                        @mouseenter="hoverItem = 'library'"
                        @mouseleave="clearFlyout()">
                        <a href="{{ route('projects.archived') }}"
                            class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Archived
                            Surveys</a>
                        <a href="{{ route('library.templates') }}"
                            class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Templates</a>
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
                    <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'org_projects')" @mouseleave="clearFlyout()">
                        <div @click="expandedItem = (expandedItem === 'org_projects' ? null : 'org_projects')"
                            class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ (request()->routeIs('projects.active') || request()->routeIs('surveys.create') || request()->routeIs('projects.drafts')) ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                            <div class="flex items-center">
                                <i
                                    class="fa-solid fa-diagram-project mr-3 {{ (request()->routeIs(['projects.index', 'projects.active', 'surveys.create', 'projects.drafts'])) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                                MANAGE SURVEYS
                            </div>
                        </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                            x-show="hoverItem === 'org_projects' && expandedItem !== 'org_projects'" 
                            :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" 
                            style="display: none;"
                            @mouseenter="hoverItem = 'org_projects'"
                            @mouseleave="clearFlyout()">
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
                                <div class="relative" x-data="{ nHubCreate: false, nHubTop: 0, nHubLeft: 0 }" @mouseenter="const r = $el.getBoundingClientRect(); nHubTop = r.top; nHubLeft = r.right + 15; nHubCreate = true" @mouseleave="nHubCreate = false">
                                    <a href="{{ route('surveys.create') }}"
                                        class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Create Survey</a>
                                    <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]" style="border-radius: 0.75rem; display: none;" x-show="nHubCreate" :style="{ top: nHubTop + 'px', left: nHubLeft + 'px' }">
                                        <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">Method</div>
                                        <div class="space-y-1">
                                            <a href="{{ route('surveys.create') }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Blank</a>
                                            <a href="{{ route('library.templates') }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Template</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="expandedItem === 'org_projects'" x-collapse class="sidebar-submenu" x-data="{ hubSubExpanded: {{ request()->filled('category') ? 'true' : 'false' }} }">
                            <a href="{{ route('projects.active') }}"
                                class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('projects.active') && !request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">ALL SURVEYS</a>
                            
                            <a href="{{ route('projects.drafts') }}"
                                class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('projects.drafts') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} mt-1">DRAFTS</a>

                            <!-- Categories Nested in Manage -->
                            <div class="mb-1 mt-1">
                                <div @click="hubSubExpanded = !hubSubExpanded"
                                    class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                    <div class="flex items-center uppercase">
                                        <i class="fa-solid fa-layer-group mr-2 opacity-50"></i>
                                        BY CATEGORY
                                    </div>
                                </div>

                                <div x-show="hubSubExpanded" x-collapse
                                    class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                    @foreach($categories as $key => $cat)
                                        <a href="{{ route('projects.active', ['category' => $key]) }}"
                                            class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('category') === $key ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">
                                            {{ $cat['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="relative mt-1" x-data="{ nHubSubCreate: false, nHubSubTop: 0, nHubSubLeft: 0 }" @mouseenter="const r = $el.getBoundingClientRect(); nHubSubTop = r.top - 8; nHubSubLeft = r.right + 15; nHubSubCreate = true" @mouseleave="nHubSubCreate = false;">
                                <a href="{{ route('surveys.create') }}"
                                    class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('surveys.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">CREATE SURVEY</a>
                                <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]" style="border-radius: 0.75rem; display: none;" x-show="nHubSubCreate" :style="{ top: nHubSubTop + 'px', left: nHubSubLeft + 'px', zIndex: 50 }">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">Method</div>
                                    <div class="space-y-1">
                                        <a href="{{ route('surveys.create') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Blank</a>
                                        <a href="{{ route('library.templates') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Template</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Library Section -->
                    <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'org_library')" @mouseleave="clearFlyout()">
                        <div @click="expandedItem = (expandedItem === 'org_library' ? null : 'org_library')"
                            class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ request()->routeIs('projects.archived') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                            <div class="flex items-center">
                                <i
                                    class="fa-solid fa-book-bookmark mr-3 {{ request()->routeIs('projects.archived') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                                LIBRARY
                            </div>
                        </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                            x-show="hoverItem === 'org_library' && expandedItem !== 'org_library'" 
                            :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" 
                            style="display: none;"
                            @mouseenter="hoverItem = 'org_library'"
                            @mouseleave="clearFlyout()">
                            <a href="{{ route('projects.archived') }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Archived
                                Projects</a>
                            <a href="{{ route('library.templates') }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">Templates</a>
                        </div>

                        <div x-show="expandedItem === 'org_library'" x-collapse class="sidebar-submenu">
                            <a href="{{ route('projects.archived') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('projects.archived') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Archived
                                Projects</a>
                            <a href="{{ route('library.templates') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('library.templates') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">Templates</a>
                        </div>
                    </div>
                @endif
            @endif

            <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'studio')" @mouseleave="clearFlyout()">
                <div @click="expandedItem = (expandedItem === 'studio' ? null : 'studio')"
                    class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ request()->routeIs('research-proposal.*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                    <div class="flex items-center">
                        <i
                            class="fa-solid fa-graduation-cap mr-3 {{ request()->routeIs('research-proposal.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        WRITE REPORT
                    </div>
                </div>

                <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                    x-show="hoverItem === 'studio' && expandedItem !== 'studio'" 
                    :style="{ top: (flyoutTop > 400 ? flyoutTop - 80 : flyoutTop) + 'px', left: flyoutLeft + 'px' }" 
                    style="display: none;"
                    @mouseenter="hoverItem = 'studio'"
                    @mouseleave="clearFlyout()">
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
            <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'public')" @mouseleave="clearFlyout()">
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
                    x-show="hoverItem === 'public' && expandedItem !== 'public'" 
                    :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" 
                    style="display: none;"
                    @mouseenter="hoverItem = 'public'"
                    @mouseleave="clearFlyout()">
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
                        <div x-data="{ qlOpen: false, qlTop: 0, qlLeft: 0 }" @mouseenter="const r = $el.getBoundingClientRect(); qlTop = r.top - 100; qlLeft = r.right + 15; qlOpen = true" @mouseleave="qlOpen = false" class="relative">
                            <a href="{{ route('surveys.create') }}"
                                class="block w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-lg text-center shadow-lg shadow-indigo-100 transition-all">
                                Create Survey
                            </a>
                            <!-- Flyout -->
                            <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]" style="border-radius: 0.75rem; display: none;" x-show="qlOpen" :style="{ top: qlTop + 'px', left: qlLeft + 'px' }">
                                <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">Method</div>
                                <div class="space-y-1 text-left">
                                    <a href="{{ route('surveys.create') }}"
                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Blank</a>
                                    <a href="{{ route('library.templates') }}"
                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">Template</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </nav>
    </div>
    <div class="h-20 w-full mb-10"></div>
</div>

<!-- Mobile Toggle Button (Fixed Bottom Left) -->
<div class="fixed bottom-6 left-6 z-[60] md:hidden">
    <button @click="mobileMenuOpen = !mobileMenuOpen" 
            class="bg-indigo-600 text-white p-3 rounded-full shadow-lg hover:bg-indigo-700 transition-all transform active:scale-95 focus:outline-none focus:ring-4 focus:ring-indigo-200">
        <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
    </button>
</div>