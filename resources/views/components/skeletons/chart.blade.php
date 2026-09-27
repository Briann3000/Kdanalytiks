@props([
    'height' => 'h-72',
    'type' => 'bar',
    'hasHeader' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 p-6 shadow-sm animate-pulse']) }}>
    @if($hasHeader)
        <div class="flex items-center justify-between mb-6">
            <div class="space-y-1.5 w-1/3">
                <div class="h-5 bg-gray-200 rounded-md w-full"></div>
                <div class="h-3 bg-gray-100 rounded w-2/3"></div>
            </div>
            <div class="flex space-x-2">
                <div class="h-6 bg-gray-100 rounded w-16"></div>
                <div class="h-6 bg-gray-200 rounded w-12"></div>
            </div>
        </div>
    @endif

    @if($type === 'scatter' || $type === 'line')
        <div class="{{ $height }} flex flex-col justify-between py-2 relative">
            {{-- Y-axis lines --}}
            <div class="w-full border-b border-gray-100 border-dashed"></div>
            <div class="w-full border-b border-gray-100 border-dashed"></div>
            <div class="w-full border-b border-gray-100 border-dashed"></div>
            <div class="w-full border-b border-gray-100 border-dashed"></div>
            <div class="w-full border-b border-gray-200"></div>

            {{-- Scatter dots / curve placeholders --}}
            <div class="absolute inset-0 flex items-center justify-around px-8 pointer-events-none">
                <div class="w-3.5 h-3.5 rounded-full bg-gray-200 self-end mb-12"></div>
                <div class="w-3.5 h-3.5 rounded-full bg-gray-300 self-center mb-6"></div>
                <div class="w-3.5 h-3.5 rounded-full bg-gray-200 self-start mt-8"></div>
                <div class="w-3.5 h-3.5 rounded-full bg-gray-300 self-center"></div>
                <div class="w-3.5 h-3.5 rounded-full bg-gray-200 self-end mb-20"></div>
                <div class="w-3.5 h-3.5 rounded-full bg-gray-300 self-start mt-12"></div>
            </div>
        </div>
    @elseif($type === 'pie' || $type === 'doughnut')
        <div class="{{ $height }} flex items-center justify-center">
            <div class="w-48 h-48 rounded-full border-8 border-gray-100 border-t-gray-300 border-r-gray-200 flex items-center justify-center">
                <div class="w-20 h-20 rounded-full bg-gray-50"></div>
            </div>
        </div>
    @else
        {{-- Bar chart layout --}}
        <div class="{{ $height }} flex items-end justify-between space-x-2 pt-6 pb-2 border-b border-gray-200">
            <div class="w-full bg-gray-100 rounded-t h-[35%]"></div>
            <div class="w-full bg-gray-200 rounded-t h-[65%]"></div>
            <div class="w-full bg-gray-100 rounded-t h-[85%]"></div>
            <div class="w-full bg-gray-300 rounded-t h-[45%]"></div>
            <div class="w-full bg-gray-200 rounded-t h-[90%]"></div>
            <div class="w-full bg-gray-100 rounded-t h-[60%]"></div>
            <div class="w-full bg-gray-200 rounded-t h-[75%]"></div>
        </div>
        <div class="flex justify-between pt-2">
            <div class="h-3 bg-gray-100 rounded w-8"></div>
            <div class="h-3 bg-gray-100 rounded w-8"></div>
            <div class="h-3 bg-gray-100 rounded w-8"></div>
            <div class="h-3 bg-gray-100 rounded w-8"></div>
            <div class="h-3 bg-gray-100 rounded w-8"></div>
            <div class="h-3 bg-gray-100 rounded w-8"></div>
            <div class="h-3 bg-gray-100 rounded w-8"></div>
        </div>
    @endif
</div>
