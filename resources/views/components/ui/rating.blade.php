{{-- resources/views/components/ui/rating.blade.php --}}
@props([
    'value' => 5, // Rating out of 5
    'max' => 5,
    'size' => 'sm', // sm, md, lg
])

@php
    $sizeClasses = match($size) {
        'sm' => 'text-xs gap-0.5',
        'md' => 'text-sm gap-1',
        'lg' => 'text-base gap-1',
        default => 'text-xs gap-0.5',
    };
@endphp

<div class="flex items-center {{ $sizeClasses }}">
    @for($i = 1; $i <= $max; $i++)
        @if($i <= $value)
            <i class="fa-solid fa-star text-orange-500"></i>
        @else
            <i class="fa-regular fa-star text-gray-300"></i>
        @endif
    @endfor
</div>
