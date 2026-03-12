@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">Analytics Dashboard</h2>
                <p class="mt-1 text-sm text-gray-500">Consolidated analytics and data visualizations.</p>
            </div>
            <div>
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Surveys by Category -->
            <div class="bg-white shadow rounded-lg border border-gray-100 p-6 lg:col-span-1">
                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center"><i
                        class="fa-solid fa-chart-pie mr-2 text-indigo-500"></i> Surveys by Category</h3>
                <div class="relative h-64 w-full">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Responses Over Time -->
            <div class="bg-white shadow rounded-lg border border-gray-100 p-6 lg:col-span-1">
                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center"><i
                        class="fa-solid fa-chart-line mr-2 text-green-500"></i> Response Trends (7 Days)</h3>
                <div class="relative h-64 w-full">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Platform Statistics -->
            <div class="bg-white shadow rounded-lg border border-gray-100 p-6 lg:col-span-1">
                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center"><i
                        class="fa-solid fa-server mr-2 text-orange-500"></i> Platform Statistics</h3>
                <ul class="divide-y divide-gray-200">
                    <li class="py-4 flex justify-between items-center">
                        <span class="text-gray-600 font-medium">Total Surveys</span>
                        <span class="text-xl font-bold text-gray-900">{{ $totalSurveys }}</span>
                    </li>
                    <li class="py-4 flex justify-between items-center">
                        <span class="text-gray-600 font-medium">Total Responses</span>
                        <span class="text-xl font-bold text-gray-900">{{ $totalResponses }}</span>
                    </li>
                    <li class="py-4 flex justify-between items-center">
                        <span class="text-gray-600 font-medium">Organizations</span>
                        <span class="text-xl font-bold text-gray-900">{{ $totalOrganizations }}</span>
                    </li>
                    <li class="py-4 flex justify-between items-center">
                        <span class="text-gray-600 font-medium">Respondents</span>
                        <span class="text-xl font-bold text-gray-900">{{ $totalRespondents }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Category Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryData = @json($categoryStats);

            new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: categoryData.map(item => item.category),
                    datasets: [{
                        data: categoryData.map(item => item.count),
                        backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Trend Chart
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            const trendData = @json($responseTrends);

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendData.map(item => item.date),
                    datasets: [{
                        label: 'Responses',
                        data: trendData.map(item => item.count),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
    </script>
@endpush