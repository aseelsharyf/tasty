{{-- resources/views/components/post/title.blade.php --}}

@props([
    'title',
    'url' => '#',
    'size' => null, // Options: 'large' (4xl/5xl), 'small' (2xl/3xl), null (use heading defaults)
    'tag' => 'h2',
    'align' => 'left', // Options: 'left', 'center', 'left md:center'
    'color' => 'text-stone-900',
    'lineClamp' => null, // Optional: 'line-clamp-3', 'line-clamp-2', etc.
])

@php
    // Size mapping - if null, let ui.heading use its defaults
    $sizeMapping = null;
    $leadingMapping = null;

    if ($size !== null) {
        $sizeMapping = match($size) {
            'large' => 'text-4xl md:text-5xl',
            'small' => 'text-2xl md:text-3xl',
            default => 'text-4xl md:text-5xl',
        };
        $leadingMapping = $size === 'large' ? 'leading-tight' : 'leading-normal';
    }
@endphp

<a href="{{ $url }}" class="w-full hover:opacity-70 transition-opacity duration-200 block">
    <x-ui.heading
        :level="$tag"
        :text="$title"
        :size="$sizeMapping"
        :color="$color"
        :align="$align"
        :leading="$leadingMapping"
        :lineClamp="$lineClamp"
    />
</a>
