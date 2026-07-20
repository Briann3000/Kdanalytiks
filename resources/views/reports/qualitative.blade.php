@extends('layouts.app')

@section('title', 'Qualitative Analysis: ' . $survey->title)

@section('content')
<div class="px-4 sm:px-0 mb-8 max-w-6xl mx-auto" x-data="{ 
    selectedQuestion: '{{ count($questions) > 0 ? $questions[0]['id'] : '' }}',
    surveyId: {{ $survey->id }}
}">
    <div class="flex items-center justify-between mb-6">
        <div>
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    @php 
                        $userRoleVal = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
                    @endphp
                    <li><a href="{{ route($userRoleVal . '.reports.index') }}" class="hover:text-[#2271b1]">{{ __('Reports') }}</a></li>
                    <li><i class="fa-solid fa-chevron-right text-[10px]"></i></li>
                    <li class="font-medium text-gray-900">{{ $survey->title }}</li>
                </ol>
            </nav>
            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">{{ __('Qualitative Insights') }}</h2>
            <p class="text-gray-500 text-sm mt-1">{{ __('Deep-dive into open-ended voter sentiment and thematic trends.') }}</p>
        </div>
        <div class="flex space-x-3">
             <a href="{{ route('surveys.report', $survey) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-chart-line mr-2 text-[#2271b1]"></i> {{ __('Standard Report') }}
            </a>
        </div>
    </div>

    <!-- Question Selector & Trigger -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <div class="flex-1">
                <label for="question-select" class="block text-sm font-semibold text-gray-700 mb-3">{{ __('Choose a question to analyze') }}</label>
                <div class="flex flex-col sm:flex-row gap-4">
                    <select 
                        id="question-select" 
                        x-model="selectedQuestion"
                        class="block w-full rounded-lg border-gray-300 text-gray-700 focus:border-[#2271b1] focus:ring-[#2271b1] shadow-sm"
                    >
                        @forelse($questions as $question)
                            <option value="{{ $question['id'] }}">{{ $question['text'] }}</option>
                        @empty
                            <option disabled>{{ __('No qualitative questions found') }}</option>
                        @endforelse
                    </select>
                    
                    <button 
                        @click="$dispatch('trigger-analysis', { id: selectedQuestion })" 
                        class="inline-flex items-center justify-center px-6 py-2 border border-transparent text-sm font-bold rounded-lg shadow-sm text-white bg-[#2271b1] hover:bg-[#135e96] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#2271b1] transition-colors whitespace-nowrap"
                    >
                        <i class="fa-solid fa-wand-sparkles mr-2"></i>
                        {{ __('Generate Report') }}
                    </button>
                </div>
            </div>
            <div class="hidden lg:block pb-1 italic text-xs text-gray-400 font-medium">
                {{ __('Individual question sentiment and theme mapping') }}
            </div>
        </div>
    </div>

    <!-- Results Component -->
    <div x-show="selectedQuestion" class="mt-8 transition-all">
         <x-ai-insight-card 
            :question-id="''" 
            :question-title="__('Qualitative Analysis')" 
            :survey-id="$survey->id" 
        />
    </div>

    @if(empty($questions))
        <div class="bg-white rounded-2xl p-16 text-center border-2 border-dashed border-gray-100 shadow-sm">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-comment-slash text-3xl text-gray-300"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('No qualitative data found') }}</h3>
            <p class="text-gray-500 max-w-sm mx-auto">{{ __('This survey does not contain any open-ended text questions (text or textarea) required for qualitative AI analysis.') }}</p>
        </div>
    @endif
</div>
@endsection
