<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KMSurveyTool') }}</title>

    <!-- Tailwind CSS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Alpine.js -->
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

        /* Prevent content cut-off */
        html, body {
            overflow-x: hidden;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
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
                padding-bottom: 80px; /* Space for chatbot button */
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
    </style>
</head>

<body class="font-sans antialiased bg-gray-50 text-gray-900 overflow-x-hidden">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation Bar -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ url('/') }}" class="text-xl font-bold text-indigo-700 flex items-center">
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
                                <span class="text-sm text-gray-600 mr-4">Welcome, <span class="font-medium text-gray-900">{{ $displayName }}</span></span>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-bold text-red-600 hover:text-red-700 transition-colors">
                                        Logout
                                    </button>
                                </form>
                            </div>

                            <!-- Mobile menu button -->
                            <div class="flex items-center sm:hidden">
                                <button type="button" onclick="toggleMobileMenu()" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                                    <span class="sr-only">Open main menu</span>
                                    <i class="fa-solid fa-bars text-xl" id="menu-icon"></i>
                                </button>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500">Sign In</a>
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
                                <button type="submit" class="flex items-center w-full px-3 py-2 text-base font-bold text-red-600 hover:bg-red-50 rounded-md">
                                    <i class="fa-solid fa-right-from-bracket mr-3"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="flex-grow">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 border-t border-gray-700 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-300">
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
    </div>

    <x-agent-ui />

    @stack('scripts')
    <script>
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
    </script>
</body>

</html>