@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
    @php /** @var \Illuminate\Pagination\LengthAwarePaginator $users */ @endphp
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 leading-tight">User Management</h2>
                <p class="mt-1 text-sm text-gray-500">Monitor and manage all system users and their access levels.</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.users.create') }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-indigo-100">
                    <i class="fa-solid fa-plus mr-2"></i> Create New User
                </a>
                <span
                    class="inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white">
                    Total: {{ $users->total() }} Users
                </span>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white shadow rounded-lg border border-gray-100 p-6 mb-8">
            <form action="{{ route('admin.users.index') }}" method="GET"
                class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                            placeholder="Name or email...">
                    </div>
                </div>

                <div class="sm:col-span-1">
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select name="role" id="role"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="all">All Roles</option>
                        @foreach(['admin', 'independent', 'organization', 'respondent'] as $r)
                            <option value="{{ $r }}" {{ request('role') == $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-1">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="active" {{ (!request()->filled('status') || request('status') == 'active') ? 'selected' : '' }}>Active Only</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All (Incl. Suspended)</option>
                    </select>
                </div>

                <div class="sm:col-span-1 flex items-end">
                    <button type="submit"
                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        @if(session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6 border border-green-200 shadow-sm">
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

        <!-- Users Table -->
        <div class="bg-white shadow rounded-lg border border-gray-100 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Info
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Joined
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="h-10 w-10 flex-shrink-0 bg-indigo-50 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-user text-indigo-400"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ ucfirst($user->role instanceof \BackedEnum ? $user->role->value : $user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php $statusVal = $user->status instanceof \BackedEnum ? $user->status->value : $user->status; @endphp
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusVal === 'active' ? 'bg-green-100 text-green-800' : ($statusVal === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($statusVal) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if($user->role != 'admin')
                                    <form action="{{ route('admin.users.status', $user) }}" method="POST" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="status"
                                            value="{{ $statusVal === 'active' ? 'suspended' : 'active' }}">
                                        @if($statusVal === 'active')
                                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 rounded text-[10px] font-bold uppercase hover:bg-red-100 transition-colors">
                                                <i class="fa-solid fa-user-slash mr-1"></i> Suspend
                                            </button>
                                        @else
                                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-green-50 text-green-700 rounded text-[10px] font-bold uppercase hover:bg-green-100 transition-colors">
                                                <i class="fa-solid fa-user-check mr-1"></i> Activate
                                            </button>
                                        @endif
                                    </form>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $users->withQueryString()->links() }}
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>
    </div>
@endsection