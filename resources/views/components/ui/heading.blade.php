{{-- resources/views/components/ui/heading.blade.php --}}

@props([
    'level' => 'h2', // Options: 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
    'text' => '',
    'size' => null, // Optional: Override size (e.g., 'text-5xl', 'text-3xl')
    'color' => 'text-stone-900',
    'align' => 'left', // Options: 'left', 'center', 'right'
    'weight' => 'font-normal',
    'font' => 'font-serif',
    'leading' => null, // Optional: Override leading
    'uppercase' => false,
    'url' => null, // Optional: Make heading a link
])

@php
    // Default size based on level (matching design tokens)
    // Mobile: 60px/50px, Desktop: 100px/86px for h1
    // Mobile: 40px/44px, Desktop: 64px/66px for h2
    // Mobile: 32px/32px, Desktop: 48px/48px for h3
    // Mobile: 24px/24px, Desktop: 32px/38px for h4
    $defaultSizes = [
        'h1' => 'text-[60px] md:text-[100px]',     // h1-mob / h1
        'h2' => 'text-[40px] md:text-[64px]',      // h2-mob / h2
        'h3' => 'text-[32px] md:text-[48px]',      // h3-mob / h3
        'h4' => 'text-[24px] md:text-[32px]',      // h4-mob / h4
        'h5' => 'text-lg md:text-xl',
        'h6' => 'text-base md:text-lg',
    ];

    // Default leading based on level (matching design tokens)
    $defaultLeading = [
        'h1' => 'leading-[50px] md:leading-[86px]',  // h1-mob / h1
        'h2' => 'leading-[44px] md:leading-[66px]',  // h2-mob / h2
        'h3' => 'leading-[32px] md:leading-[48px]',  // h3-mob / h3
        'h4' => 'leading-[24px] md:leading-[38px]',  // h4-mob / h4
        'h5' => 'leading-normal',
        'h6' => 'leading-normal',
    ];

    // Use custom size or default
    $sizeClass = $size ?? $defaultSizes[$level] ?? 'text-3xl';
    $leadingClass = $leading ?? $defaultLeading[$level] ?? 'leading-tight';

    // Alignment
    $alignClass = match($align) {
        'center' => 'text-center',
        'right' => 'text-right',
        'left' => 'text-left',
        default => 'text-left',
    };

    // Uppercase
    $uppercaseClass = $uppercase ? 'uppercase' : '';

    // Combine classes
    $classes = trim("w-full {$font} {$sizeClass} {$color} {$alignClass} {$weight} {$leadingClass} {$uppercaseClass}");
@endphp

@if($url)
    <a href="{{ $url }}" class="block">
        <{{ $level }} class="{{ $classes }}">
            {{ $text }}
        </{{ $level }}>
    </a>
@else
    <{{ $level }} class="{{ $classes }}">
        {{ $text }}
    </{{ $level }}>
@endif
