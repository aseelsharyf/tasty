@props([
    'adSlot' => null,
    'size' => 'auto',
    'bgColor' => '#F7F7F7',
    'paddingTop' => 'medium',
    'paddingBottom' => 'medium',
])

@php
    // Apply preset sizes (auto means no fixed dimensions - let the ad network handle it)
    $dimensions = match($size) {
        'leaderboard' => ['width' => 728, 'height' => 90],
        'medium-rectangle' => ['width' => 300, 'height' => 250],
        'large-rectangle' => ['width' => 336, 'height' => 280],
        'half-page' => ['width' => 300, 'height' => 600],
        'billboard' => ['width' => 970, 'height' => 250],
        'mobile-banner' => ['width' => 320, 'height' => 50],
        'large-mobile-banner' => ['width' => 320, 'height' => 100],
        default => null, // auto
    };

    $adWidth = $dimensions['width'] ?? null;
    $adHeight = $dimensions['height'] ?? null;

    $paddingClasses = [
        'top' => match($paddingTop) {
            'none' => 'pt-0',
            'small' => 'pt-4 lg:pt-6',
            'medium' => 'pt-8 lg:pt-12',
            'large' => 'pt-12 lg:pt-20',
            default => 'pt-8 lg:pt-12',
        },
        'bottom' => match($paddingBottom) {
            'none' => 'pb-0',
            'small' => 'pb-4 lg:pb-6',
            'medium' => 'pb-8 lg:pb-12',
            'large' => 'pb-12 lg:pb-20',
            default => 'pb-8 lg:pb-12',
        },
    ];
@endphp

<section
    class="w-full {{ $paddingClasses['top'] }} {{ $paddingClasses['bottom'] }}"
    style="background-color: {{ $bgColor }};"
>
    <div class="container-main px-4">
        <x-ads.slot
            :adSlot="$adSlot"
            :width="$adWidth"
            :height="$adHeight"
            class="mx-auto"
        />
    </div>
</section>
