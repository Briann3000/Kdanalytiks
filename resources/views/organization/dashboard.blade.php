@extends('layouts.app')

@section('title', __('ORGANIZATION DASHBOARD'))

@section('content')
    <h1>{{ __('ORGANIZATION DASHBOARD') }}</h1>
    <p>{{ __('Welcome') }}, {{ auth()->user()->name }}</p>

    @if($organization)
        <h2>{{ $organization->name }}</h2>
        <p>{{ __('Payment Status') }}: {{ __($organization->payment_status) }}</p>
    @else
        <p>{{ __('No organization profile found.') }}</p>
    @endif

    <h3>{{ __('Your Surveys') }}</h3>
    @if($surveys->count() > 0)
        <table class="w3-table w3-striped">
            <tr>
                <th>{{ __('Title') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
            </tr>
            @foreach($surveys as $survey)
                <tr>
                    <td>{{ $survey->title }}</td>
                    <td>{{ __($survey->status) }}</td>
                    <td>
                        <a href="#" class="w3-button w3-small w3-blue">{{ __('Edit') }}</a>
                        <a href="#" class="w3-button w3-small w3-green">{{ __('View Responses') }}</a>
                    </td>
                </tr>
            @endforeach
        </table>
    @else
        <p>{{ __('No surveys yet.') }}</p>
    @endif

    <div class="w3-margin-top">
        <a href="{{ route('organization.create-survey') }}" class="w3-button w3-blue">{{ __('Create New Survey') }}</a>
        <a href="{{ route('organization.surveys') }}" class="w3-button w3-green">{{ __('Manage Surveys') }}</a>
        <a href="{{ route('account.settings') }}"
            class="w3-button bg-[#2271b1] text-white hover:bg-[#135e96]">{{ __('Account Settings') }}</a>
    </div>
@endsection