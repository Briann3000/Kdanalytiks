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

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-5xl px-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Organization -->
                <a href="{{ route('login.role', ['role' => 'organization']) }}"
                    class="group relative bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center transition-all hover:shadow-2xl hover:-translate-y-2 hover:border-indigo-200">
                    <div
                        class="mx-auto h-20 w-20 flex items-center justify-center rounded-[2rem] bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-500 mb-8 transform group-hover:rotate-6">
                        <i class="fa-solid fa-building text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Organization</h3>
                    <p class="mt-4 text-sm text-gray-500 leading-relaxed font-medium">For companies and institutions
                        managing large-scale research projects</p>
                    <div class="mt-8 flex justify-center">
                        <span
                            class="text-indigo-600 text-xs font-black uppercase tracking-widest group-hover:translate-x-2 transition-transform">Get
                            Started <i class="fa-solid fa-arrow-right ml-2"></i></span>
                    </div>
                </a>

                <!-- Independent Researcher -->
                <a href="{{ route('login.role', ['role' => 'independent']) }}"
                    class="group relative bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center transition-all hover:shadow-2xl hover:-translate-y-2 hover:border-purple-200">
                    <div
                        class="mx-auto h-20 w-20 flex items-center justify-center rounded-[2rem] bg-purple-50 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-all duration-500 mb-8 transform group-hover:-rotate-6">
                        <i class="fa-solid fa-user-graduate text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Researcher</h3>
                    <p class="mt-4 text-sm text-gray-500 leading-relaxed font-medium">For academic and project work</p>
                    <div class="mt-8 flex justify-center">
                        <span
                            class="text-purple-600 text-xs font-black uppercase tracking-widest group-hover:translate-x-2 transition-transform">Get
                            Started <i class="fa-solid fa-arrow-right ml-2"></i></span>
                    </div>
                </a>

                <!-- Respondent -->
                <a href="{{ route('login.role', ['role' => 'respondent']) }}"
                    class="group relative bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center transition-all hover:shadow-2xl hover:-translate-y-2 hover:border-green-200">
                    <div
                        class="mx-auto h-20 w-20 flex items-center justify-center rounded-[2rem] bg-green-50 text-green-600 group-hover:bg-green-600 group-hover:text-white transition-all duration-500 mb-8 transform group-hover:rotate-6">
                        <i class="fa-solid fa-clipboard-check text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Respondent</h3>
                    <p class="mt-4 text-sm text-gray-500 leading-relaxed font-medium">Share feedback and earn</p>
                    <div class="mt-8 flex justify-center">
                        <span
                            class="text-green-600 text-xs font-black uppercase tracking-widest group-hover:translate-x-2 transition-transform">Start
                            Earning <i class="fa-solid fa-arrow-right ml-2"></i></span>
                    </div>
                </a>
            </div>
        </div>
    </div>
@endsection