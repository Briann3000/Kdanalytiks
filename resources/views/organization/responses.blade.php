@extends('layouts.app')

@section('title', 'Survey Responses')

@section('content')
    <h1>Responses: {{ $survey->title }}</h1>

    @if($survey->responses->count() > 0)
        <p>Total responses: <strong>{{ $survey->responses->count() }}</strong></p>

        <table class="w3-table w3-striped w3-bordered">
            <tr class="w3-blue">
                <th>#</th>
                <th>Respondent</th>
                <th>Submitted</th>
                <th>Answers</th>
            </tr>
            @foreach($survey->responses as $response)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $response->respondent->name ?? 'Anonymous' }}</td>
                    <td>{{ $response->submitted_at ? $response->submitted_at->format('M d, Y H:i') : 'N/A' }}</td>
                    <td>{{ $response->answers->count() }} answers</td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="w3-panel w3-yellow w3-round">
            <p>No responses received yet.</p>
        </div>
    @endif

    <div class="w3-margin-top">
        <a href="{{ route('organization.surveys') }}" class="w3-button w3-grey w3-round">Back to Surveys</a>
    </div>
@endsection
