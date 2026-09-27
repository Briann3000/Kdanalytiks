@props([
    'fields' => 4,
    'hasSubmit' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 p-6 shadow-sm space-y-5 animate-pulse']) }}>
    @for($i = 0; $i < $fields; $i++)
        <div class="space-y-2">
            <div class="h-3.5 bg-gray-200 rounded w-1/4"></div>
            <div class="h-10 bg-gray-100 rounded-lg w-full border border-gray-200"></div>
        </div>
    @endfor

    @if($hasSubmit)
        <div class="pt-4 flex items-center justify-end space-x-3">
            <div class="h-10 bg-gray-100 rounded-lg w-20"></div>
            <div class="h-10 bg-gray-300 rounded-lg w-28"></div>
        </div>
    @endif
</div>
