@extends('layouts.app')

@section('title', 'Survey Templates')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight uppercase">Survey Templates</h1>
            <p class="text-sm text-gray-500 font-medium">Pre-designed blueprints to jumpstart your research.</p>
        </div>
        <a href="{{ route('surveys.create') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-500 shadow-xl shadow-indigo-100 transition-all flex items-center">
            <i class="fa-solid fa-plus mr-2"></i> Custom Project
        </a>
    </div>

    <!-- Template Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        @foreach($templates as $tpl)
        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm hover:shadow-xl transition-all group hover:-translate-y-2">
            <div class="w-16 h-16 {{ $tpl->color ?? 'bg-indigo-50 text-indigo-600' }} rounded-2xl flex items-center justify-center text-3xl mb-6 shadow-inner transition-transform group-hover:scale-110">
                <i class="fa-solid {{ $tpl->icon ?? 'fa-file-lines' }}"></i>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2 tracking-tight">{{ $tpl->title }}</h3>
            <p class="text-sm text-gray-500 mb-6 font-medium leading-relaxed">
                {{ $tpl->description ?? 'Standardized schema optimized for high-quality data collection and analytical accuracy.' }}
            </p>
            <div class="flex items-center space-x-6">
                <a href="{{ route('library.templates.clone', $tpl) }}" class="flex items-center text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:translate-x-1 transition-transform">
                    Use Template <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('surveys.edit', $tpl) }}" class="flex items-center text-[10px] font-black text-gray-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">
                        Edit Template <i class="fa-solid fa-pen-to-square ml-2"></i>
                    </a>
                @else
                    <a href="{{ route('surveys.show', $tpl) }}" class="flex items-center text-[10px] font-black text-gray-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">
                        View Template <i class="fa-solid fa-eye ml-2"></i>
                    </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="bg-slate-900 rounded-[2rem] p-12 text-center text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <i class="fa-solid fa-sparkles text-[300px] absolute -right-20 -top-20"></i>
        </div>
        <div class="relative z-10">
            <h2 class="text-3xl font-black uppercase tracking-tight mb-4">Need something specific?</h2>
            <p class="text-gray-400 max-w-2xl mx-auto mb-8 font-medium">Use our AI Survey Architect to generate a custom template based on your specific research goals and audience.</p>
            <a href="{{ route('surveys.create') }}" class="inline-flex items-center px-8 py-4 bg-white text-gray-900 rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-gray-100 transition-all shadow-2xl">
                <i class="fa-solid fa-sparkles mr-2 text-indigo-600"></i> Open AI Architect
            </a>
        </div>
    </div>
</div>
@endsection
