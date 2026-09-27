@props([
    'rows' => 4,
    'hasAvatar' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100 overflow-hidden animate-pulse']) }}>
    @for($i = 0; $i < $rows; $i++)
        <div class="p-4 sm:p-5 flex items-center justify-between">
            <div class="flex items-center space-x-3 w-3/4">
                @if($hasAvatar)
                    <div class="w-10 h-10 rounded-full bg-gray-200 shrink-0"></div>
                @endif
                <div class="space-y-2 flex-1">
                    <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-3 bg-gray-100 rounded w-2/3"></div>
                </div>
            </div>
            <div class="h-8 bg-gray-100 rounded-lg w-16 shrink-0"></div>
        </div>
    @endfor
</div>
