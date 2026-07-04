@extends('layouts.app')

@section('content')
    <div class="w3-container w3-padding-64 w3-center">
        <h1 class="w3-xxxlarge w3-text-blue"><i class="fa fa-poll"></i> KDAnalytiks</h1>
        <p class="w3-large">Create, manage, and analyze surveys with ease</p>
    </div>

    <div class="w3-row-padding w3-padding-32 w3-center">
        <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
            <i class="fa fa-user-shield w3-text-blue w3-jumbo"></i>
            <h3>Admin</h3>
            <p>Manage users, surveys, payments, and reports</p>
            <a href="{{ route('admin.login') }}" class="w3-button w3-blue w3-round-large">Login</a>
        </div>

        <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
            <i class="fa fa-building w3-text-green w3-jumbo"></i>
            <h3>Organizations</h3>
            <p>Create and manage surveys for your business</p>
            <a href="{{ route('organization.login') }}" class="w3-button w3-green w3-round-large">Login</a>
            <a href="{{ route('organization.register') }}"
                class="w3-button w3-light-grey w3-round-large w3-margin-top">Register</a>
        </div>

        <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
            <i class="fa fa-user-graduate w3-text-purple w3-jumbo"></i>
            <h3>Researchers</h3>
            <p>PhD students and independent researchers</p>
            <a href="{{ route('independent.login') }}" class="w3-button w3-purple w3-round-large">Login</a>
            <a href="{{ route('independent.register') }}"
                class="w3-button w3-light-grey w3-round-large w3-margin-top">Register</a>
        </div>

        <div class="w3-quarter w3-card w3-padding w3-hover-shadow">
            <i class="fa fa-users w3-text-orange w3-jumbo"></i>
            <h3>Respondents</h3>
            <p>Register and take surveys sent to you</p>
            <a href="{{ route('respondent.login') }}" class="w3-button w3-orange w3-round-large">Login</a>
            <a href="{{ route('respondent.register') }}"
                class="w3-button w3-light-grey w3-round-large w3-margin-top">Register</a>
        </div>
    </div>

    <div class="w3-row-padding w3-padding-32 w3-center">
        <div class="w3-half w3-card w3-padding w3-hover-shadow">
            <i class="fa fa-list-alt w3-text-blue w3-jumbo"></i>
            <h3>Public Surveys</h3>
            <p>Browse and participate in public surveys</p>
            <a href="{{ route('public.surveys') }}" class="w3-button w3-blue w3-round-large">Browse Surveys</a>
        </div>
    </div>
@endsection