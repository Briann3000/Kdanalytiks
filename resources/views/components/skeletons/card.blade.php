@props([
    'lines' => 3,
    'hasImage' => false,
    'hasHeader' => true,
    'hasFooter' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 p-6 shadow-sm animate-pulse']) }}>
    @if($hasImage)
        <div class="w-full h-40 bg-gray-200 rounded-lg mb-4"></div>
    @endif

    @if($hasHeader)
        <div class="flex items-center justify-between mb-4">
            <div class="h-5 bg-gray-200 rounded-md w-1/3"></div>
            <div class="h-4 bg-gray-100 rounded-md w-16"></div>
        </div>
    @endif

    <div class="space-y-3">
        @for($i = 0; $i < $lines; $i++)
            <div class="h-3.5 bg-gray-100 rounded {{ $i === $lines - 1 ? 'w-2/3' : ($i % 2 === 0 ? 'w-full' : 'w-5/6') }}"></div>
        @endfor
    </div>

    @if($hasFooter)
        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
            <div class="h-4 bg-gray-100 rounded w-24"></div>
            <div class="h-8 bg-gray-200 rounded-lg w-20"></div>
        </div>
    @endif
</div>
