{{-- resources/views/components/post/description.blade.php --}}

@props([
    'description',
    'size' => 'lg', // Options: 'base', 'lg', 'xl'
    'align' => 'left', // Options: 'left', 'center', 'center md:left'
    'color' => 'text-stone-900',
    'hideOnMobile' => false,
    'lineClamp' => null,
])

@php
    // Map size to ui/text variant
    $variantMapping = match($size) {
        'base' => 'sm',
        'lg' => 'md',
        'xl' => 'lg',
        default => 'md',
    };
@endphp

<x-ui.text
    :text="$description"
    :variant="$variantMapping"
    :color="$color"
    :align="$align"
    :hideOnMobile="$hideOnMobile"
    :lineClamp="$lineClamp"
/>
