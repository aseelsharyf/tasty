{{-- Shared Button Component --}}
@props([
    'tag' => 'button',
    'href' => null,
    'url' => null,
    'text' => null,
    'type' => 'button',
    'variant' => 'yellow',
    'icon' => null,
    'iconPosition' => 'right',
    'loading' => false,
    'disabled' => false,
    'class' => '',
])

@php
    // Support both href and url props
    $href = $href ?? $url;

    // Determine the tag to use
    $tag = $href ? 'a' : ($tag ?? 'button');

    // Variant classes
    $variantClass = match($variant) {
        'yellow' => 'btn-yellow',
        'white' => 'btn-white',
        default => 'btn-yellow',
    };

    // Icon SVG paths
    $icons = [
        'arrow-right' => '<path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'plus' => '<path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'play' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M10 8.5L16 12L10 15.5V8.5Z" fill="currentColor"/>',
        'check' => '<path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];

    $iconSvg = $icon && isset($icons[$icon]) ? $icons[$icon] : null;

    // Loading spinner SVG
    $loadingSpinner = '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>';

    // Build class string
    $classes = "btn {$variantClass} {$class}";
@endphp

<{{ $tag }}
    @if($tag === 'a')
        href="{{ $href }}"
    @else
        type="{{ $type }}"
    @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled || $loading)
        @if($tag === 'button') disabled @endif
        aria-disabled="true"
    @endif
>
    @if($iconPosition === 'left' && $iconSvg && !$loading)
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">{!! $iconSvg !!}</svg>
    @endif

    @if($loading)
        <svg class="animate-spin" width="24" height="24" viewBox="0 0 24 24" fill="none">{!! $loadingSpinner !!}</svg>
    @endif

    <span>{{ $text ?? $slot }}</span>

    @if($iconPosition === 'right' && $iconSvg && !$loading)
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">{!! $iconSvg !!}</svg>
    @endif
</{{ $tag }}>
