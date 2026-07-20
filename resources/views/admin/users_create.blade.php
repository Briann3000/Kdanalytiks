@extends('layouts.app')

@section('title', __('Create User'))

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-0">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ __('Create New User') }}</h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Add a new user to the system with a specific role and access level.') }}
            </p>
        </div>

        @if($errors->any())
            <div class="rounded-md bg-red-50 p-4 mb-6 border border-red-200">
                <div class="flex">
                    <i class="fa-solid fa-circle-exclamation text-red-400 mt-0.5"></i>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">{{ __('Please fix the following errors:') }}</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
            <form action="{{ route('admin.users.store') }}" method="POST" class="p-8 space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name"
                            class="block text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('Full Name') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-400/50 rounded-md py-2.5 px-4 focus:ring-[#2271b1] focus:border-[#2271b1] transition-all shadow-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email"
                            class="block text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('Email Address') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-400/50 rounded-md py-2.5 px-4 focus:ring-[#2271b1] focus:border-[#2271b1] transition-all shadow-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="role"
                            class="block text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('Role') }}</label>
                        <select name="role" id="role" required
                            class="mt-1 block w-full pl-3 pr-10 py-2.5 text-base border-2 border-gray-400/50 focus:outline-none focus:ring-[#2271b1] focus:border-[#2271b1] sm:text-sm rounded-md transition-all shadow-sm">
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" {{ old('role') == $role->value ? 'selected' : '' }}>
                                    {{ __($role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="password"
                            class="block text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('Password') }}</label>
                        <input type="password" name="password" id="password" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-400/50 rounded-md py-2.5 px-4 focus:ring-[#2271b1] focus:border-[#2271b1] transition-all shadow-sm">
                    </div>

                    <div>
                        <label for="password_confirmation"
                            class="block text-sm font-bold text-gray-700 uppercase tracking-wider">{{ __('Confirm Password') }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="mt-1 block w-full sm:text-sm border-2 border-gray-400/50 rounded-md py-2.5 px-4 focus:ring-[#2271b1] focus:border-[#2271b1] transition-all shadow-sm">
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('admin.users.index') }}"
                        class="text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">
                        {{ __('Cancel and return') }}
                    </a>
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2.5 border border-transparent shadow-sm text-sm font-black uppercase tracking-widest rounded-lg text-white bg-[#2271b1] hover:bg-[#135e96] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2271b1] shadow-zinc-200/50 transition-all">
                        {{ __('Create User Account') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection