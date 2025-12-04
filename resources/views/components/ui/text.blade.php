{{-- resources/views/components/ui/text.blade.php --}}

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
    /**
     * Typography classes from app.css (based on Figma)
     * These use responsive CSS with media queries built-in:
     *
     * text-body-sm: 12px/12px → 14px/12px
     * text-body-md: 18px/24px → 20px/28px
     * text-body-lg: 20px/26px → 24px/26px
     * text-nav: 18px/26px (monospace)
     */
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
