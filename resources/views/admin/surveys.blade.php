@extends('layouts.app')

@section('title', 'Manage Surveys')

@section('content')
@php /** @var \Illuminate\Pagination\LengthAwarePaginator $surveys */ @endphp
<div class="px-4 sm:px-0">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 leading-tight">Survey Management</h2>
            <p class="mt-1 text-sm text-gray-500">Oversee all surveys across the platform.</p>
        </div>
        <div>
            <a href="{{ route('admin.surveys.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                <i class="fa-solid fa-plus mr-2"></i> Create New Survey
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg border border-gray-100 p-6 mb-8">
        <form action="{{ route('admin.surveys.index') }}" method="GET" class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div class="sm:col-span-4">
                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                    </div>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" placeholder="Survey title...">
                </div>
            </div>

            <div class="sm:col-span-2 flex items-end">
                <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Search Surveys
                </button>
            </div>
        </form>
    </div>

    <!-- Surveys Table -->
    <div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden mb-8">
        <div class="table-container">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Survey Detail</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner / Creator</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responses</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($surveys as $survey)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ $survey->title }}</div>
                            <div class="text-xs text-gray-500">{{ $survey->category }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($survey->organization)
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 h-6 w-6 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                        <i class="fa-solid fa-building text-[10px] text-green-600"></i>
                                    </span>
                                    <span class="text-xs font-medium text-gray-900">{{ $survey->organization->name }}</span>
                                </div>
                            @elseif($survey->independent)
                                <div class="flex items-center">
                                    <span class="flex-shrink-0 h-6 w-6 bg-purple-100 rounded-full flex items-center justify-center mr-2">
                                        <i class="fa-solid fa-user-graduate text-[10px] text-purple-600"></i>
                                    </span>
                                    <span class="text-xs font-medium text-gray-900">{{ $survey->independent->name }}</span>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Platform Admin</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 uppercase tracking-wider">
                                {{ ucfirst($survey->type instanceof \BackedEnum ? $survey->type->value : $survey->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php 
                                $statusVal = $survey->status instanceof \BackedEnum ? $survey->status->value : $survey->status; 
                                $statusLabel = $statusVal === 'pending_approval' ? 'Pending Approval' : ucfirst($statusVal);
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusVal === 'active' ? 'bg-green-100 text-green-800' : ($statusVal === 'pending_approval' ? 'bg-yellow-100 text-yellow-800' : ($statusVal === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800')) }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-indigo-600">
                                @if(($survey->responses_count ?? 0) > 0)
                                    <a href="{{ route('surveys.report', $survey) }}" class="hover:underline hover:text-indigo-900" title="View Question Analysis">
                                        {{ $survey->responses_count }} <i class="fa-solid fa-chart-pie ml-1 text-[10px]"></i>
                                    </a>
                                @else
                                    0
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                            {{ $survey->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($statusVal === 'pending_approval')
                                <form action="{{ route('admin.surveys.approve', $survey) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900 font-bold mr-3">Approve</button>
                                </form>
                            @endif
                            
                            @if($statusVal === 'active')
                                <form action="{{ route('admin.surveys.deactivate', $survey) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-bold">Deactivate</button>
                                </form>
                            @elseif($statusVal === 'closed')
                                 <form action="{{ route('admin.surveys.approve', $survey) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-bold">Re-activate</button>
                                </form>
                            @endif

                            @if($survey->created_by === auth()->id())
                                <div class="inline-flex items-center ml-4 border-l pl-4 border-gray-200">
                                    @if($statusVal === 'draft')
                                        <form action="{{ route('surveys.publish', $survey) }}" method="POST" class="inline-block" title="Publish Survey">
                                            @csrf
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-bold mr-3"><i class="fa-solid fa-paper-plane"></i> Publish</button>
                                        </form>
                                    @endif
                                    <button type="button" onclick="openInviteModal('{{ route('surveys.invite', $survey) }}', '{{ addslashes($survey->title) }}')" class="text-blue-600 hover:text-blue-900 font-bold mr-3" title="Send Email Invitations">
                                        <i class="fa-solid fa-envelope"></i>
                                    </button>
                                    <a href="{{ route('surveys.edit', $survey) }}" class="text-indigo-600 hover:text-indigo-900 font-bold mr-3" title="Edit your survey">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('surveys.destroy', $survey) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this survey?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-bold" title="Delete your survey">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">No surveys found matching your criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ method_exists($surveys, 'withQueryString') ? $surveys->withQueryString()->links() : $surveys->links() }}
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
            <i class="fa-solid fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>
</div>

<!-- Send Invitation Modal -->
<div id="inviteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeInviteModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="fa-solid fa-envelope text-indigo-600"></i>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Invite Participants
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 mb-4">
                            Send direct email invitations for "<span id="inviteSurveyTitle" class="font-bold"></span>". Separate multiple emails with commas.
                        </p>
                        <form id="inviteForm" method="POST" action="">
                            @csrf
                            <textarea name="emails" rows="4" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md p-2" placeholder="john@example.com, jane@example.com"></textarea>
                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    Send Invitations
                                </button>
                                <button type="button" onclick="closeInviteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openInviteModal(actionUrl, title) {
        document.getElementById('inviteSurveyTitle').innerText = title;
        document.getElementById('inviteForm').action = actionUrl;
        document.getElementById('inviteModal').classList.remove('hidden');
    }

    function closeInviteModal() {
        document.getElementById('inviteModal').classList.add('hidden');
    }
</script>
@endsection
