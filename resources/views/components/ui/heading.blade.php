{{-- UI Heading Component --}}

@props([
    'level' => 'h2', // Options: 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
    'text' => '',
    'variant' => null, // Options: null, 'hero' (for h1-hero style)
    'size' => null, // Optional: Override with custom size class
    'color' => 'text-tasty-blue-black',
    'align' => 'left', // Options: 'left', 'center', 'right'
    'uppercase' => false,
    'url' => null, // Optional: Make heading a link
    'lineClamp' => null, // Optional: 'line-clamp-3', 'line-clamp-2', etc.
])

@php
    $typographyClasses = [
        'h1' => 'text-h1',
        'h1-hero' => 'text-h1-hero',
        'h2' => 'text-h2',
        'h3' => 'text-h3',
        'h4' => 'text-h4',
        'h5' => 'text-h4', // fallback to h4 styling
        'h6' => 'text-h4', // fallback to h4 styling
    ];

    // Determine which typography class to use
    $typographyKey = $level;
    if ($level === 'h1' && $variant === 'hero') {
        $typographyKey = 'h1-hero';
    }

    // Use custom size or typography class
    $typeClass = $size ?? $typographyClasses[$typographyKey] ?? 'text-h3';

    // Alignment
    $alignClass = match($align) {
        'center' => 'text-center',
        'right' => 'text-right',
        'left' => 'text-left',
        default => 'text-left',
    };

    // Uppercase (h1-hero has it built in, but allow manual override)
    $uppercaseClass = $uppercase ? 'uppercase' : '';

    // Combine classes
    $classes = trim("{$typeClass} {$color} {$alignClass} {$uppercaseClass} {$lineClamp}");
@endphp

@if($url)
    <a href="{{ $url }}" class="block">
        <{{ $level }} {{ $attributes->merge(['class' => $classes]) }}>
            {{ $text }}{{ $slot }}
        </{{ $level }}>
    </a>
@else
    <{{ $level }} {{ $attributes->merge(['class' => $classes]) }}>
        {{ $text }}{{ $slot }}
    </{{ $level }}>
@endif
