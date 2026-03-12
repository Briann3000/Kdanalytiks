@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center px-4 sm:px-0">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Manage Surveys</h2>
        <p class="mt-1 text-sm text-gray-500">View and manage all your created surveys and draft schemas.</p>
    </div>
    <div>
        <a href="{{ route($role . '.surveys.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fa-solid fa-plus mr-2"></i> Create New Survey
        </a>
    </div>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-md">
    <ul role="list" class="divide-y divide-gray-200">
        @forelse ($surveys as $survey)
            <li>
                <div class="px-4 py-4 flex items-center sm:px-6 hover:bg-gray-50 transition-colors">
                    <div class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between">
                        <div class="truncate">
                            <div class="flex text-sm">
                                <p class="font-medium text-indigo-600 truncate mr-2">{{ $survey->title }}</p>
                                @php 
                                    $statusVal = $survey->status instanceof \BackedEnum ? $survey->status->value : $survey->status;
                                    $statusClasses = [
                                        'draft' => 'bg-gray-100 text-gray-800',
                                        'pending_approval' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                        'active' => 'bg-green-100 text-green-800',
                                        'closed' => 'bg-red-100 text-red-800',
                                    ][$statusVal] ?? 'bg-gray-100 text-gray-800';
                                    
                                    $statusLabel = [
                                        'draft' => 'Draft',
                                        'pending_approval' => 'Pending Approval',
                                        'active' => 'Active',
                                        'closed' => 'Closed',
                                    ][$statusVal] ?? ucfirst($statusVal);
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>

                                @php $typeVal = $survey->type instanceof \BackedEnum ? $survey->type->value : $survey->type; @endphp
                                @if($typeVal === 'public')
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fa-solid fa-globe mr-1"></i> Public
                                    </span>
                                @else
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <i class="fa-solid fa-envelope mr-1"></i> Invite Only
                                    </span>
                                @endif
                            </div>
                            <div class="mt-2 flex">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fa-solid fa-tag flex-shrink-0 mr-1.5 text-gray-400"></i>
                                    <p>{{ $survey->category }}</p>
                                </div>
                                <div class="mt-0 ml-6 flex items-center text-sm text-gray-500">
                                    <i class="fa-solid fa-calendar flex-shrink-0 mr-1.5 text-gray-400"></i>
                                    <p>Created {{ $survey->created_at->format('M j, Y') }}</p>
                                </div>
                                @if($statusVal === 'active')
                                <div class="mt-0 ml-6 flex items-center text-sm text-green-600 font-medium">
                                    <i class="fa-solid fa-check-circle flex-shrink-0 mr-1.5 text-green-500"></i>
                                    <p>Live & Collecting</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action buttons -->
                    <div class="ml-5 flex-shrink-0 flex items-center space-x-2">
                        @if($statusVal === 'draft')
                            <form action="{{ route('surveys.publish', $survey) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent shadow-sm text-sm font-medium rounded text-white bg-indigo-600 hover:bg-indigo-700 transition-colors" title="Submit for Approval">
                                    <i class="fa-solid fa-paper-plane mr-2"></i> Publish
                                </button>
                            </form>
                        @endif

                        @if($statusVal === 'active')
                             <button type="button" onclick="openInviteModal('{{ route('surveys.invite', $survey) }}', '{{ addslashes($survey->title) }}')" class="inline-flex items-center px-3 py-1.5 border border-indigo-200 shadow-sm text-sm font-medium rounded text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors" title="Send Invitation via Email">
                                 <i class="fa-solid fa-envelope mr-2"></i> Send Link
                             </button>
                             <a href="{{ route('surveys.show', $survey) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-indigo-200 shadow-sm text-sm font-medium rounded text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors" title="View Public Link">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        @endif

                        <a href="{{ route('surveys.edit', $survey) }}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors" title="Edit Survey">
                            <i class="fa-solid fa-edit"></i>
                        </a>
                        
                        <a href="{{ route('surveys.responses', $survey) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent shadow-sm text-sm font-medium rounded text-white bg-green-600 hover:bg-green-700 transition-colors" title="View Responses">
                            <i class="fa-solid fa-chart-pie"></i>
                        </a>

                        <form action="{{ route('surveys.destroy', $survey) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this survey and all its responses?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent shadow-sm text-sm font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 transition-colors" title="Delete Survey">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </li>
        @empty
            <li>
                <div class="px-4 py-8 flex flex-col items-center justify-center text-center sm:px-6">
                    <i class="fa-solid fa-clipboard-list text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500 font-medium text-lg">No surveys found</p>
                    <p class="text-gray-400 text-sm mt-1 mb-4">You haven't created any surveys yet.</p>
                    <a href="{{ route($role . '.surveys.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fa-solid fa-plus mr-2"></i> Create First Survey
                    </a>
                </div>
            </li>
        @endforelse
    </ul>
</div>

@if(method_exists($surveys, 'hasPages') && $surveys->hasPages())
    <div class="mt-4">
        {{ method_exists($surveys, 'links') ? $surveys->links() : '' }}
    </div>
@endif

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
