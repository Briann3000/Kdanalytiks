@extends('layouts.app')

@section('title', 'Set New Password')

@section('content')
    <div class="min-h-[70vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Set New Password</h2>
            <p class="mt-2 text-sm text-gray-600">
                Please enter your new password below.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">
                <form class="space-y-6" action="{{ route('password.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email address
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-envelope text-gray-400"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                class="bg-gray-50 focus:ring-[#2271b1] focus:border-[#2271b1] block w-full pl-10 sm:text-sm border-gray-300 rounded-md @error('email') border-red-300 text-red-900 placeholder-red-300 focus:outline-none focus:ring-red-500 focus:border-red-500 @enderror"
                                value="{{ $email ?? old('email') }}" readonly>
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-600" id="email-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            New Password
                        </label>
                        <input id="password" name="password" type="password" required
                            class="focus:ring-[#2271b1] focus:border-[#2271b1] block w-full pl-10 pr-10 sm:text-sm border-gray-300 rounded-md @error('password') border-red-300 text-red-900 placeholder-red-300 focus:outline-none focus:ring-red-500 focus:border-red-500 @enderror">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button" onclick="togglePassword('password', 'password-icon')"
                                class="text-gray-400 hover:text-[#2271b1] focus:outline-none">
                                <i id="password-icon" class="fa-solid fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600" id="password-error">{{ $message }}</p>
                    @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                    Confirm New Password
                </label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="focus:ring-[#2271b1] focus:border-[#2271b1] block w-full pl-10 pr-10 sm:text-sm border-gray-300 rounded-md">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <button type="button" onclick="togglePassword('password_confirmation', 'conf-password-icon')"
                        class="text-gray-400 hover:text-[#2271b1] focus:outline-none">
                        <i id="conf-password-icon" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <div>
            <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#2271b1] hover:bg-[#135e96] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2271b1]">
                Reset Password
            </button>
        </div>
        </form>
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