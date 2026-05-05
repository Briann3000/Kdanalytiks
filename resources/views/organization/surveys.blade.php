@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('title', __('Manage Surveys'))

@section('content')
    <h1>{{ __('Manage Surveys') }}</h1>

    @if(session('success'))
        <div class="w3-panel w3-green w3-round">
            {{ __(session('success')) }}
        </div>
    @endif

    <div class="w3-margin-bottom">
        <a href="{{ route('organization.create-survey') }}" class="w3-button w3-blue">{{ __('Create New Survey') }}</a>
    </div>

    <table class="w3-table w3-striped">
        <tr>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Description') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
        </tr>
        @forelse($surveys as $survey)
            <tr>
                <td>{{ $survey->title }}</td>
                <td>{{ Str::limit($survey->description, 50) }}</td>
                <td>{{ __($survey->category) }}</td>
                <td>{{ __($survey->type) }}</td>
                <td>{{ __($survey->status) }}</td>
                <td>
                    <a href="{{ route('organization.edit-survey', $survey) }}"
                        class="w3-button w3-small w3-blue">{{ __('Edit') }}</a>
                    <a href="{{ route('organization.questions', $survey) }}"
                        class="w3-button w3-small w3-green">{{ __('Questions') }}</a>
                    <a href="{{ route('organization.responses', $survey) }}"
                        class="w3-button w3-small w3-orange">{{ __('Responses') }}</a>
                    <form method="post" action="{{ route('organization.delete-survey', $survey) }}" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w3-button w3-small w3-red"
                            onclick="return confirm('{{ __('Are you sure?') }}')">{{ __('Delete') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">{{ __('No surveys found.') }}</td>
            </tr>
        @endforelse
    </table>
@endsection