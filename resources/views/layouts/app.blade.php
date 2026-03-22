<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KMSurveyTool') }}</title>

    <!-- Tailwind CSS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Alpine.js + Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
            overflow: hidden;
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
            overflow-x: hidden !important;
            scrollbar-width: thin;
            transition: transform 0.3s ease-in-out, width 0.3s ease;
        }

        @media (max-width: 768px) {
            .sidebar-pane {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                transform: translateX(-100%);
                width: 260px;
                z-index: 1000;
            }
            .sidebar-pane.mobile-open {
                transform: translateX(0);
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

        /* Remove wrapper scrollbar styles as we now apply them directly to sidebar-pane */

        /* Essential logic for JS fixed flyouts */
        .flyout-menu {
            position: fixed !important;
            width: auto;
            min-width: 180px;
            background: white;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 9999;
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
            overflow-x: hidden;
            /* Prevent horizontal page wobble while allowing vertical content scroll */
        }

        @media (max-width: 1023px) {
            .sidebar-pane {
                /* Removed drawer transition as per request */
            }

            .sidebar-overlay {
                display: none !important;
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
    </style>
</head>

<body class="font-sans antialiased bg-gray-50 text-gray-900" x-data="{ mobileMenuOpen: false }">
    <div class="min-h-screen flex flex-col" x-data="{ sidebarOpen: true, desktopSidebarOpen: window.innerWidth > 1024 }"
        @close-sidebar.window="desktopSidebarOpen = false"
        @open-sidebar.window="desktopSidebarOpen = true">
        <!-- Navigation Bar -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-full mx-auto px-4 sm:px-8 lg:px-12">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <!-- Sidebar Toggle (Youtube-style) -->
                        @auth
                            @php
                                $isReportPage = request()->routeIs('surveys.report', 'surveys.responses') || request()->is('*/report', '*/responses*');
                            @endphp

                            <!-- Main Sidebar Toggle -->
                            <button type="button" @click="desktopSidebarOpen = !desktopSidebarOpen"
                                onclick="if(window.innerWidth < 1024) toggleSidebar()"
                                class="mr-3 p-2 rounded-xl bg-slate-50 border border-slate-200 text-indigo-700 hover:bg-slate-100 hover:border-slate-300 shadow-sm transition-all flex items-center justify-center w-10 h-10 group">
                                <i class="fa-solid fa-bars-staggered text-lg group-hover:scale-110 transition-transform"
                                    :class="desktopSidebarOpen ? 'rotate-0' : 'rotate-180'"></i>
                            </button>
                        @endauth

                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ url('/') }}"
                                class="text-xl font-black text-indigo-700 flex items-center tracking-tighter">
                                <i class="fa-solid fa-square-poll-vertical mr-2"></i>
                                <span>KMSurveyTool</span>
                            </a>
                        </div>

                        <!-- Desktop Nav Links -->
                        @auth
                            <div class="hidden sm:ml-8 sm:flex sm:items-center">
                                @php
                                    $roleValNav = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                                @endphp

                                <a href="{{ route($roleValNav . '.dashboard') }}"
                                    class="text-gray-500 hover:text-indigo-700 px-3 py-2 text-sm font-bold transition-colors">
                                    Dashboard
                                </a>
                            </div>
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
                                <span class="text-sm text-gray-600 mr-4">Welcome, <span
                                        class="font-medium text-gray-900">{{ $displayName }}</span></span>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition-all font-black text-[11px] uppercase tracking-widest">
                                        <i class="fa-solid fa-power-off"></i> Logout
                                    </button>
                                </form>
                            </div>

                            <!-- Mobile menu button -->
                            <div class="flex items-center sm:hidden">
                                <button type="button" onclick="toggleMobileMenu()"
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                                    <span class="sr-only">Open main menu</span>
                                    <i class="fa-solid fa-bars text-xl" id="menu-icon"></i>
                                </button>
                            </div>
                        @else
                            <a href="{{ route('login') }}"
                                class="text-sm font-bold text-indigo-600 hover:text-indigo-500">Sign In</a>
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
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Account</p>
                            <p class="text-sm font-bold text-gray-800">{{ $displayName }}</p>
                        </div>
                        <a href="{{ route($roleValMob . '.dashboard') }}"
                            class="block pl-3 pr-4 py-2 border-l-4 border-indigo-500 text-base font-medium text-indigo-700 bg-indigo-50 rounded-r-md">
                            Dashboard
                        </a>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex items-center w-full px-3 py-2 text-base font-bold text-red-600 hover:bg-red-50 rounded-md">
                                    <i class="fa-solid fa-right-from-bracket mr-3"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </nav>

        @php
            // Show sidebar for all authenticated pages except specific full-width ones (like taking a survey)
            // Also explicitly hide on landing, login, register
            $excludedRoutes = ['home', 'login', 'register', 'login.role', 'password.request', 'password.reset', 'surveys.show', 'surveys.submit'];
            $isWorkspace = !request()->routeIs($excludedRoutes);
        @endphp

        @if($isWorkspace || View::hasSection('sidebar'))
            <div class="workspace-layout">
                <!-- Sidebar Overlay (Mobile Only) -->
                <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

                <!-- Sidebar -->
                <aside class="sidebar-pane custom-scrollbar" id="sidebar-pane" x-show="desktopSidebarOpen" x-transition>
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
                <main class="content-pane custom-scrollbar">
                    @yield('content')
                </main>
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
                        <span class="font-bold text-white">KMSurveyTool</span> &copy; {{ date('Y') }}. All rights reserved.
                    </div>
                    <div>
                        +254 725 788 400 <span class="mx-2 text-gray-500">|</span>
                        Powered by <span class="font-semibold text-white">PRC™ Consulting</span> <span
                            class="mx-2 text-gray-500">|</span>
                        <a href="mailto:kmsurveytool@gmail.com"
                            class="hover:text-white transition-colors">kmsurveytool@gmail.com</a>
                    </div>
                </div>
            </footer>
        @endif
    </div>

    <x-agent-ui />

    @stack('scripts')

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

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar-pane');
            const overlay = document.getElementById('sidebar-overlay');
            const icon = document.getElementById('sidebar-toggle-icon');

            if (sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars-staggered');
            } else {
                sidebar.classList.add('open');
                overlay.classList.add('open');
                icon.classList.remove('fa-bars-staggered');
                icon.classList.add('fa-xmark');
            }
        }
    </script>
</body>

</html>