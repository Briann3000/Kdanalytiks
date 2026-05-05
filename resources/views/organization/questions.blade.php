@extends('layouts.app')

@section('title', __('Survey Questions'))

@section('content')
    <h1>{{ __('Questions') }}: {{ $survey->title }}</h1>

    @if($survey->questions->count() > 0)
        <table class="w3-table w3-striped w3-bordered">
            <tr class="w3-blue">
                <th>#</th>
                <th>{{ __('Question') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Required') }}</th>
            </tr>
            @foreach($survey->questions->sortBy('position') as $question)
                <tr>
                    <td>{{ $question->position ?? $loop->iteration }}</td>
                    <td>{{ $question->text }}</td>
                    <td>{{ __($question->type ?? 'text') }}</td>
                    <td>{{ $question->required ? __('YES') : __('NO') }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="w3-panel w3-yellow w3-round">
            <p>{{ __('No questions added to this survey yet.') }}</p>
        </div>
    @endif

    <div class="w3-margin-top">
        <a href="{{ route('organization.surveys') }}" class="w3-button w3-grey w3-round">{{ __('Back to Surveys') }}</a>
    </div>
@endsection