@extends('layouts.app')

@section('title', 'Response Details')

@section('content')
<div class="px-4 sm:px-0 mb-8 max-w-4xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    @php 
                        $userRoleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                    @endphp
                    <li><a href="{{ route($userRoleVal . '.surveys.index') }}" class="hover:text-indigo-600">Surveys</a></li>
                    <li><i class="fa-solid fa-chevron-right text-[10px]"></i></li>
                    <li><a href="{{ route('surveys.responses', $survey) }}" class="hover:text-indigo-600">{{ $survey->title }}</a></li>
                    <li><i class="fa-solid fa-chevron-right text-[10px]"></i></li>
                    <li class="font-medium text-gray-900">Response #{{ $response->id }}</li>
                </ol>
            </nav>
            <h2 class="text-2xl font-bold text-gray-900">Response Details</h2>
        </div>
        <div>
            <a href="{{ route('surveys.responses', $survey) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Responses
            </a>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto bg-white shadow overflow-hidden sm:rounded-lg border border-gray-100 mb-10">
    <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Submitter Information
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            Submitted on {{ $response->created_at->format('M d, Y \a\t H:i:s') }}
        </p>
    </div>
    <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
        <dl class="sm:divide-y sm:divide-gray-200">
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Name</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    {{ $response->respondent ? $response->respondent->name : 'Anonymous' }}
                </dd>
            </div>
            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Email address</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    {{ $response->respondent ? $response->respondent->email : 'N/A' }}
                </dd>
            </div>
        </dl>
    </div>
</div>

<div class="max-w-4xl mx-auto bg-white shadow overflow-hidden sm:rounded-lg border border-gray-100">
    <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Answers
            </h3>
        </div>
    </div>
    <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
        <dl class="sm:divide-y sm:divide-gray-200">
            @if(!empty($survey->json_schema))
                @php
                    $jsonAnswer = $response->answers->first();
                    $parsedData = $jsonAnswer ? json_decode($jsonAnswer->value, true) : null;
                @endphp
                
                @if($parsedData && is_array($parsedData))
                    @foreach($parsedData as $data)
                        @if(isset($data['name']) && isset($data['userData']))
                            <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-50">
                                <dt class="text-sm font-medium text-gray-700">
                                    {{ $data['label'] ?? $data['name'] }}
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold whitespace-pre-wrap">
                                    @if(is_array($data['userData']))
                                        {{ implode(', ', $data['userData']) }}
                                    @else
                                        {{ $data['userData'] }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="py-4 sm:py-5 sm:px-6">
                        <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-100 p-4 rounded">{{ $jsonAnswer ? $jsonAnswer->value : 'No data found' }}</pre>
                    </div>
                @endif
                
            @else
                {{-- Legacy Questions --}}
                @foreach($survey->questions()->orderBy('position')->get() as $question)
                    @php
                        $answer = $response->answers->where('question_id', $question->id)->first();
                    @endphp
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 hover:bg-gray-50">
                        <dt class="text-sm font-medium text-gray-700">
                            {{ $question->text }}
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-bold">
                            @if($answer)
                                @if(in_array($question->type, ['video', 'audio']))
                                    <a href="{{ asset('storage/' . $answer->value) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 flex items-center">
                                        <i class="fa-solid fa-file-audio mr-2"></i> View Uploaded File
                                    </a>
                                @else
                                    {{ $answer->value }}
                                @endif
                            @else
                                <span class="text-gray-400 italic">No answer provided</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            @endif
        </dl>
    </div>
</div>
@endsection
