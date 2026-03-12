@extends('layouts.app')

@section('title', 'My Surveys')

@section('content')
    <h1>My Surveys</h1>

    @if(session('success'))
        <div class="w3-panel w3-green w3-round">
            {{ session('success') }}
        </div>
    @endif

    <div class="w3-margin-bottom">
        <a href="{{ route('independent.create-survey') }}" class="w3-button w3-purple w3-round">Create New Survey</a>
    </div>

    @if($surveys->count() > 0)
        <table class="w3-table w3-striped w3-bordered">
            <tr class="w3-purple">
                <th>Title</th>
                <th>Category</th>
                <th>Type</th>
                <th>Status</th>
                <th>Responses</th>
            </tr>
            @foreach($surveys as $survey)
                <tr>
                    <td>{{ $survey->title }}</td>
                    <td>{{ $survey->category }}</td>
                    <td>{{ ucfirst($survey->type) }}</td>
                    <td>{{ ucfirst($survey->status) }}</td>
                    <td>{{ $survey->responses_count }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p>No surveys yet. Create your first survey!</p>
    @endif
@endsection
