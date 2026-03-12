@extends('layouts.app')

@section('title', 'Login Selection')

@section('content')
<div class="min-h-[70vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Welcome Back</h2>
        <p class="mt-2 text-sm text-gray-600">
            Please select your account type to sign in
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-4xl px-4">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Organization -->
            <a href="{{ route('login.role', ['role' => 'organization']) }}" class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center transition-all hover:shadow-xl hover:-translate-y-1 hover:border-indigo-200">
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors mb-6">
                    <i class="fa-solid fa-building text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Organization</h3>
                <p class="mt-2 text-sm text-gray-500">For companies and institutions managing surveys.</p>
            </a>

            <!-- Independent Researcher -->
            <a href="{{ route('login.role', ['role' => 'independent']) }}" class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center transition-all hover:shadow-xl hover:-translate-y-1 hover:border-indigo-200">
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-2xl bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors mb-6">
                    <i class="fa-solid fa-user-graduate text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Researcher</h3>
                <p class="mt-2 text-sm text-gray-500">For individual academic or project research.</p>
            </a>

            <!-- Respondent -->
            <a href="{{ route('login.role', ['role' => 'respondent']) }}" class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center transition-all hover:shadow-xl hover:-translate-y-1 hover:border-indigo-200">
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-2xl bg-green-50 text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors mb-6">
                    <i class="fa-solid fa-clipboard-check text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Respondent</h3>
                <p class="mt-2 text-sm text-gray-500">For users participating and answering surveys.</p>
            </a>

            <!-- Admin -->
            <a href="{{ route('login.role', ['role' => 'admin']) }}" class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center transition-all hover:shadow-xl hover:-translate-y-1 hover:border-indigo-200">
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-2xl bg-gray-50 text-gray-600 group-hover:bg-gray-800 group-hover:text-white transition-colors mb-6">
                    <i class="fa-solid fa-user-shield text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Administrator</h3>
                <p class="mt-2 text-sm text-gray-500">Platform management and configuration.</p>
            </a>
        </div>
    </div>
</div>
@endsection
