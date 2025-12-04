{{-- resources/views/components/ui/text.blade.php --}}

@props([
    'text' => '',
    'variant' => 'md', // Options: 'sm', 'md', 'lg', 'nav'
    'color' => 'text-stone-900',
    'align' => 'left', // Options: 'left', 'center', 'right'
    'tag' => 'p',
    'hideOnMobile' => false,
    'lineClamp' => null,
])

@php
    // Default size based on variant (matching design tokens)
    // body-sm-mob: 12px, body-sm: 14px
    // body-md-mob: 18px, body-md: 20px
    // body-lg-mob: 20px, body-lg: 24px
    // nav: 18px / 26px
    $defaultSizes = [
        'sm' => 'text-xs md:text-sm',              // 12px / 14px
        'md' => 'text-[18px] md:text-xl',          // 18px / 20px
        'lg' => 'text-xl md:text-2xl',             // 20px / 24px
        'nav' => 'text-[18px]',                    // 18px
    ];

    // Default leading based on variant (matching design tokens)
    $defaultLeading = [
        'sm' => 'leading-3',                       // 12px
        'md' => 'leading-6 md:leading-7',          // 24px / 28px
        'lg' => 'leading-[26px]',                  // 26px (same for mobile and desktop)
        'nav' => 'leading-[26px]',                 // 26px
    ];

    // Use defaults
    $sizeClass = $defaultSizes[$variant] ?? 'text-base';
    $leadingClass = $defaultLeading[$variant] ?? 'leading-normal';

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
    $classes = trim("w-full {$sizeClass} {$color} {$alignClass} {$leadingClass} {$visibilityClass} {$lineClamp}");
@endphp

<{{ $tag }} class="{{ $classes }}">
    {{ $text }}
</{{ $tag }}>
