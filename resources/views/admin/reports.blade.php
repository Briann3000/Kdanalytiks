@extends('layouts.app')

@section('title', 'System Reports')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">System Reports & Analytics</h2>
                <p class="mt-1 text-sm text-gray-500">Comprehensive breakdown of platform usage and distribution.</p>
            </div>
            <div>
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </div>

        <!-- Top Level Stats -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
            <div class="bg-white p-6 shadow rounded-lg border border-gray-100 flex items-center">
                <div class="p-3 bg-zinc-200 rounded-lg text-[#2271b1] mr-4">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Users</p>
                    <h3 class="text-2xl font-bold text-gray-900">{{ $totalUsers }}</h3>
                </div>
            </div>
            <div class="bg-white p-6 shadow rounded-lg border border-gray-100 flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg text-blue-600 mr-4">
                    <i class="fa-solid fa-file-invoice text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Surveys</p>
                    <h3 class="text-2xl font-bold text-gray-900">{{ $totalSurveys }}</h3>
                </div>
            </div>
            <div class="bg-white p-6 shadow rounded-lg border border-gray-100 flex items-center">
                <div class="p-3 bg-green-100 rounded-lg text-green-600 mr-4">
                    <i class="fa-solid fa-chart-bar text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Responses</p>
                    <h3 class="text-2xl font-bold text-gray-900">{{ $totalResponses }}</h3>
                </div>
            </div>
        </div>

        <!-- Distributions -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Users by Role -->
            <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">User Distribution by Role</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($usersByRole as $role => $count)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                    {{ ucfirst($role) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-[#2271b1]">
                                    {{ $count }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Surveys by Status -->
            <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Survey Status Breakdown</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($surveysByStatus as $status => $count)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                    {{ ucfirst($status) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-blue-600">
                                    {{ $count }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection