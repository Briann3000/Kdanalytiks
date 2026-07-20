@extends('layouts.app')

@section('title', 'Public Surveys')

@section('content')
    @auth
        <div class="mb-6">
            @php
                $userRole = auth()->user()->role instanceof \UnitEnum ? auth()->user()->role->value : auth()->user()->role;
            @endphp
            <a href="{{ route($userRole . '.dashboard') }}"
                class="inline-flex items-center text-sm font-bold text-[#2271b1] hover:text-[#135e96] transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i> {{ __('Back to Dashboard') }}
            </a>
        </div>
    @endauth

    <div class="bg-white p-10 mb-10 shadow-sm border border-gray-100 relative overflow-hidden">
        <div class="relative z-10 max-w-3xl">

            <h1 class="text-5xl font-black mb-6 text-gray-900 tracking-tight leading-tight">
                {{ __('Explore Public Surveys') }}
            </h1>
            <p class="text-gray-600 text-xl font-medium leading-relaxed">
                {{ __('Browse Surveys and Earn') }}
            </p>
        </div>

    </div>

    <div class="mb-8 bg-white/50 backdrop-blur-sm p-4 rounded-3xl border border-gray-100 shadow-sm">
        <form action="{{ route('surveys.public') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-center">
            <div class="relative w-full md:w-96 group">
                <i
                    class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400 group-focus-within:text-[#2271b1] transition-colors"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('Search By Title...') }}"
                    class="w-full pl-12 pr-4 py-3 bg-white border border-gray-100 rounded-2xl text-xs font-black tracking-widest focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] transition-all shadow-sm">
            </div>
            <div class="relative w-full md:w-64 group">
                <i
                    class="fa-solid fa-filter absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400 group-focus-within:text-[#2271b1] transition-colors"></i>
                <select name="category"
                    class="w-full pl-12 pr-10 py-3 bg-white border border-gray-100 rounded-2xl text-xs font-semi-bold tracking-widest focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] appearance-none transition-all shadow-sm">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        @php $val = $category instanceof \BackedEnum ? $category->value : $category; @endphp
                        <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $val)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="relative w-full md:w-64 group">
                <i
                    class="fa-solid fa-filter absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
                <select name="paid_status"
                    class="w-full pl-12 pr-10 py-3 bg-white border border-gray-100 rounded-2xl text-xs font-semi-bold tracking-widest focus:ring-2 focus:ring-[#2271b1]/20 focus:border-[#2271b1] appearance-none transition-all shadow-sm">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="paid" {{ request('paid_status') === 'paid' ? 'selected' : '' }}>
                        {{ __('Paid Surveys Only') }}
                    </option>
                    <option value="unpaid" {{ request('paid_status') === 'unpaid' ? 'selected' : '' }}>
                        {{ __('Free Surveys Only') }}
                    </option>
                    <option value="exhausted" {{ request('paid_status') === 'exhausted' ? 'selected' : '' }}>
                        {{ __('Paid (Budget Exhausted)') }}
                    </option>
                </select>
            </div>
            <button type="submit"
                class="px-8 py-3 bg-[#2271b1] text-white rounded-2xl font-black text-xs tracking-widest shadow-lg shadow-zinc-200/50 hover:bg-[#135e96] transition-all">
                {{ __('Filter') }}
            </button>
            @if(request()->anyFilled(['search', 'category', 'paid_status']))
                <a href="{{ route('surveys.public') }}"
                    class="text-xs font-black tracking-widest text-gray-400 hover:text-red-500 transition-colors">
                    {{ __('Clear') }}
                </a>
            @endif
        </form>
    </div>

    @if($surveys->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            @foreach($surveys as $survey)
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all flex flex-col group transform hover:-translate-y-1">
                    <div class="p-6 flex-grow">
                        <div class="flex justify-between items-start mb-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-zinc-100 text-[#2271b1]">
                                {{ __($survey->category instanceof \BackedEnum ? $survey->category->value : $survey->category) }}
                            </span>
                            @if($survey->is_paid)
                                @php
                                    $budgetExhausted = ($survey->reward_budget - $survey->current_reward_spent) < $survey->reward_per_response;
                                @endphp
                                @if($budgetExhausted)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-700 shadow-sm border border-red-200"
                                        title="{{ __('The reward budget for this survey has been exhausted.') }}">
                                        <i class="fa-solid mr-1"></i> {{ __('Budget Exhausted') }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 shadow-sm border border-emerald-200">
                                        <i class="fa-solid  mr-1"></i> {{ number_format($survey->reward_per_response, 0) }}
                                        {{ $survey->reward_currency ?? 'KES' }}
                                    </span>
                                @endif
                            @endif
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-[#2271b1] transition-colors mb-3 line-clamp-2">
                            {{ $survey->title }}
                        </h3>
                        <p class="text-gray-500 text-sm mb-4 line-clamp-3">
                            {{ $survey->description ?? __('No description provided.') }}
                        </p>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-400 font-medium font-sans truncate mr-2">
                            <i class="fa-solid fa-calendar mr-1"></i> {{ $survey->created_at->format('M d, Y') }}
                        </span>
                        <div class="flex space-x-2">
                            <a href="mailto:?subject=Invitation to Participate: {{ rawurlencode($survey->title) }}&body=Hello,%0A%0ACheck out this survey:%0A%0A{{ rawurlencode($survey->title) }}%0A{{ route('surveys.show', $survey) }}%0A%0AThanks!"
                                class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-50 transition-colors"
                                title="Send Invitation via Email">
                                <i class="fa-solid fa-envelope text-zinc-2000"></i>
                            </a>
                            <a href="{{ route('surveys.show', $survey) }}"
                                class="inline-flex items-center px-4 py-2 bg-[#2271b1] text-white text-xs font-bold rounded-lg hover:bg-[#135e96] transition-colors">
                                {{ __('Participate') }} <i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $surveys->appends(request()->query())->links() }}
        </div>
    @else
        <div class="bg-white rounded-2xl p-16 text-center border-2 border-dashed border-gray-100 shadow-sm">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-magnifying-glass text-3xl text-gray-300"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('No public surveys found') }}</h3>
            <p class="text-gray-500 mb-8 max-w-sm mx-auto">
                {{ __("We couldn't find any public surveys matching your current search or filters. Try adjusting your criteria.") }}
            </p>
            <a href="{{ route('surveys.public') }}"
                class="inline-flex items-center px-6 py-3 bg-zinc-200 text-[#135e96] font-bold rounded-xl hover:bg-zinc-300 transition-colors">
                {{ __('Clear Filters') }}
            </a>
        </div>
    @endif

@endsection