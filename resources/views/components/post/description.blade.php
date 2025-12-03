{{-- resources/views/components/post/description.blade.php --}}

@props([
    'description',
    'size' => 'lg', // Options: 'base', 'lg', 'xl'
    'align' => 'left', // Options: 'left', 'center'
    'color' => 'text-stone-900',
    'leading' => 'leading-7',
    'hideOnMobile' => false,
])

@php
    // Size classes
    $sizeClasses = match($size) {
        'base' => 'text-base',
        'lg' => 'text-lg md:text-xl',
        'xl' => 'text-xl',
        default => 'text-lg md:text-xl',
    };

    // Alignment classes
    $alignClasses = match($align) {
        'center' => 'text-center',
        'left' => 'text-left',
        'center md:left' => 'text-center md:text-left',
        default => 'text-left',
    };

    // Visibility classes
    $visibilityClasses = $hideOnMobile ? 'hidden md:block' : '';

    // Combine all classes
    $classes = trim("w-full {$sizeClasses} {$color} {$alignClasses} {$leading} {$visibilityClasses}");
@endphp

<p class="{{ $classes }}">
    {{ $description }}
</p>
