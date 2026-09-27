@props([
    'rows' => 5,
    'cols' => 4,
    'hasHeader' => true,
    'hasPagination' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden animate-pulse']) }}>
    @if($hasHeader)
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="h-5 bg-gray-200 rounded-md w-1/4"></div>
            <div class="flex items-center space-x-2">
                <div class="h-8 bg-gray-100 rounded-lg w-24"></div>
                <div class="h-8 bg-gray-200 rounded-lg w-20"></div>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50/75">
                <tr>
                    @for($c = 0; $c < $cols; $c++)
                        <th class="px-6 py-3 text-left">
                            <div class="h-3.5 bg-gray-200 rounded {{ $c === 0 ? 'w-24' : ($c === $cols - 1 ? 'w-16' : 'w-20') }}"></div>
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @for($r = 0; $r < $rows; $r++)
                    <tr>
                        @for($c = 0; $c < $cols; $c++)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="h-3.5 bg-gray-100 rounded {{ $c === 0 ? 'w-3/4 bg-gray-200' : ($c === $cols - 1 ? 'w-12' : ($r % 2 === 0 ? 'w-2/3' : 'w-1/2')) }}"></div>
                            </td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    @if($hasPagination)
        <div class="px-6 py-3 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
            <div class="h-3.5 bg-gray-200 rounded w-32"></div>
            <div class="flex space-x-1">
                <div class="h-7 w-7 bg-gray-200 rounded-md"></div>
                <div class="h-7 w-7 bg-gray-100 rounded-md"></div>
                <div class="h-7 w-7 bg-gray-100 rounded-md"></div>
            </div>
        </div>
    @endif
</div>
