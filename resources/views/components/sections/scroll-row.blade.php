{{-- resources/views/components/sections/scroll-row.blade.php
     Horizontal scrolling section with optional header
     Used for The Spread, Ceylon sections, etc.
--}}
@props([
    'bgColor' => 'bg-tasty-yellow',
    'showDividers' => true,
    'dividerColor' => 'outline-white',
])

<section class="w-full {{ $bgColor }} overflow-x-hidden">
    <div class="px-5 md:px-10 py-16 md:py-32">
        <div class="flex flex-col gap-10 md:flex-row md:gap-10 md:overflow-x-auto md:scrollbar-hide md:scroll-smooth">
            {{ $slot }}
        </div>
    </div>
</section>
