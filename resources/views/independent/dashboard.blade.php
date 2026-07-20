@extends('layouts.app')

@section('title', __('Researcher Dashboard'))

@section('content')
    <h1>{{ __('Researcher Dashboard') }}</h1>
    <p>{{ __('Welcome') }}, {{ auth()->user()->name }}</p>

    @if($independent)
        <div class="w3-row-padding w3-margin-bottom">
            <div class="w3-third">
                <div class="w3-card w3-purple w3-padding w3-center">
                    <h3>{{ $surveys->count() }}</h3>
                    <p>{{ __('Your Surveys') }}</p>
                </div>
            </div>
            <div class="w3-third">
                <div class="w3-card w3-green w3-padding w3-center">
                    <h3>{{ $surveys->sum('responses_count') }}</h3>
                    <p>{{ __('Total Responses') }}</p>
                </div>
            </div>
            <div class="w3-third">
                <div class="w3-card w3-blue w3-padding w3-center">
                    <h3>{{ __($independent->payment_status) }}</h3>
                    <p>{{ __('Payment Status') }}</p>
                </div>
            </div>
        </div>

        <h3>{{ __('Your Surveys') }}</h3>
        @if($surveys->count() > 0)
            <table class="w3-table w3-striped w3-bordered">
                <tr class="w3-purple">
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Responses') }}</th>
                </tr>
                @foreach($surveys as $survey)
                    <tr>
                        <td>{{ $survey->title }}</td>
                        <td>{{ __($survey->category) }}</td>
                        <td>{{ __(ucfirst($survey->status)) }}</td>
                        <td>{{ $survey->responses_count }}</td>
                    </tr>
                @endforeach
            </table>
        @else
            <p>{{ __('No surveys yet.') }}</p>
        @endif

        <div class="w3-margin-top">
            <a href="{{ route('independent.create-survey') }}"
                class="w3-button w3-purple w3-round">{{ __('Create New Survey') }}</a>
            <a href="{{ route('account.settings') }}"
                class="w3-button bg-[#2271b1] text-white hover:bg-[#135e96] w3-round">{{ __('Account Settings') }}</a>
        </div>
    @else
        <div class="w3-panel w3-yellow w3-round">
            <p>{{ __('No researcher profile found. Please contact the administrator.') }}</p>
        </div>
    @endif
@endsection