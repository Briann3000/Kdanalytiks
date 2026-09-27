@props([
    'count' => 4,
    'cols' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
])

<div {{ $attributes->merge(['class' => 'grid gap-5 ' . $cols . ' animate-pulse']) }}>
    @for($i = 0; $i < $count; $i++)
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div class="h-3.5 bg-gray-200 rounded w-2/5"></div>
                <div class="w-8 h-8 rounded-lg bg-gray-100"></div>
            </div>
            <div class="h-8 bg-gray-300 rounded-md w-1/2"></div>
            <div class="flex items-center space-x-2 pt-1">
                <div class="h-3 bg-gray-100 rounded w-1/4"></div>
                <div class="h-3 bg-gray-100 rounded w-1/3"></div>
            </div>
        </div>
    @endfor
</div>
