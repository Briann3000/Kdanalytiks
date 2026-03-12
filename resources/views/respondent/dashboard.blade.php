@extends('layouts.app')

@section('title', 'Respondent Dashboard')

@section('content')
    <h1>Respondent Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}</p>

    <div class="w3-row-padding w3-margin-bottom">
        <div class="w3-half">
            <div class="w3-card w3-blue w3-padding w3-center">
                <h3>{{ $responses->count() }}</h3>
                <p>Completed Surveys</p>
            </div>
        </div>
        <div class="w3-half">
            <div class="w3-card w3-green w3-padding w3-center">
                <h3>{{ $availableSurveys->count() }}</h3>
                <p>Available Surveys</p>
            </div>
        </div>
    </div>

    @if($availableSurveys->count() > 0)
        <h3>Available Surveys</h3>
        <div class="w3-row-padding">
            @foreach($availableSurveys as $survey)
                <div class="w3-third w3-margin-bottom">
                    <div class="w3-card w3-white w3-padding">
                        <h4>{{ $survey->title }}</h4>
                        <p class="w3-text-grey">{{ Illuminate\Support\Str::limit($survey->description, 80) }}</p>
                        <span class="w3-tag w3-round w3-margin-bottom">{{ $survey->category }}</span>
                        <a href="{{ route('surveys.show', $survey) }}" class="w3-button w3-block w3-blue w3-round">
                            <i class="fa fa-arrow-right"></i> Take Survey
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($responses->count() > 0)
        <h3>Your Completed Surveys</h3>
        <table class="w3-table w3-striped w3-bordered">
            <tr class="w3-blue">
                <th>Survey</th>
                <th>Submitted</th>
            </tr>
            @foreach($responses as $response)
                <tr>
                    <td>{{ $response->survey->title ?? 'Deleted Survey' }}</td>
                    <td>{{ $response->submitted_at ? $response->submitted_at->format('M d, Y H:i') : 'N/A' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p>You haven't completed any surveys yet.</p>
    @endif
@endsection