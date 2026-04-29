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
    if (request()->routeIs(['surveys.index', 'surveys.summary', 'surveys.data', 'surveys.reports', 'surveys.settings', 'surveys.create', 'surveys.edit']) && request('status') !== 'archived')
        $initialExpanded = 'projects';
    if (request()->routeIs(['surveys.index']) && request('status') === 'archived')
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
        'polls' => ['icon' => 'fa-square-poll-vertical', 'label' => 'Polls'],
        'others' => ['icon' => 'fa-folder-open', 'label' => 'Others'],
    ];
@endphp

<div class="space-y-6 px-1 h-full" x-data="{ 
    expandedItem: '{{ $initialExpanded }}', 
    hoverItem: null,
    mobileMenuOpen: false,
    hubExpanded: {{ request()->filled('category') && (request()->routeIs('surveys.*') || request()->routeIs('surveys.create')) ? 'true' : 'false' }},
    publicExpanded: {{ request()->routeIs('surveys.public') && request()->filled('category') ? 'true' : 'false' }},
    flyoutTop: 0,
    flyoutLeft: 0,
    setFlyout(e, type) {
        if(!e) return;
        this.hoverItem = type;
        const r = e.getBoundingClientRect();
        this.flyoutTop = r.top;
        this.flyoutLeft = r.right - 5;
    },
    clearFlyout() {
        this.hoverItem = null;
    }
}">
    <div>
        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">
            {{ __($role === 'admin' ? 'ADMINISTRATION' : ($role === 'guest' ? 'NAVIGATION' : 'WORKSPACE')) }}
        </h4>
        <nav class="space-y-1">
            @if($role === 'admin')
                <div class="sidebar-item relative" @mouseenter="hoverItem = 'dashboard'" @mouseleave="hoverItem = null">
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->routeIs('admin.dashboard') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i
                            class="fa-solid fa-server mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        {{ __('DASHBOARD') }}
                    </a>
                </div>

                <!-- MANAGE USERS -->
                <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'users')" @mouseleave="clearFlyout()">
                    <div class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->is('admin/users*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer"
                        @click="expandedItem = (expandedItem === 'users' ? null : 'users')">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-user-gear mr-3 {{ request()->is('admin/users*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            {{ __('MANAGE USERS') }}
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 transition-transform duration-300"
                            :class="expandedItem === 'users' ? 'rotate-90 text-indigo-500' : ''"></i>
                    </div>

                    <template x-teleport="body">
                        <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                            x-show="hoverItem === 'users' && expandedItem !== 'users'"
                            :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" style="display: none;"
                            @mouseenter="hoverItem = 'users'" @mouseleave="clearFlyout()">
                            <div class="mb-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">
                                    {{ __('Quick Actions') }}
                                </div>
                                <a href="{{ route('admin.users.index') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('All Users') }}</a>

                                <a href="{{ route('admin.users.create') }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Create User') }}</a>

                            </div>
                            <div class="border-t border-gray-50 pt-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">
                                    {{ __('Filter Roles') }}</div>
                                <div class="space-y-1">
                                    <a href="{{ route('admin.users.index', ['role' => 'independent']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">{{ __('INDEPENDENT') }}</a>
                                    <a href="{{ route('admin.users.index', ['role' => 'organization']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">{{ __('ORGANIZATION') }}</a>
                                    <a href="{{ route('admin.users.index', ['role' => 'respondent']) }}"
                                        class="block px-3 py-1 text-[10px] font-bold text-gray-400 hover:text-indigo-600">{{ __('RESPONDENT') }}</a>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Accordion -->
                    <div x-show="expandedItem === 'users'" x-collapse class="sidebar-submenu"
                        x-data="{ allUsersExpanded: {{ request()->has('role') ? 'true' : 'false' }} }">
                        <div class="mb-1">
                            <div @click="allUsersExpanded = !allUsersExpanded"
                                x-data="{ nestSub: false, nestTop: 0, nestLeft: 0 }"
                                @mouseenter="const r = $el.getBoundingClientRect(); nestTop = r.top; nestLeft = r.right - 5; nestSub = true"
                                @mouseleave="nestSub = false"
                                class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->routeIs('admin.users.index') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors relative">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-users mr-2 opacity-50"></i>
                                    {{ __('ACTIVE USERS') }}
                                </div>

                                <template x-teleport="body">
                                    <!-- Submenu Flyout for Roles -->
                                    <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]"
                                        style="border-radius: 0.75rem; display: none;" x-show="nestSub"
                                        :style="{ top: nestTop + 'px', left: nestLeft + 'px' }">
                                        <div
                                            class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">
                                            {{ __('Filter Roles') }}
                                        </div>
                                        <div class="space-y-1">
                                            <a href="{{ route('admin.users.index', ['role' => 'independent']) }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">{{ __('INDEPENDENT') }}</a>
                                            <a href="{{ route('admin.users.index', ['role' => 'organization']) }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">{{ __('ORGANIZATION') }}</a>
                                            <a href="{{ route('admin.users.index', ['role' => 'respondent']) }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">{{ __('RESPONDENT') }}</a>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div x-show="allUsersExpanded" x-collapse
                                class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1 font-bold">
                                <a href="{{ route('admin.users.index', ['role' => 'independent']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('role') === 'independent' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">{{ __('INDEPENDENT') }}</a>
                                <a href="{{ route('admin.users.index', ['role' => 'organization']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('role') === 'organization' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">{{ __('ORGANIZATION') }}</a>
                                <a href="{{ route('admin.users.index', ['role' => 'respondent']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('role') === 'respondent' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">{{ __('RESPONDENT') }}</a>
                            </div>
                        </div>

                        <a href="{{ route('admin.users.create') }}"
                            class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('admin.users.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('CREATE USER') }}</a>
                    </div>
                </div>

                <!-- MANAGE SURVEYS -->
                <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'surveys')" @mouseleave="clearFlyout()">
                    <div class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->is('admin/surveys*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer"
                        @click="expandedItem = (expandedItem === 'surveys' ? null : 'surveys')">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-list-check mr-3 {{ request()->is('admin/surveys*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            {{ __('MANAGE SURVEYS') }}
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 transition-transform duration-300"
                            :class="expandedItem === 'surveys' ? 'rotate-90 text-indigo-500' : ''"></i>
                    </div>

                    <template x-teleport="body">
                        <!-- Flyout for Manage Surveys -->
                        <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                            x-show="hoverItem === 'surveys' && expandedItem !== 'surveys'"
                            :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" style="display: none;"
                            @mouseenter="hoverItem = 'surveys'" @mouseleave="clearFlyout()">
                            <div class="mb-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">
                                    {{ __('Inventory') }}
                                </div>
                                <div class="relative" x-data="{ n1: false, n1Top: 0, n1Left: 0 }"
                                    @mouseenter="const r = $el.getBoundingClientRect(); n1Top = r.top; n1Left = r.right - 5; n1 = true"
                                    @mouseleave="n1 = false">
                                    <a href="{{ route('admin.surveys.index') }}"
                                        class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('All Surveys') }}</a>
                                    <!-- Nested Flyout -->
                                    <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]"
                                        style="border-radius: 0.75rem; display: none;" x-show="n1"
                                        :style="{ top: n1Top + 'px', left: n1Left + 'px' }">
                                        <div
                                            class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">
                                            {{ __('By Role') }}
                                        </div>
                                        <div class="space-y-1">
                                            <a href="{{ route('admin.surveys.index', ['source' => 'admin']) }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">{{ __('ADMIN') }}</a>
                                            <a href="{{ route('admin.surveys.index', ['source' => 'organization']) }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">{{ __('ORGANIZATION') }}</a>
                                            <a href="{{ route('admin.surveys.index', ['source' => 'independent']) }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">{{ __('INDEPENDENT') }}</a>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('admin.surveys.index', ['status' => 'active']) }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Active Surveys') }}</a>
                                {{-- Removed Pending Approval --}}
                                <a href="{{ route('admin.surveys.index', ['status' => 'draft']) }}"
                                    class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Drafts') }}</a>
                            </div>
                            <div class="border-t border-gray-50 pt-3">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">
                                    {{ __('Actions') }}
                                </div>
                                <div class="relative" x-data="{ n2: false, n2Top: 0, n2Left: 0 }"
                                    @mouseenter="const r = $el.getBoundingClientRect(); n2Top = r.top; n2Left = r.right - 5; n2 = true"
                                    @mouseleave="n2 = false">
                                    <a href="{{ route('surveys.create') }}"
                                        class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Create Survey') }}</a>
                                    <!-- Nested Flyout -->
                                    <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]"
                                        style="border-radius: 0.75rem; display: none;" x-show="n2"
                                        :style="{ top: n2Top + 'px', left: n2Left + 'px' }">
                                        <div
                                            class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">
                                            {{ __('Method') }}
                                        </div>
                                        <div class="space-y-1">
                                            <a href="{{ route('surveys.create') }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">{{ __('BLANK') }}</a>
                                            <a href="{{ route('library.templates') }}"
                                                class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors">{{ __('TEMPLATE') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Accordion -->
                    <div x-show="expandedItem === 'surveys'" x-collapse class="sidebar-submenu"
                        x-data="{ roleFiltersExpanded: {{ request()->has('source') ? 'true' : 'false' }} }">
                        <div class="relative" x-data="{ n3: false, n3Top: 0, n3Left: 0 }"
                            @mouseenter="const r = $el.getBoundingClientRect(); n3Top = r.top - 8; n3Left = r.right - 5; n3 = true"
                            @mouseleave="n3 = false">
                            <a href="{{ route('admin.surveys.index') }}"
                                class="block py-1 text-xs font-bold uppercase tracking-wide {{ !request()->has('status') && request()->routeIs('admin.surveys.index') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('ALL SURVEYS') }}</a>
                            <template x-teleport="body">
                                <!-- Nested Flyout -->
                                <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]"
                                    style="border-radius: 0.75rem; display: none;" x-show="n3"
                                    :style="{ top: n3Top + 'px', left: n3Left + 'px' }">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">
                                        {{ __('By Role') }}</div>
                                    <div class="space-y-1">
                                        <a href="{{ route('admin.surveys.index', ['source' => 'admin']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Admin') }}</a>
                                        <a href="{{ route('admin.surveys.index', ['source' => 'organization']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Org') }}</a>
                                        <a href="{{ route('admin.surveys.index', ['source' => 'independent']) }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Independent') }}</a>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <a href="{{ route('admin.surveys.index', ['status' => 'active']) }}"
                            class="block py-1 text-xs font-bold uppercase tracking-wide {{ request('status') === 'active' ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} mt-1">{{ __('ACTIVE SURVEYS') }}</a>

                        <a href="{{ route('admin.surveys.index', ['status' => 'draft']) }}"
                            class="block py-1 text-xs font-bold uppercase tracking-wide {{ request('status') === 'draft' ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} mt-1">{{ __('DRAFTS') }}</a>

                        <!-- Filters Section -->
                        <div class="mb-1 mt-1">
                            <div @click="roleFiltersExpanded = !roleFiltersExpanded"
                                class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->has('source') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-filter mr-2 opacity-50"></i>
                                    {{ __('ROLES') }}
                                </div>
                            </div>

                            <div x-show="roleFiltersExpanded" x-collapse
                                class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                <a href="{{ route('admin.surveys.index', ['source' => 'admin']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'admin' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">{{ __('ADMIN') }}</a>
                                <a href="{{ route('admin.surveys.index', ['source' => 'organization']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'organization' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">{{ __('ORG') }}</a>
                                <a href="{{ route('admin.surveys.index', ['source' => 'independent']) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('source') === 'independent' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">{{ __('INDEPENDENT') }}</a>
                            </div>
                        </div>

                        <div class="relative mt-1" x-data="{ n4: false, n4Top: 0, n4Left: 0 }"
                            @mouseenter="const r = $el.getBoundingClientRect(); n4Top = r.top - 8; n4Left = r.right - 5; n4 = true"
                            @mouseleave="n4 = false">
                            <a href="{{ route('surveys.create') }}"
                                class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('surveys.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('CREATE SURVEY') }}</a>
                            <template x-teleport="body">
                                <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]"
                                    style="border-radius: 0.75rem; display: none;" x-show="n4"
                                    :style="{ top: n4Top + 'px', left: n4Left + 'px' }">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">
                                        {{ __('Method') }}
                                    </div>
                                    <div class="space-y-1">
                                        <a href="{{ route('surveys.create') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Blank') }}</a>
                                        <a href="{{ route('library.templates') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Template') }}</a>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Admin Library -->
                <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'library')" @mouseleave="clearFlyout()">
                    <div @click="expandedItem = (expandedItem === 'library' ? null : 'library')"
                        class="flex items-center justify-between px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->routeIs('surveys.archived') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                        <div class="flex items-center">
                            <i
                                class="fa-solid fa-book-bookmark mr-3 {{ request()->routeIs('surveys.archived') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            {{ __('LIBRARY') }}
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 transition-transform duration-300"
                            :class="expandedItem === 'library' ? 'rotate-90 text-indigo-500' : ''"></i>
                    </div>

                    <template x-teleport="body">
                        <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                            x-show="hoverItem === 'library' && expandedItem !== 'library'"
                            :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" style="display: none;"
                            @mouseenter="hoverItem = 'library'" @mouseleave="clearFlyout()">
                            <a href="{{ route('surveys.index', ['status' => 'archived']) }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Archived Surveys') }}</a>
                            <a href="{{ route('library.templates') }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Templates') }}</a>
                        </div>
                    </template>

                    <div x-show="expandedItem === 'library'" x-collapse class="sidebar-submenu">
                        <a href="{{ route('surveys.index', ['status' => 'archived']) }}"
                            class="block py-1 text-xs font-bold {{ (request()->routeIs('surveys.index') && request('status') === 'archived') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('Archived Surveys') }}</a>
                        <a href="{{ route('library.templates') }}"
                            class="block py-1 text-xs font-bold {{ request()->routeIs('library.templates') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('Templates') }}</a>
                    </div>
                </div>
            @else
                <a href="{{ $role === 'guest' ? route('home') : route($role . '.dashboard') }}"
                    class="flex items-center px-3 py-2 text-sm uppercase font-bold {{ ($role !== 'guest' && request()->routeIs($role . '.dashboard')) ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i
                        class="fa-solid fa-gauge-high mr-3 {{ ($role !== 'guest' && request()->routeIs($role . '.dashboard')) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    {{ __($role === 'guest' ? 'HOME' : 'DASHBOARD') }}
                </a>

                @if(in_array($role, ['organization', 'independent']))
                    <!-- Surveys Section -->
                    <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'org_projects')" @mouseleave="clearFlyout()">
                        <div @click="expandedItem = (expandedItem === 'org_projects' ? null : 'org_projects')"
                            class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ (request()->routeIs('surveys.index') && request('status') !== 'archived' || request()->routeIs('surveys.create')) ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                            <div class="flex items-center">
                                <i
                                    class="fa-solid fa-diagram-project mr-3 {{ (request()->routeIs(['surveys.index', 'surveys.create'])) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                                {{ __('MANAGE SURVEYS') }}
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 transition-transform duration-300"
                                :class="expandedItem === 'org_projects' ? 'rotate-90 text-indigo-500' : ''"></i>
                        </div>

                        <template x-teleport="body">
                            <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                                x-show="hoverItem === 'org_projects' && expandedItem !== 'org_projects'"
                                :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" style="display: none;"
                                @mouseenter="hoverItem = 'org_projects'" @mouseleave="clearFlyout()">
                                <div class="mb-3">
                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">
                                        {{ __('Survey Hub') }}

                                    </div>
                                    <div class="space-y-1">
                                        @foreach($categories as $key => $cat)
                                            <a href="{{ route('surveys.index', ['status' => 'active', 'category' => $key]) }}"
                                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-indigo-50/50 rounded-lg transition-colors">
                                                <i class="fa-solid {{ $cat['icon'] }} mr-2 opacity-50 w-4 text-center"></i>
                                                {{ __($cat['label']) }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="border-t border-gray-50 pt-3 mt-3">
                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">
                                        {{ __('Manage') }}
                                    </div>
                                    <a href="{{ route('surveys.index', ['status' => 'active']) }}"
                                        class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Active Surveys') }}</a>
                                    <a href="{{ route('surveys.index', ['status' => 'draft']) }}"
                                        class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Drafts') }}</a>
                                    <div class="relative" x-data="{ nHubCreate: false, nHubTop: 0, nHubLeft: 0 }"
                                        @mouseenter="const r = $el.getBoundingClientRect(); nHubTop = r.top; nHubLeft = r.right - 5; nHubCreate = true"
                                        @mouseleave="nHubCreate = false">
                                        <a href="{{ route('surveys.create') }}"
                                            class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Create Survey') }}</a>
                                        <template x-teleport="body">
                                            <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]"
                                                style="border-radius: 0.75rem; display: none;" x-show="nHubCreate"
                                                :style="{ top: nHubTop + 'px', left: nHubLeft + 'px' }">
                                                <div
                                                    class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">
                                                    {{ __('Method') }}
                                                </div>
                                                <div class="space-y-1">
                                                    <a href="{{ route('surveys.create') }}"
                                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Blank') }}</a>
                                                    <a href="{{ route('library.templates') }}"
                                                        class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Template') }}</a>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="expandedItem === 'org_projects'" x-collapse class="sidebar-submenu"
                            x-data="{ hubSubExpanded: {{ request()->filled('category') ? 'true' : 'false' }} }">
                            <a href="{{ route('surveys.index', ['status' => 'active']) }}"
                                class="block py-1 text-xs font-bold uppercase tracking-wide {{ (request()->routeIs('surveys.index') && request('status') === 'active' && !request()->filled('category')) ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('ACTIVE SURVEYS') }}</a>

                            <a href="{{ route('surveys.index', ['status' => 'draft']) }}"
                                class="block py-1 text-xs font-bold uppercase tracking-wide {{ (request()->routeIs('surveys.index') && request('status') === 'draft') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} mt-1">{{ __('DRAFTS') }}</a>

                            <!-- Categories Nested in Manage -->
                            <div class="mb-1 mt-1">
                                <div @click="hubSubExpanded = !hubSubExpanded"
                                    class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                                    <div class="flex items-center uppercase">
                                        <i class="fa-solid fa-layer-group mr-2 opacity-50"></i>
                                        {{ __('BY CATEGORY') }}
                                    </div>
                                </div>

                                <div x-show="hubSubExpanded" x-collapse
                                    class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                                    @foreach($categories as $key => $cat)
                                        <a href="{{ route('surveys.index', ['status' => 'active', 'category' => $key]) }}"
                                            class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('category') === $key && request('status') === 'active' ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">
                                            {{ __($cat['label']) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="relative mt-1" x-data="{ nHubSubCreate: false, nHubSubTop: 0, nHubSubLeft: 0 }"
                                @mouseenter="const r = $el.getBoundingClientRect(); nHubSubTop = r.top - 8; nHubSubLeft = r.right - 5; nHubSubCreate = true"
                                @mouseleave="nHubSubCreate = false;">
                                <a href="{{ route('surveys.create') }}"
                                    class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('surveys.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('CREATE SURVEY') }}</a>
                                <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]"
                                    style="border-radius: 0.75rem; display: none;" x-show="nHubSubCreate"
                                    :style="{ top: nHubSubTop + 'px', left: nHubSubLeft + 'px', zIndex: 50 }">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">
                                        {{ __('Method') }}
                                    </div>
                                    <div class="space-y-1">
                                        <a href="{{ route('surveys.create') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Blank') }}</a>
                                        <a href="{{ route('library.templates') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Template') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Library Section -->
                    <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'org_library')" @mouseleave="clearFlyout()">
                        <div @click="expandedItem = (expandedItem === 'org_library' ? null : 'org_library')"
                            class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ (request()->routeIs('surveys.index') && request('status') === 'archived') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                            <div class="flex items-center">
                                <i
                                    class="fa-solid fa-book-bookmark mr-3 {{ (request()->routeIs('surveys.index') && request('status') === 'archived') ? 'text-indigo-50' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                                {{ __('LIBRARY') }}
                            </div>
                            <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 transition-transform duration-300"
                                :class="expandedItem === 'org_library' ? 'rotate-90 text-indigo-500' : ''"></i>
                        </div>

                        <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                            x-show="hoverItem === 'org_library' && expandedItem !== 'org_library'"
                            :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px' }" style="display: none;"
                            @mouseenter="hoverItem = 'org_library'" @mouseleave="clearFlyout()">
                            <a href="{{ route('surveys.index', ['status' => 'archived']) }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Archived Surveys') }}</a>
                            <a href="{{ route('library.templates') }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('Templates') }}</a>
                        </div>

                        <div x-show="expandedItem === 'org_library'" x-collapse class="sidebar-submenu">
                            <a href="{{ route('surveys.index', ['status' => 'archived']) }}"
                                class="block py-1 text-xs font-bold {{ (request()->routeIs('surveys.index') && request('status') === 'archived') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('Archived Surveys') }}</a>
                            <a href="{{ route('library.templates') }}"
                                class="block py-1 text-xs font-bold {{ request()->routeIs('library.templates') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('Templates') }}</a>
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
                        {{ __('WRITE REPORT') }}
                    </div>
                    <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 transition-transform duration-300"
                        :class="expandedItem === 'studio' ? 'rotate-90 text-indigo-500' : ''"></i>
                </div>

                <template x-teleport="body">
                    <div class="flyout-menu shadow-xl border border-gray-100 p-2"
                        x-show="hoverItem === 'studio' && expandedItem !== 'studio'"
                        :style="{ top: (parseInt(flyoutTop) > 400 ? (parseInt(flyoutTop) - 80) : parseInt(flyoutTop)) + 'px', left: flyoutLeft + 'px' }"
                        style="display: none;" @mouseenter="hoverItem = 'studio'" @mouseleave="clearFlyout()">
                        <a href="{{ route('research-proposal.index') }}"
                            class="block px-3 py-1.5 text-xs font-bold font-bold {{ request()->routeIs('research-proposal.index') ? 'text-indigo-700 bg-gray-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg">{{ __('Report Generator') }}</a>
                        <a href="{{ route('research-proposal.create') }}"
                            class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('research-proposal.create') ? 'text-indigo-700 bg-gray-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg">{{ __('Draft Reports') }}</a>
                        <a href="{{ route('research-proposal.history') }}"
                            class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('research-proposal.history') ? 'text-indigo-700 bg-gray-50' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg">{{ __('Reports History') }}</a>
                    </div>
                </template>

                <div x-show="expandedItem === 'studio'" x-collapse class="sidebar-submenu">
                    <a href="{{ route('research-proposal.index') }}"
                        class="block py-1 text-xs font-bold {{ request()->routeIs('research-proposal.index') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('Report Generator') }}</a>
                    <a href="{{ route('research-proposal.create') }}"
                        class="block py-1 text-xs font-bold {{ request()->routeIs('research-proposal.create') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('Draft Reports') }}</a>
                    <a href="{{ route('research-proposal.history') }}"
                        class="block py-1 text-xs font-bold {{ request()->routeIs('research-proposal.history') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('Reports History') }}</a>
                </div>
            </div>

            <!-- Public Surveys Section -->
            <div class="sidebar-item relative" @mouseenter="setFlyout($el, 'public')" @mouseleave="clearFlyout()">
                <div @click="expandedItem = (expandedItem === 'public' ? null : 'public')"
                    class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ request()->routeIs('surveys.public') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors cursor-pointer">
                    <div class="flex items-center">
                        <i
                            class="fa-solid fa-globe mr-3 {{ request()->routeIs('surveys.public') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        {{ __('TAKE A SURVEY') }}
                    </div>
                    <i class="fa-solid fa-chevron-right text-[10px] text-gray-300 transition-transform duration-300"
                        :class="expandedItem === 'public' ? 'rotate-90 text-indigo-500' : ''"></i>
                </div>

                <template x-teleport="body">
                    <div class="flyout-menu shadow-xl border border-gray-100 p-4 min-w-[200px]"
                        x-show="hoverItem === 'public' && expandedItem !== 'public'"
                        :style="{ top: (parseInt(flyoutTop) > 400 ? (parseInt(flyoutTop) - 120) : parseInt(flyoutTop)) + 'px', left: flyoutLeft + 'px' }"
                        style="display: none;" @mouseenter="hoverItem = 'public'" @mouseleave="clearFlyout()">
                        <div class="mb-3">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">
                                {{ __('Discover') }}
                            </div>
                            <a href="{{ route('surveys.public') }}"
                                class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-gray-50 rounded-lg">{{ __('All Surveys') }}</a>
                        </div>
                        <div class="border-t border-gray-50 pt-3">
                            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-3">
                                {{ __('By Category') }}
                            </div>
                            <div class="space-y-1">
                                @foreach($categories as $key => $cat)
                                    <a href="{{ route('surveys.public', ['category' => $key]) }}"
                                        class="block px-3 py-1.5 text-xs font-bold text-gray-600 hover:text-indigo-700 hover:bg-indigo-50/50 rounded-lg transition-colors">
                                        <i class="fa-solid {{ $cat['icon'] }} mr-2 opacity-50 w-4 text-center"></i>
                                        {{ __($cat['label']) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="expandedItem === 'public'" x-collapse class="sidebar-submenu">
                    <a href="{{ route('surveys.public') }}"
                        class="block py-1 text-xs font-bold uppercase tracking-wide {{ request()->routeIs('surveys.public') && !request()->filled('category') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }}">{{ __('ACTIVE SURVEYS') }}</a>

                    <div class="mb-1 mt-1"
                        x-data="{ publicCatsExpanded: {{ request()->filled('category') && request()->routeIs('surveys.public') ? 'true' : 'false' }} }">
                        <div @click="publicCatsExpanded = !publicCatsExpanded"
                            class="flex items-center justify-between py-1.5 text-xs font-bold {{ request()->filled('category') && request()->routeIs('surveys.public') ? 'text-indigo-700' : 'text-gray-600 hover:text-indigo-700' }} cursor-pointer transition-colors">
                            <div class="flex items-center uppercase">
                                <i class="fa-solid fa-layer-group mr-2 opacity-50"></i>
                                {{ __('BY CATEGORY') }}
                            </div>
                        </div>

                        <div x-show="publicCatsExpanded" x-collapse
                            class="pl-4 space-y-1 my-1 border-l-2 border-indigo-50/50 ml-1">
                            @foreach($categories as $key => $cat)
                                <a href="{{ route('surveys.public', ['category' => $key]) }}"
                                    class="block py-1 text-[10px] font-bold uppercase tracking-wider {{ request('category') === $key && request()->routeIs('surveys.public') ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-600' }} transition-colors">
                                    {{ __($cat['label']) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wallet Section -->
            <div class="sidebar-item relative">
                <a href="{{ route('wallet.index') }}"
                    class="flex items-center px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->routeIs('wallet.*') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600 shadow-sm' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                    <i
                        class="fa-solid fa-wallet mr-3 {{ request()->routeIs('wallet.*') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                    {{ __('MY WALLET') }}
                </a>
            </div>

            @if($role !== 'guest' && $role !== 'respondent')
                <div class="pt-6 border-t border-gray-100 px-3">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">{{ __('Quick Links') }}</h4>
                    <div class="space-y-3">
                        <div x-data="{ qlOpen: false, qlTop: 0, qlLeft: 0 }"
                            @mouseenter="const r = $el.getBoundingClientRect(); qlTop = r.top - 100; qlLeft = r.right - 5; qlOpen = true"
                            @mouseleave="qlOpen = false" class="relative">
                            @if(in_array($role, ['organization', 'independent', 'admin']))
                                <a href="{{ route('surveys.create') }}"
                                    class="block w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-lg text-center shadow-lg shadow-indigo-100 transition-all">
                                    <i class="fa-solid fa-plus-circle mr-2"></i> {{ __('Create Survey') }}
                                </a>
                            @endif
                            <template x-teleport="body">
                                <!-- Flyout -->
                                <div class="flyout-menu shadow-2xl border border-gray-100 p-3 min-w-[140px]"
                                    style="border-radius: 0.75rem; display: none;" x-show="qlOpen"
                                    :style="{ top: qlTop + 'px', left: qlLeft + 'px' }">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 px-2">
                                        {{ __('Method') }}
                                    </div>
                                    <div class="space-y-1 text-left">
                                        <a href="{{ route('surveys.create') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Blank') }}</a>
                                        <a href="{{ route('library.templates') }}"
                                            class="block px-3 py-1.5 text-[10px] font-bold text-gray-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition-colors uppercase">{{ __('Template') }}</a>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            @endif
        </nav>
    </div>

    <!-- ACCOUNT SECTION -->
    @auth
        <div>
            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 mt-8">{{ __('ACCOUNT') }}</h4>
            <nav class="space-y-1">
                <div class="sidebar-item relative">
                    <a href="{{ route('account.settings') }}"
                        class="flex items-center px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->routeIs('account.settings') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                        <i
                            class="fa-solid fa-user-gear mr-3 {{ request()->routeIs('account.settings') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                        {{ __('SETTINGS') }}
                    </a>
                </div>
                @if(in_array($role, ['organization', 'independent', 'respondent']))
                    <div class="sidebar-item relative">
                        <a href="{{ route('subscriptions.index') }}"
                            class="flex items-center px-3 py-2 text-sm font-bold uppercase tracking-wider {{ request()->routeIs('subscriptions.index') ? 'text-indigo-700 bg-indigo-50 border-l-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-700 hover:bg-gray-50' }} rounded-lg group transition-colors">
                            <i
                                class="fa-solid fa-crown mr-3 {{ request()->routeIs('subscriptions.index') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"></i>
                            {{ __('SUBSCRIBE') }}
                        </a>
                    </div>
                @endif
            </nav>
        </div>
    @endauth

    <div class="h-20 w-full mb-10"></div>
</div>

<!-- Mobile Toggle Button (Fixed Bottom Left) -->
<div class="fixed bottom-6 left-6 z-[60] md:hidden">
    <button @click="mobileMenuOpen = !mobileMenuOpen"
        class="bg-indigo-600 text-white p-3 rounded-full shadow-lg hover:bg-indigo-700 transition-all transform active:scale-95 focus:outline-none focus:ring-4 focus:ring-indigo-200">
        <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
    </button>
</div>