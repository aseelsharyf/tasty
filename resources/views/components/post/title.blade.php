{{-- resources/views/components/post/title.blade.php --}}

@props([
    'title',
    'url' => '#',
    'size' => 'large', // Options: 'large' (4xl/5xl), 'small' (2xl/3xl)
    'tag' => 'h2',
    'align' => 'left', // Options: 'left', 'center', 'left md:center'
    'color' => 'text-stone-900',
    'lineClamp' => null, // Optional: 'line-clamp-3', 'line-clamp-2', etc.
])

@php
    // Size mapping
    $sizeMapping = match($size) {
        'large' => 'text-4xl md:text-5xl',
        'small' => 'text-2xl md:text-3xl',
        default => 'text-4xl md:text-5xl',
    };

    // Leading based on size
    $leadingMapping = $size === 'large' ? 'leading-tight' : 'leading-normal';
@endphp

<a href="{{ $url }}" class="w-full hover:opacity-70 transition-opacity duration-200 block">
    <x-ui.heading
        :level="$tag"
        :text="$title"
        :size="$sizeMapping"
        :color="$color"
        :align="$align"
        :leading="$leadingMapping . ($lineClamp ? ' ' . $lineClamp : '')"
    />
</a>
