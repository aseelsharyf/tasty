{{-- resources/views/components/post/title.blade.php --}}

@props([
    'title',
    'url' => '#',
    'size' => null, // Options: 'large' (h3), 'small' (h4), null (use h2 default)
    'tag' => 'h2',
    'align' => 'left', // Options: 'left', 'center', 'right'
    'color' => 'text-tasty-blue-black',
    'lineClamp' => null, // Optional: 'line-clamp-3', 'line-clamp-2', etc.
])

@php
    /**
     * Map size prop to heading level for typography
     * This ensures we use the typography system from app.css
     * which includes the correct display font (New Spirit/Playfair Display)
     *
     * large = h3 (32px/32px → 48px/48px)
     * small = h4 (24px/24px → 32px/38px)
     * null = use tag level (default h2: 40px/44px → 64px/66px)
     */
    $headingLevel = match($size) {
        'large' => 'h3',
        'small' => 'h4',
        default => $tag,
    };
@endphp

<x-ui.heading
    :level="$headingLevel"
    :text="$title"
    :color="$color"
    :align="$align"
    :lineClamp="$lineClamp"
    :url="$url"
/>
