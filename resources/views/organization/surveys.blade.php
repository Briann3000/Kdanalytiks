@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('title', 'Manage Surveys')

@section('content')
    <h1>Manage Surveys</h1>

    @if(session('success'))
        <div class="w3-panel w3-green w3-round">
            {{ session('success') }}
        </div>
    @endif

    <div class="w3-margin-bottom">
        <a href="{{ route('organization.create-survey') }}" class="w3-button w3-blue">Create New Survey</a>
    </div>

    <table class="w3-table w3-striped">
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Category</th>
            <th>Type</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        @forelse($surveys as $survey)
            <tr>
                <td>{{ $survey->title }}</td>
                <td>{{ Str::limit($survey->description, 50) }}</td>
                <td>{{ $survey->category }}</td>
                <td>{{ $survey->type }}</td>
                <td>{{ $survey->status }}</td>
                <td>
                    <a href="{{ route('organization.edit-survey', $survey) }}" class="w3-button w3-small w3-blue">Edit</a>
                    <a href="{{ route('organization.questions', $survey) }}" class="w3-button w3-small w3-green">Questions</a>
                    <a href="{{ route('organization.responses', $survey) }}" class="w3-button w3-small w3-orange">Responses</a>
                    <form method="post" action="{{ route('organization.delete-survey', $survey) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w3-button w3-small w3-red"
                            onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No surveys found.</td>
            </tr>
        @endforelse
    </table>
@endsection