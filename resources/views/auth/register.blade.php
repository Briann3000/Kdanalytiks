@extends('layouts.app')

@php
    $roleName = in_array($role, ['independent', 'researcher']) ? 'researcher' : $role;
    $roleLabel = $roleName === 'researcher' ? 'Researcher' : ucfirst($roleName);
@endphp

@section('title', __($roleLabel . ' Registration'))

@section('content')
    <div class="min-h-[80vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                <i class="fa-solid fa-user-plus text-xl"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">{{ __($roleLabel . ' Registration') }}</h2>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('Join KDAnalytiks and start building') }}
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">
                @if (session('success'))
                    <div class="rounded-md bg-green-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fa-solid fa-circle-check text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @php /** @var \Illuminate\Support\ViewErrorBag $errors */ @endphp
                @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fa-solid fa-circle-xmark text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    {{ __('There were errors with your registration') }}
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul role="list" class="list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <form class="space-y-6" method="POST">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ request('redirect') }}">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Full Name') }}</label>
                            <div class="mt-1">
                                <input id="name" name="name" type="text" required
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="email"
                                class="block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
                            <div class="mt-1">
                                <input id="email" name="email" type="email" required
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="phone_number"
                                class="block text-sm font-medium text-gray-700">{{ __('Phone Number (Optional)') }}</label>
                            <div class="mt-1">
                                <input id="phone_number" name="phone_number" type="text"
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        @if($role == 'organization')
                            <div class="sm:col-span-6">
                                <label for="organization_name"
                                    class="block text-sm font-medium text-gray-700">{{ __('Organization Name') }}</label>
                                <div class="mt-1">
                                    <input id="organization_name" name="organization_name" type="text" required
                                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        @elseif($role == 'independent')
                            <div class="sm:col-span-6">
                                <label for="institution"
                                    class="block text-sm font-medium text-gray-700">{{ __('Institution (Optional)') }}</label>
                                <div class="mt-1">
                                    <input id="institution" name="institution" type="text"
                                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="sm:col-span-6">
                                <label for="research_area"
                                    class="block text-sm font-medium text-gray-700">{{ __('Research Area (Optional)') }}</label>
                                <div class="mt-1">
                                    <input id="research_area" name="research_area" type="text"
                                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        @endif

                        <div class="sm:col-span-3">
                            <label for="password"
                                class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input id="password" name="password" type="password" required
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pr-10">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button type="button" onclick="togglePassword('password', 'password-icon')"
                                        class="text-gray-400 hover:text-indigo-600 focus:outline-none">
                                        <i id="password-icon" class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="password_confirmation"
                                class="block text-sm font-medium text-gray-700">{{ __('Confirm Password') }}</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input id="password_confirmation" name="password_confirmation" type="password" required
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pr-10">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button type="button"
                                        onclick="togglePassword('password_confirmation', 'conf-password-icon')"
                                        class="text-gray-400 hover:text-indigo-600 focus:outline-none">
                                        <i id="conf-password-icon" class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <i class="fa-solid fa-user-plus mt-0.5 mr-2"></i> {{ __('Register Account') }}
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">{{ __('Already have an account?') }}</span>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <a href="{{ route('login.role', ['role' => $roleName, 'redirect' => request('redirect')]) }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            {{ __('Sign in to your :role account', ['role' => __($roleLabel)]) }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>