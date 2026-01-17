{{-- Image Component with BlurHash Support --}}
@props([
    'src',
    'alt' => '',
    'blurhash' => null,
    'width' => null,
    'height' => null,
    'class' => '',
    'imgClass' => '',
    'lazy' => true,
    'objectFit' => 'cover',
    'objectPosition' => 'center',
])

@php
    $uniqueId = 'blurhash-' . uniqid();
    $aspectRatio = ($width && $height) ? ($height / $width * 100) : null;
@endphp

<div
    class="relative overflow-hidden {{ $class }}"
    @if($blurhash) data-blurhash="{{ $blurhash }}" data-blurhash-id="{{ $uniqueId }}" @endif
    @if($aspectRatio) style="padding-bottom: {{ $aspectRatio }}%" @endif
>
    {{-- Canvas placeholder for blurhash --}}
    @if($blurhash)
        <canvas
            id="{{ $uniqueId }}"
            width="32"
            height="32"
            class="absolute inset-0 w-full h-full"
            style="object-fit: {{ $objectFit }}; object-position: {{ $objectPosition }};"
        ></canvas>
    @else
        {{-- Fallback placeholder when no blurhash --}}
        <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>
    @endif

    {{-- Actual image --}}
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        @if($lazy) loading="lazy" @endif
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        class="w-full h-full transition-opacity duration-300 opacity-0 {{ $imgClass }}"
        style="object-fit: {{ $objectFit }}; object-position: {{ $objectPosition }};"
        onload="this.classList.remove('opacity-0'); this.classList.add('opacity-100'); if(this.previousElementSibling) this.previousElementSibling.style.display='none';"
        {{ $attributes }}
    >
</div>
