{{-- resources/views/components/ui/button.blade.php --}}

@props([
    'url' => '#',
    'text' => '',
    'icon' => 'plus', // Options: 'plus', 'play', 'arrow-right', or null for no icon
    'iconPosition' => 'left', // Options: 'left', 'right'
    'bgColor' => 'bg-tasty-yellow',
    'textColor' => 'text-stone-900',
    'hoverBg' => 'hover:bg-stone-50',
    'paddingSize' => 'md', // Options: 'sm', 'md', 'lg' - Controls button padding
    'textSize' => null, // Optional: Override text size (e.g., 'text-xs', 'text-2xl')
    'iconSize' => null, // Optional: Override icon size (e.g., 'text-lg', 'text-xl')
    'textStyle' => 'bold', // Options: 'bold' (uppercase, tracking-widest), 'normal'
    'iconRotate' => false, // Enable rotation on hover
])

@php
    // Padding size classes with consistent aspect ratio
    $paddingClasses = match($paddingSize) {
        'sm' => 'px-6 py-2',           // Small button
        'md' => 'px-8 py-3',           // Medium button (More Updates)
        'lg' => 'pl-4 pr-5 py-3',      // Large button (Watch) - matches design spec
        default => 'px-8 py-3',
    };

    // Gap size - use custom or default based on padding size
    $gapClass = match($paddingSize) {
        'sm' => 'gap-2',
        'md' => 'gap-2 md:gap-3',
        'lg' => 'gap-2',
        default => 'gap-2 md:gap-3',
    };

    // Text size - use custom or default based on padding size
    $textSizeClass = $textSize ?? match($paddingSize) {
        'sm' => 'text-xs md:text-sm',
        'md' => 'text-xs md:text-sm',
        'lg' => 'text-xl',
        default => 'text-xs md:text-sm',
    };

    // Icon size - use custom or default based on padding size (w-6 h-6 for lg)
    $iconSizeClass = $iconSize ?? match($paddingSize) {
        'sm' => 'text-base',
        'md' => 'text-lg',
        'lg' => 'w-6 h-6',
        default => 'text-lg',
    };

    // Text style classes
    $textStyleClasses = $textStyle === 'bold'
        ? 'font-bold uppercase tracking-widest'
        : 'font-normal';

    // Icon animation
    $iconAnimation = $iconRotate
        ? 'group-hover:rotate-90 transition-transform duration-300'
        : 'transition-transform duration-300';
@endphp

<a
    href="{{ $url }}"
    {{ $attributes->merge(['class' => "group {$bgColor} {$paddingClasses} rounded-full inline-flex items-center justify-center " . ($iconPosition === 'left' ? $gapClass : $gapClass . ' flex-row-reverse') . " shadow-sm hover:shadow-md {$hoverBg} transition-all duration-300"]) }}
>
    @if($icon)
        <i class="fa-solid fa-{{ $icon }} {{ $iconSizeClass }} {{ $textColor }} {{ $iconAnimation }}"></i>
    @endif

    <span class="{{ $textStyleClasses }} {{ $textSizeClass }} {{ $textColor }}">{{ $text }}</span>
</a>
