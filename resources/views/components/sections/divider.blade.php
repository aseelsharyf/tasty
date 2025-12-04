{{-- resources/views/components/sections/divider.blade.php
     Vertical divider line for between cards/sections
--}}
@props([
    'color' => 'outline-white',
    'hideOnMobile' => true,
])

<div class="{{ $hideOnMobile ? 'hidden md:block' : '' }} w-0 self-stretch outline outline-1 outline-offset-[-0.5px] {{ $color }} shrink-0"></div>
