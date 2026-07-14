@extends('layouts.app')

@section('title', __('Survey Responses'))

@section('content')
    <h1>{{ __('Responses') }}: {{ $survey->title }}</h1>

    @if($survey->responses->count() > 0)
        <p>{{ __('Total Responses') }}: <strong>{{ $survey->responses->count() }}</strong></p>

        <table class="w3-table w3-striped w3-bordered">
            <tr class="w3-blue">
                <th>#</th>
                <th>{{ __('Respondent') }}</th>
                <th>{{ __('Submitted') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
            @foreach($survey->responses as $response)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $response->respondent->name ?? ($response->guest_name ?? __('Anonymous')) }}</td>
                    <td>{{ $response->submitted_at ? $response->submitted_at->format('M d, Y H:i') : 'N/A' }}</td>
                    <td>{{ $response->answers->count() }} {{ __('responses') }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="w3-panel w3-yellow w3-round">
            <p>{{ __('No responses received yet.') }}</p>
        </div>
    @endif

    <div class="w3-margin-top">
        <a href="{{ route('organization.surveys') }}" class="w3-button w3-grey w3-round">{{ __('Back to Surveys') }}</a>
    </div>
@endsection