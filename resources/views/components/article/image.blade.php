{{-- Article Image Block --}}
@props([
    'src' => '',
    'alt' => '',
    'caption' => '',
    'size' => 'full', // full, wide, narrow
    'aspectRatio' => '16/9', // or 'auto' for natural aspect
])

@php
    $containerClass = match($size) {
        'full' => 'article-image-full',
        'wide' => 'article-image-wide',
        'narrow' => 'article-content-narrow',
        default => 'article-image-full',
    };
@endphp

<figure class="{{ $containerClass }} flex flex-col gap-6 md:gap-8 py-8 md:py-16">
    <div class="w-full overflow-hidden {{ $aspectRatio !== 'auto' ? 'relative' : '' }}" @if($aspectRatio !== 'auto') style="aspect-ratio: {{ $aspectRatio }};" @endif>
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            class="w-full h-full object-cover {{ $aspectRatio !== 'auto' ? 'absolute inset-0' : '' }}"
        >
    </div>
    @if($caption)
        <figcaption class="article-caption max-w-[818px] mx-auto text-center px-4">
            {{ $caption }}
        </figcaption>
    @endif
</figure>
