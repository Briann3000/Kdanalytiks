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

    @stack('styles')
    @yield('head')
</head>

<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation Bar -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ url('/') }}" class="text-xl font-bold text-indigo-600">
                                <i class="fa-solid fa-square-poll-vertical mr-2"></i>KMSurveyTool
                            </a>
                        </div>
                        @auth
                            <div class="ml-4 flex items-center sm:ml-6 sm:space-x-8">
                                @php
                                    $roleValNav = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                                @endphp
                                <a href="{{ route($roleValNav . '.dashboard') }}"
                                    class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Dashboard
                                </a>
                            </div>
                        @endauth
                    </div>
                    <div class="flex items-center">
                        @auth
                            @php
                                $roleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                                $displayName = auth()->user()->name;

                                if ($roleVal === 'organization' && auth()->user()->organization) {
                                    $displayName = auth()->user()->organization->name;
                                } elseif ($roleVal === 'independent' && auth()->user()->independent) {
                                    $displayName = auth()->user()->independent->name;
                                }
                            @endphp
                            <span class="text-sm text-gray-700 mr-4">Welcome, {{ $displayName }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">
                                    Logout
                                </button>
                            </form>
                        @endauth
                    </div>
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

    @stack('scripts')
</body>

</html>