{{-- resources/views/components/sections/grid.blade.php
     Grid layout section for cards
--}}
@props([
    'cols' => 3, // Number of columns on desktop
    'bgColor' => 'bg-white',
    'padding' => 'py-16 md:py-32',
])

@php
    $colsClass = match((int)$cols) {
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
        default => 'md:grid-cols-3',
    };
@endphp

<section class="w-full {{ $bgColor }} {{ $padding }}">
    <div class="px-5 md:px-10">
        <div class="grid grid-cols-1 {{ $colsClass }} gap-10">
            {{ $slot }}
        </div>
    </div>
</section>
