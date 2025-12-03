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
    // Size classes
    $sizeClasses = match($size) {
        'large' => 'text-4xl md:text-5xl',
        'small' => 'text-2xl md:text-3xl',
        default => 'text-4xl md:text-5xl',
    };

    // Alignment classes
    $alignClasses = match($align) {
        'center' => 'text-center',
        'left' => 'text-left',
        'left md:center' => 'text-left md:text-center',
        default => 'text-left',
    };

    // Leading classes based on size
    $leadingClasses = $size === 'large' ? 'leading-tight' : '';

    // Combine all classes
    $classes = trim("w-full font-serif {$sizeClasses} {$color} {$alignClasses} font-normal m-0 hover:opacity-70 transition-opacity duration-200 {$leadingClasses} {$lineClamp}");
@endphp

<a href="{{ $url }}" class="w-full">
    <{{ $tag }} class="{{ $classes }}">
        {{ $title }}
    </{{ $tag }}>
</a>
