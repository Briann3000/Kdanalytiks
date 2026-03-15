@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-8 px-4 sm:px-0">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 leading-tight">Manage Surveys</h2>
        <p class="mt-1 text-sm text-gray-500">View and manage all your created surveys and draft schemas.</p>
    </div>
    <div>
        <a href="{{ route($role . '.surveys.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
            <i class="fa-solid fa-plus mr-2"></i> Create New Survey
        </a>
    </div>
</div>

<div class="bg-white shadow rounded-lg border border-gray-100 overflow-hidden">
    <div class="table-container">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Survey Detail</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responses</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($surveys as $survey)
                    @php 
                        $statusVal = $survey->status instanceof \BackedEnum ? $survey->status->value : $survey->status;
                        $typeVal = $survey->type instanceof \BackedEnum ? $survey->type->value : $survey->type;
                    @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ $survey->title }}</div>
                            <div class="text-xs text-gray-500">{{ $survey->category }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 uppercase tracking-wider">
                                {{ $typeVal === 'public' ? 'Public' : 'Invitation' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'pending_approval' => 'bg-yellow-100 text-yellow-800',
                                    'active' => 'bg-green-100 text-green-800',
                                    'closed' => 'bg-red-100 text-red-800',
                                ][$statusVal] ?? 'bg-gray-100 text-gray-800';
                                
                                $statusLabel = [
                                    'pending_approval' => 'Pending Approval',
                                    'draft' => 'Draft',
                                    'active' => 'Active',
                                    'closed' => 'Closed',
                                ][$statusVal] ?? ucfirst($statusVal);
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-indigo-600">
                                <a href="{{ route('surveys.responses', $survey) }}" class="hover:underline flex items-center">
                                    {{ $survey->responses_count ?? 0 }} 
                                    <i class="fa-solid fa-chart-pie ml-1 text-[10px]"></i>
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $survey->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-3">
                                @if($statusVal === 'draft')
                                    <form action="{{ route('surveys.publish', $survey) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900" title="Publish">
                                            <i class="fa-solid fa-paper-plane"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($statusVal === 'active')
                                    <button type="button" onclick="openInviteModal('{{ route('surveys.invite', $survey) }}', '{{ addslashes($survey->title) }}')" class="text-blue-600 hover:text-blue-900" title="Send Link">
                                        <i class="fa-solid fa-envelope"></i>
                                    </button>
                                    <a href="{{ route('surveys.show', $survey) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900" title="Public Link">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                @endif

                                <a href="{{ route('surveys.qualitative', $survey) }}" class="text-purple-600 hover:text-purple-900" title="AI Qualitative Insights">
                                    <i class="fa-solid fa-brain"></i>
                                </a>

                                <a href="{{ route('surveys.edit', $survey) }}" class="text-gray-600 hover:text-gray-900" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <form action="{{ route('surveys.destroy', $survey) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">No surveys found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($surveys instanceof \Illuminate\Pagination\LengthAwarePaginator && $surveys->hasPages())
    <div class="mt-4">
        {{ $surveys->links() }}
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
