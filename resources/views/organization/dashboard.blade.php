@extends('layouts.app')

@section('title', 'Organization Dashboard')

@section('content')
    <h1>Organization Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}</p>

    @if($organization)
        <h2>{{ $organization->name }}</h2>
        <p>Payment Status: {{ $organization->payment_status }}</p>
    @else
        <p>No organization profile found.</p>
    @endif

    <h3>Your Surveys</h3>
    @if($surveys->count() > 0)
        <table class="w3-table w3-striped">
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            @foreach($surveys as $survey)
                <tr>
                    <td>{{ $survey->title }}</td>
                    <td>{{ $survey->status }}</td>
                    <td>
                        <a href="#" class="w3-button w3-small w3-blue">Edit</a>
                        <a href="#" class="w3-button w3-small w3-green">View Responses</a>
                    </td>
                </tr>
            @endforeach
        </table>
    @else
        <p>No surveys yet.</p>
    @endif

    <div class="w3-margin-top">
        <a href="{{ route('organization.create-survey') }}" class="w3-button w3-blue">Create New Survey</a>
        <a href="{{ route('organization.surveys') }}" class="w3-button w3-green">Manage Surveys</a>
    </div>
@endsection