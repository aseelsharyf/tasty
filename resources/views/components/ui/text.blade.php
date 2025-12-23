{{-- UI Text Component --}}

@props([
    'text' => '',
    'variant' => 'md', // Options: 'sm', 'md', 'lg', 'nav'
    'color' => 'text-tasty-blue-black',
    'align' => 'left', // Options: 'left', 'center', 'right'
    'tag' => 'p',
    'hideOnMobile' => false,
    'lineClamp' => null,
])

@php
    $typographyClasses = [
        'sm' => 'text-body-sm',
        'md' => 'text-body-md',
        'lg' => 'text-body-lg',
        'nav' => 'text-nav',
    ];

    // Use typography class from app.css
    $typeClass = $typographyClasses[$variant] ?? 'text-body-md';

    // Alignment
    $alignClass = match($align) {
        'center' => 'text-center',
        'right' => 'text-right',
        'left' => 'text-left',
        default => 'text-left',
    };

    // Visibility
    $visibilityClass = $hideOnMobile ? 'hidden md:block' : '';

    // Combine classes
    $classes = trim("{$typeClass} {$color} {$alignClass} {$visibilityClass} {$lineClamp}");
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $text }}{{ $slot }}
</{{ $tag }}>
