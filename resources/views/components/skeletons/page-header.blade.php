@props([
    'hasBreadcrumbs' => true,
    'hasAction' => true,
])

<div {{ $attributes->merge(['class' => 'mb-6 animate-pulse']) }}>
    @if($hasBreadcrumbs)
        <div class="flex items-center space-x-2 mb-3">
            <div class="h-3 bg-gray-200 rounded w-16"></div>
            <span class="text-gray-300">/</span>
            <div class="h-3 bg-gray-200 rounded w-24"></div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="space-y-2">
            <div class="h-8 bg-gray-300 rounded-lg w-64"></div>
            <div class="h-4 bg-gray-200 rounded w-96 max-w-full"></div>
        </div>

        @if($hasAction)
            <div class="flex items-center space-x-3">
                <div class="h-9 bg-gray-200 rounded-lg w-28"></div>
                <div class="h-9 bg-gray-200 rounded-lg w-24"></div>
            </div>
        @endif
    </div>
</div>
