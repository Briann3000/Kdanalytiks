<!-- Organization Workspace Section -->
<div class="sidebar-item relative" @mouseenter="setFlyout($el, 'workspace')" @mouseleave="scheduleClearFlyout()">
    <div @click="expandedItem = (expandedItem === 'workspace' ? null : 'workspace')"
        class="flex items-center justify-between px-3 py-2 text-sm font-bold {{ $isWorkspaceActive ? 'text-[#f0f0f1] bg-[#2271b1] border-l-2 border-[#2271b1] shadow-sm' : 'text-[#f0f0f1] hover:bg-[#101417] hover:text-[#72aee6]' }} rounded-lg group transition-colors cursor-pointer">
        <div class="flex items-center">
            <i
                class="fa-solid fa-building-user mr-3 {{ $isWorkspaceActive ? 'text-[#f0f0f1]' : 'text-zinc-200 group-hover:text-[#f0f0f1]' }}"></i>
            {{ __('Organization Workspace') }}
        </div>
        <i class="fa-solid fa-chevron-right text-[10px] text-zinc-300 transition-transform duration-300"
            :class="expandedItem === 'workspace' ? 'rotate-90 text-[#f0f0f1]' : ''"></i>
    </div>

    <!-- Flyout Menu on Hover -->
    <template x-teleport="body">
        <div class="flyout-menu shadow-xl border border-[#2c3338] p-2 min-w-[200px]"
            x-show="hoverItem === 'workspace' && expandedItem !== 'workspace'"
            :style="{ top: flyoutTop + 'px', left: flyoutLeft + 'px', maxHeight: flyoutMaxHeight }"
            style="display: none;" @mouseenter="cancelClearFlyout()" @mouseleave="scheduleClearFlyout()">
            @if($canManageOrg)
                <a href="{{ route('organization.audit.index') }}"
                    class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('organization.audit.*') ? 'text-[#f0f0f1] font-semibold bg-[#2271b1]' : 'text-[#f0f0f1] hover:bg-[#101417] hover:text-[#72aee6]' }} rounded-lg">{{ __('Audit Trail Logs') }}</a>
            @endif
            @if($canFieldwork)
                <a href="{{ route('organization.fieldwork.index') }}"
                    class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('organization.fieldwork.*') ? 'text-[#f0f0f1] font-semibold bg-[#2271b1]' : 'text-[#f0f0f1] hover:bg-[#101417] hover:text-[#72aee6]' }} rounded-lg">{{ __('Fieldwork & Enumerators') }}</a>
            @endif
            @if($canSocius)
                <a href="{{ route('organization.socius.index') }}"
                    class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('organization.socius.*') ? 'text-[#f0f0f1] font-semibold bg-[#2271b1]' : 'text-[#f0f0f1] hover:bg-[#101417] hover:text-[#72aee6]' }} rounded-lg">{{ __('Org Socius AI') }}</a>
            @endif
            @if($canManageOrg)
                <a href="{{ route('organization.settings.index') }}"
                    class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('organization.settings.*') ? 'text-[#f0f0f1] font-semibold bg-[#2271b1]' : 'text-[#f0f0f1] hover:bg-[#101417] hover:text-[#72aee6]' }} rounded-lg">{{ __('Settings') }}</a>
            @endif
            <a href="{{ route('organization.switcher') }}"
                class="block px-3 py-1.5 text-xs font-bold text-indigo-300 hover:text-indigo-200 rounded-lg">{{ __('Switch Workspace') }}</a>
            @if($canTeamView)
                <a href="{{ route('organization.team.index') }}"
                    class="block px-3 py-1.5 text-xs font-bold {{ request()->routeIs('organization.team.*') ? 'text-[#f0f0f1] font-semibold bg-[#2271b1]' : 'text-[#f0f0f1] hover:bg-[#101417] hover:text-[#72aee6]' }} rounded-lg">{{ __('Team Members') }}</a>
            @endif
        </div>
    </template>

    <!-- Accordion Submenu -->
    <div x-show="expandedItem === 'workspace'" x-collapse class="sidebar-submenu">
        @if($canManageOrg)
            <a href="{{ route('organization.audit.index') }}"
                class="block py-1 text-xs font-bold {{ request()->routeIs('organization.audit.*') ? 'text-[#f0f0f1] font-semibold' : 'text-[#f0f0f1]' }}">{{ __('Audit Trail Logs') }}</a>
        @endif
        @if($canFieldwork)
            <a href="{{ route('organization.fieldwork.index') }}"
                class="block py-1 text-xs font-bold {{ request()->routeIs('organization.fieldwork.*') ? 'text-[#f0f0f1] font-semibold' : 'text-[#f0f0f1]' }}">{{ __('Fieldwork & Enumerators') }}</a>
        @endif
        @if($canSocius)
            <a href="{{ route('organization.socius.index') }}"
                class="block py-1 text-xs font-bold {{ request()->routeIs('organization.socius.*') ? 'text-[#f0f0f1] font-semibold' : 'text-[#f0f0f1]' }}">{{ __('Org Socius AI') }}</a>
        @endif
        @if($canManageOrg)
            <a href="{{ route('organization.settings.index') }}"
                class="block py-1 text-xs font-bold {{ request()->routeIs('organization.settings.*') ? 'text-[#f0f0f1] font-semibold' : 'text-[#f0f0f1]' }}">{{ __('Settings') }}</a>
        @endif
        <a href="{{ route('organization.switcher') }}"
            class="block py-1 text-xs font-bold text-indigo-300 hover:text-indigo-200">{{ __('Switch Workspace') }}</a>
        @if($canTeamView)
            <a href="{{ route('organization.team.index') }}"
                class="block py-1 text-xs font-bold {{ request()->routeIs('organization.team.*') ? 'text-[#f0f0f1] font-semibold' : 'text-[#f0f0f1]' }}">{{ __('Team Members') }}</a>
        @endif
    </div>
</div>