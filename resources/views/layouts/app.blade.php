<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- PWA Settings -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#4f46e5">
    <link rel="manifest" href="{{ asset('manifest.json') }}?v=4">

    <title>{{ config('app.name', 'KDAnalytiks') }}</title>

    <!-- Tailwind CSS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js + Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Visual Generation Libs -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.min.js"></script>

    @stack('styles')
    @yield('head')
    <style>
        /* Global Responsiveness Helpers */
        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }

        /* Global Scaling */
        html {
            font-size: 15px;
            /* Reduced for data-density */
            font-family: 'Inter', sans-serif;
        }

        /* Prevent content cut-off */
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        /* Fluid Layout Helpers */
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        @media (min-width: 1024px) {
            .container-fluid {
                padding-left: 3rem;
                padding-right: 3rem;
            }
        }

        main {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }

        @media (max-width: 640px) {
            .mobile-stack {
                flex-direction: column;
            }

            .mobile-hide {
                display: none;
            }

            .mobile-padding {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            main {
                padding-bottom: 80px;
                /* Space for chatbot button */
            }
        }

        /* Mobile Menu Transitions */
        #mobile-menu {
            transition: max-height 0.3s ease-out, opacity 0.2s ease;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
        }

        #mobile-menu.open {
            max-height: 500px;
            opacity: 1;
        }

        /* Split-Pane Styles */
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        .workspace-layout {
            display: flex;
            height: calc(100vh - 4.1rem);
            overflow: visible !important;
            position: relative;
        }

        .sidebar-pane {
            width: 200px;
            background: white;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            z-index: 100;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100%;
            overflow-y: auto !important;
            scrollbar-width: thin;
            transition: transform 0.3s ease-in-out, width 0.3s ease;
        }

        @media (max-width: 1023px) {
            .sidebar-pane {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                width: 260px;
                z-index: 1000;
                background: white;
                box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease-in-out;
                transform: translateX(-100%);
            }

            .sidebar-pane[style*="display: none"] {
                transform: translateX(-100%) !important;
                opacity: 0;
            }

            .sidebar-pane:not([style*="display: none"]) {
                transform: translateX(0) !important;
                opacity: 1;
            }

            .sidebar-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(2px);
                z-index: 999;
            }

            .workspace-layout {
                height: auto;
            }
        }

        .sidebar-pane::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-pane::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 20px;
        }

        .sidebar-item {
            z-index: 10;
        }

        /* Remove wrapper scrollbar styles as we now apply them directly to sidebar-pane */

        /* Essential logic for JS fixed flyouts */
        .flyout-menu {
            position: fixed !important;
            width: auto;
            min-width: 180px;
            background: white;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 99999;
            border-radius: 0.5rem;
            pointer-events: auto;
        }

        /* Basic submenu indentation */
        .sidebar-submenu {
            padding-left: 1.5rem;
            margin-top: 0.25rem;
            border-left: 1px solid #f3f4f6;
        }

        .content-pane {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: #fdfdfd;
            position: relative;
            overflow-y: auto;
            overflow-x: auto;
            /* Allow horizontal content scroll for builder tables */
        }

        @media (max-width: 1023px) {
            .sidebar-pane {
                /* Removed drawer transition as per request */
            }

            .content-pane {
                padding: 0;
            }
        }

        /* Sidebar Navigation Container */
        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 1rem;
            overflow: visible !important;
        }

        .sidebar-item {
            position: relative;
        }

        /* Custom Scrollbar Helper */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 20px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.75rem;
            color: #6b7280;
        }

        /* Native App Safe Areas */
        .is-native-app {
            padding-top: env(safe-area-inset-top, 20px);
        }

        .is-native-app nav.sticky {
            top: env(safe-area-inset-top, 20px);
        }

        .pb-safe {
            padding-bottom: env(safe-area-inset-bottom, 16px);
        }

        /* Pull-to-Refresh Visuals - Premium Glassmorphism */
        #ptr-indicator {
            position: fixed;
            top: -70px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: #4f46e5;
            z-index: 9999;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        #ptr-indicator .refresh-text {
            display: none;
            /* Hide text for a cleaner icon-only look */
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50 text-gray-900" x-data="{ mobileMenuOpen: false }"
    :class="Capacitor.isNativePlatform() ? 'is-native-app' : ''">
    <!-- Pull to Refresh Indicator -->
    <div id="ptr-indicator">
        <i class="fa-solid fa-arrows-rotate animate-spin-slow"></i>
    </div>
    <div class="min-h-screen flex flex-col"
        x-data="{ sidebarOpen: true, desktopSidebarOpen: window.innerWidth > 1024 && !{{ request()->routeIs('surveys.create', 'surveys.edit') ? 'true' : 'false' }} }"
        @close-sidebar.window="desktopSidebarOpen = false" @open-sidebar.window="desktopSidebarOpen = true">
        <!-- Navigation Bar -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-[60]">
            <div class="max-w-full mx-auto px-4 sm:px-8 lg:px-12">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <!-- Sidebar Toggle (Youtube-style) -->
                        @auth
                            @if(auth()->user()->hasVerifiedEmail())
                                @php
                                    $isReportPage = request()->routeIs('surveys.report', 'surveys.responses') || request()->is('*/report', '*/responses*');
                                @endphp

                                <!-- Main Sidebar Toggle -->
                                <button type="button" @click="desktopSidebarOpen = !desktopSidebarOpen"
                                    class="mr-3 p-2 rounded-xl bg-slate-50 border border-slate-200 text-indigo-700 hover:bg-slate-100 hover:border-slate-300 shadow-sm transition-all flex items-center justify-center w-10 h-10 group">
                                    <i class="fa-solid fa-bars-staggered text-lg group-hover:scale-110 transition-transform"
                                        :class="desktopSidebarOpen ? 'rotate-0' : 'rotate-180'"></i>
                                </button>
                            @endif
                        @endauth

                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ url('/') }}"
                                class="text-xl font-black text-indigo-700 flex items-center tracking-tighter">
                                <i class="fa-solid fa-square-poll-vertical mr-2"></i>
                                <span>KDAnalytiks</span>
                            </a>
                        </div>

                        <!-- Desktop Nav Links -->
                        @auth
                            @if(auth()->user()->hasVerifiedEmail())
                                <div class="hidden sm:ml-8 sm:flex sm:items-center">
                                    @php
                                        $roleValNav = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                                    @endphp

                                    <a href="{{ route($roleValNav . '.dashboard') }}"
                                        class="text-gray-500 hover:text-indigo-700 px-3 py-2 text-sm font-bold transition-colors">
                                        {{ __('Dashboard') }}
                                    </a>
                                </div>
                            @endif
                        @endauth
                    </div>

                    <div class="flex items-center">
                        <!-- Desktop User Info -->
                        @auth
                            <div class="hidden sm:flex sm:items-center">
                                @php
                                    $roleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                                    $displayName = auth()->user()->name;
                                    if ($roleVal === 'organization' && auth()->user()->organization) {
                                        $displayName = auth()->user()->organization->name;
                                    } elseif ($roleVal === 'independent' && auth()->user()->independent) {
                                        $displayName = auth()->user()->independent->name;
                                    }
                                @endphp
                                <span class="text-sm text-gray-600 mr-4">{{ __('Welcome') }}, <span
                                        class="font-medium text-gray-900">{{ $displayName }}</span></span>

                                <!-- Language Picker (Auth) -->
                                <div class="relative mr-4" x-data="{ open: false }">
                                    <button @click="open = !open"
                                        class="flex items-center text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-indigo-600 transition-colors">
                                        <i class="fa-solid fa-globe mr-2"></i>
                                        <span>{{ app()->getLocale() }}</span>
                                    </button>

                                    <div x-show="open" @click.away="open = false" x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        class="absolute right-0 mt-2 w-40 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 z-[100] border border-gray-100 overflow-hidden">
                                        <div class="py-1">
                                            @php
                                                $langs = [
                                                    'en' => ['name' => 'English', 'flag' => '🇬🇧'],
                                                    'sw' => ['name' => 'Kiswahili', 'flag' => '🇰🇪'],
                                                    'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
                                                    'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪'],
                                                    'es' => ['name' => 'Español', 'flag' => '🇪🇸'],
                                                    'ar' => ['name' => 'العربية', 'flag' => '🇸🇦'],
                                                    'zh' => ['name' => '中文', 'flag' => '🇨🇳'],
                                                ];
                                            @endphp
                                            @foreach($langs as $code => $lang)
                                                <a href="{{ route('locale.switch', $code) }}"
                                                    class="flex items-center px-4 py-2.5 text-[10px] font-bold text-gray-700 hover:bg-indigo-50 transition-colors {{ app()->getLocale() === $code ? 'text-indigo-600 bg-indigo-50/30' : '' }}">
                                                    <span class="mr-3">{{ $lang['flag'] }}</span>
                                                    <span>{{ $lang['name'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition-all font-black text-[11px] uppercase tracking-widest">
                                        <i class="fa-solid fa-power-off"></i> {{ __('Logout') }}
                                    </button>
                                </form>
                            </div>

                            <!-- Mobile menu button -->
                            @if(auth()->user()->hasVerifiedEmail())
                                <div class="flex items-center sm:hidden">
                                    <button type="button" onclick="toggleMobileMenu()"
                                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                                        <span class="sr-only">Open main menu</span>
                                        <i class="fa-solid fa-bars text-xl" id="menu-icon"></i>
                                    </button>
                                </div>
                            @endif
                        @else
                            <div class="flex items-center space-x-6">
                                <!-- Language Picker (Guest) -->
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open"
                                        class="flex items-center text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-indigo-600 transition-colors">
                                        <i class="fa-solid fa-globe mr-2"></i>
                                        <span>{{ app()->getLocale() }}</span>
                                    </button>

                                    <div x-show="open" @click.away="open = false" x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        class="absolute right-0 mt-2 w-40 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 z-[100] border border-gray-100 overflow-hidden">
                                        <div class="py-1">
                                            @php
                                                $langs = [
                                                    'en' => ['name' => 'English', 'flag' => '🇬🇧'],
                                                    'sw' => ['name' => 'Kiswahili', 'flag' => '🇰🇪'],
                                                    'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
                                                    'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪'],
                                                    'es' => ['name' => 'Español', 'flag' => '🇪🇸'],
                                                    'ar' => ['name' => 'العربية', 'flag' => '🇸🇦'],
                                                    'zh' => ['name' => '中文', 'flag' => '🇨🇳'],
                                                ];
                                            @endphp
                                            @foreach($langs as $code => $lang)
                                                <a href="{{ route('locale.switch', $code) }}"
                                                    class="flex items-center px-4 py-2.5 text-[10px] font-bold text-gray-700 hover:bg-indigo-50 transition-colors {{ app()->getLocale() === $code ? 'text-indigo-600 bg-indigo-50/30' : '' }}">
                                                    <span class="mr-3">{{ $lang['flag'] }}</span>
                                                    <span>{{ $lang['name'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <a href="{{ route('login') }}"
                                    class="text-sm font-bold text-indigo-600 hover:text-indigo-500">{{ __('Sign In') }}</a>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="sm:hidden" id="mobile-menu">
                <div class="pt-2 pb-3 space-y-1 px-4 border-t border-gray-100 bg-white shadow-lg">
                    @auth
                        @php
                            $roleValMob = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                        @endphp
                        <div class="py-3 mb-2 border-b border-gray-50">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">{{ __('ACCOUNT') }}</p>
                            <p class="text-sm font-bold text-gray-800">{{ $displayName }}</p>
                        </div>
                        @if(auth()->user()->hasVerifiedEmail())
                            <a href="{{ route($roleValMob . '.dashboard') }}"
                                class="block pl-3 pr-4 py-2 border-l-4 border-indigo-500 text-base font-medium text-indigo-700 bg-indigo-50 rounded-r-md">
                                {{ __('Dashboard') }}
                            </a>
                        @endif
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex items-center w-full px-3 py-2 text-base font-bold text-red-600 hover:bg-red-50 rounded-md">
                                    <i class="fa-solid fa-right-from-bracket mr-3"></i> {{ __('Sign Out') }}
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </nav>

        @php
            // Show sidebar for all authenticated pages except specific full-width ones (like taking a survey)
            // Also explicitly hide on landing, login, register, and email verification notice/verify pages
            $excludedRoutes = ['home', 'login', 'register', 'login.role', 'password.request', 'password.reset', 'surveys.show', 'surveys.submit', 'verification.notice', 'verification.verify', 'admin.login', 'organization.login', 'independent.login', 'respondent.login', 'admin.register', 'organization.register', 'independent.register', 'respondent.register'];
            $isWorkspace = auth()->check() && auth()->user()->hasVerifiedEmail() && !request()->routeIs($excludedRoutes);
        @endphp

        @if($isWorkspace || View::hasSection('sidebar'))
            <div class="workspace-layout">
                <!-- Sidebar Overlay (Mobile Only) -->
                <div class="sidebar-overlay flex xl:hidden" id="sidebar-overlay" x-show="desktopSidebarOpen" x-cloak
                    @click="desktopSidebarOpen = false" x-transition.opacity
                    style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index: 999;">
                </div>

                <!-- Sidebar -->
                <aside class="sidebar-pane custom-scrollbar" id="sidebar-pane" x-show="desktopSidebarOpen" x-transition
                    @if(request()->routeIs('surveys.create', 'surveys.edit')) style="display: none !important;" @endif>
                    <div class="sidebar-nav">
                        @if(View::hasSection('sidebar'))
                            @yield('sidebar')
                        @else
                            @include('layouts.partials.sidebar')
                        @endif
                    </div>
                </aside>

                <!-- Sub Sidebar (Contextual) -->
                @yield('sub_sidebar')

                <!-- Main Content -->
                <main class="content-pane custom-scrollbar pb-24 md:pb-0 flex-1">
                    <div class="flex-grow">
                        <!-- Global Session Alerts -->
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                            @if(session('success'))
                                <div
                                    class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center shadow-sm animate-fade-in-down">
                                    <i class="fa-solid fa-circle-check mr-3 text-lg"></i>
                                    <span class="font-medium">{{ session('success') }}</span>
                                </div>
                            @endif

                            @if(session('error'))
                                <div
                                    class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center shadow-sm animate-fade-in-down">
                                    <i class="fa-solid fa-circle-exclamation mr-3 text-lg"></i>
                                    <div class="flex-1">
                                        <span class="font-medium">{{ session('error') }}</span>
                                        @if(str_contains(session('error'), 'Upgrade Required'))
                                            <a href="#" class="ml-2 underline font-bold hover:text-red-800">View Plans
                                                &rightarrow;</a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        @yield('content')
                    </div>
                    @include('layouts.partials.footer')
                </main>

                <!-- Mobile Bottom Navigation (Visible only on small screens) -->
                @auth
                    @php
                        $roleValNav = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                    @endphp
                    <nav
                        class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex justify-around items-center h-16 z-50 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] pb-safe pt-2">
                        <a href="{{ route($roleValNav . '.dashboard') }}"
                            class="flex flex-col items-center justify-center w-full text-gray-500 hover:text-indigo-600 {{ request()->routeIs($roleValNav . '.dashboard') ? 'text-indigo-600' : '' }} transition-colors">
                            <i class="fa-solid fa-house mb-1 text-lg"></i>
                            <span class="text-[10px] font-bold">{{ __('Home') }}</span>
                        </a>
                        <a href="{{ route('surveys.index', ['status' => 'active']) }}"
                            class="flex flex-col items-center justify-center w-full text-gray-500 hover:text-indigo-600 {{ (request()->routeIs('surveys.index') && request('status') === 'active') ? 'text-indigo-600' : '' }} transition-colors">
                            <i class="fa-solid fa-layer-group mb-1 text-lg"></i>
                            <span class="text-[10px] font-bold">{{ __('Projects') }}</span>
                        </a>
                        <a href="{{ route('surveys.create') }}"
                            class="flex flex-col items-center justify-center w-full text-gray-500 hover:text-indigo-600 {{ request()->routeIs('surveys.create') ? 'text-indigo-600' : '' }} transition-colors relative">
                            <div
                                class="absolute -top-4 bg-indigo-600 text-white w-10 h-10 flex items-center justify-center rounded-full shadow-lg border-2 border-gray-50">
                                <i class="fa-solid fa-plus text-lg"></i>
                            </div>
                            <span class="text-[10px] font-bold mt-5">{{ __('Create') }}</span>
                        </a>
                        <a href="{{ route('research-proposal.index') }}"
                            class="flex flex-col items-center justify-center w-full text-gray-500 hover:text-indigo-600 {{ request()->routeIs('research-proposal.*') ? 'text-indigo-600' : '' }} transition-colors">
                            <i class="fa-solid fa-file-signature mb-1 text-lg"></i>
                            <span class="text-[10px] font-bold">{{ __('Report') }}</span>
                        </a>
                    </nav>
                @endauth
            </div>
        @else
            <!-- Default Layout for Non-Workspace Pages -->
            <main class="flex-grow py-8 px-4 sm:px-8 lg:px-12 overflow-y-auto">
                @yield('content')
            </main>

            <!-- Footer for Non-Workspace Pages -->
            <footer class="bg-gray-800 border-t border-gray-700">
                <div class="max-w-full mx-auto py-8 px-4 sm:px-8 lg:px-12 text-center text-sm text-gray-300">
                    <div class="mb-2">
                        <span class="font-bold text-white">KDAnalytiks</span> &copy; {{ date('Y') }}. All rights reserved.
                    </div>
                    <div>
                        +254 725 788 400 <span class="mx-2 text-gray-500">|</span>
                        Powered by <a href="https://www.kenpro.org" target="_blank"
                            class="font-semibold text-white hover:underline">KENPRO</a> <span
                            class="mx-2 text-gray-500">|</span>
                        <a href="mailto:infokdanalytiks@gmail.com"
                            class="hover:text-white transition-colors">infokdanalytiks@gmail.com</a>
                        <span class="mx-2 text-gray-500">|</span>
                        <a href="{{ route('privacy') }}"
                            class="hover:text-white transition-colors font-medium underline">Privacy Policy</a>
                    </div>
                </div>
            </footer>
        @endif
    </div>

    <x-agent-ui />

    @stack('scripts')

    <script>
        function urgeLogin(surveyUrl, isPaid, rewardAmount, currency) {
            let title = `{{ __('Sign in to Participate') }}`;
            let text = `{{ __('Join KDAnalytiks to contribute your insights.') }}`;

            if (isPaid) {
                title = `{{ __('Earn') }} ` + rewardAmount + ' ' + currency;
                text = `{{ __('Register or Login to receive this reward and access your wallet.') }}`;
            }

            Swal.fire({
                title: '<span class="text-indigo-600">' + title + '</span>',
                html: '<p class="text-sm text-gray-600 font-medium">' + text + '</p>',
                icon: 'info',
                showCloseButton: true,
                showCancelButton: false,
                showDenyButton: true,
                confirmButtonText: `{{ __('Login') }}`,
                denyButtonText: `{{ __('Register') }}`,
                confirmButtonColor: '#4f46e5',
                denyButtonColor: '#6366f1',
                customClass: {
                    popup: 'rounded-3xl border-none shadow-2xl',
                    title: 'text-2xl font-black tracking-tight',
                    confirmButton: 'rounded-xl px-8 py-3 text-xs font-black uppercase tracking-widest',
                    denyButton: 'rounded-xl px-8 py-3 text-xs font-black uppercase tracking-widest'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('login') }}?redirect=" + encodeURIComponent(surveyUrl);
                } else if (result.isDenied) {
                    window.location.href = "{{ route('register', ['role' => 'respondent']) }}?redirect=" + encodeURIComponent(surveyUrl);
                }
            });
        }
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('workspace', {
                activeTab: '{{ request('tab', 'overview') }}',
                setTab(tab) {
                    this.activeTab = tab;
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tab);
                    window.history.pushState({}, '', url);
                },
                scrollTo(id) {
                    const el = document.getElementById(id);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
            window.addEventListener('popstate', () => {
                const urlParams = new URLSearchParams(window.location.search);
                const tab = urlParams.get('tab');
                if (tab) Alpine.store('workspace').activeTab = tab;
            });
        });
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const icon = document.getElementById('menu-icon');
            const isOpen = menu.classList.contains('open');

            if (isOpen) {
                menu.classList.remove('open');
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            } else {
                menu.classList.add('open');
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
            }
        }


        // --- Mobile Integration JS ---

        // 1. Keyboard Handling
        if (typeof Capacitor !== 'undefined' && Capacitor.isPluginAvailable('Keyboard')) {
            window.addEventListener('keyboardWillShow', (e) => {
                const activeElement = document.activeElement;
                if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
                    setTimeout(() => {
                        activeElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            });
        }

        // 2. Pull-to-Refresh (Standard Implementation)
        let touchStart = 0;
        let pullDelta = 0;
        const ptr = document.getElementById('ptr-indicator');

        window.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                touchStart = e.touches[0].pageY;
            } else {
                touchStart = 0;
            }
        }, { passive: true });

        window.addEventListener('touchmove', (e) => {
            if (touchStart > 0) {
                const currentY = e.touches[0].pageY;
                pullDelta = Math.min(60, currentY - touchStart);
                if (pullDelta > 0) {
                    ptr.style.transform = `translateY(${pullDelta}px)`;
                }
            }
        }, { passive: true });

        window.addEventListener('touchend', () => {
            if (pullDelta >= 60) {
                ptr.style.transform = `translateY(60px)`;
                window.location.reload();
            } else {
                ptr.style.transform = `translateY(0)`;
            }
            pullDelta = 0;
            touchStart = 0;
        });

    </script>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js?v=5').then(registration => {
                    console.log('SW registered: ', registration);
                    registration.update(); // Force update check
                }).catch(registrationError => {
                    console.log('SW registration failed: ', registrationError);
                });
            });
        }
    </script>
</body>

</html>