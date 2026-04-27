@extends('layouts.app')

@section('title', 'Independent Dashboard')

@section('content')
    <h1>Researcher Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}</p>

    @if($independent)
        <div class="w3-row-padding w3-margin-bottom">
            <div class="w3-third">
                <div class="w3-card w3-purple w3-padding w3-center">
                    <h3>{{ $surveys->count() }}</h3>
                    <p>Your Surveys</p>
                </div>
            </div>
            <div class="w3-third">
                <div class="w3-card w3-green w3-padding w3-center">
                    <h3>{{ $surveys->sum('responses_count') }}</h3>
                    <p>Total Responses</p>
                </div>
            </div>
            <div class="w3-third">
                <div class="w3-card w3-blue w3-padding w3-center">
                    <h3>{{ $independent->payment_status }}</h3>
                    <p>Payment Status</p>
                </div>
            </div>
        </div>

        <h3>Your Surveys</h3>
        @if($surveys->count() > 0)
            <table class="w3-table w3-striped w3-bordered">
                <tr class="w3-purple">
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Responses</th>
                </tr>
                @foreach($surveys as $survey)
                    <tr>
                        <td>{{ $survey->title }}</td>
                        <td>{{ $survey->category }}</td>
                        <td>{{ ucfirst($survey->status) }}</td>
                        <td>{{ $survey->responses_count }}</td>
                    </tr>
                @endforeach
            </table>
        @else
            <p>No surveys yet.</p>
        @endif

        <div class="w3-margin-top">
            <a href="{{ route('independent.create-survey') }}" class="w3-button w3-purple w3-round">Create New Survey</a>
            <a href="{{ route('account.settings') }}" class="w3-button w3-indigo w3-round">Account Settings</a>
        </div>
    @else
        <div class="w3-panel w3-yellow w3-round">
            <p>No researcher profile found. Please contact the administrator.</p>
        </div>
    @endif
@endsection