{{-- Default Article Header - Based on Figma design 2048-788 --}}
@props([
    'post' => [],
    'isRtl' => false,
    'readingTime' => null,
])

@php
    $title = $post['title'] ?? 'Untitled';
    $subtitle = $post['subtitle'] ?? null;
    $category = $post['category'] ?? null;
    $featuredImage = $post['featured_image_url'] ?? null;
@endphp

{{-- Hero Image Section --}}
@if($featuredImage)
    <div class="relative w-full h-[400px] lg:h-[840px]">
        {{-- Background Image --}}
        <div class="absolute inset-0">
            <img
                src="{{ $featuredImage }}"
                alt="{{ $title }}"
                class="w-full h-full object-cover object-center"
            />
        </div>
        {{-- Gradient Overlay fading to yellow --}}
        <div class="absolute inset-0 bg-gradient-to-b from-transparent from-40% via-tasty-yellow/50 via-70% to-tasty-yellow"></div>
    </div>
@endif

{{-- Title Section - Yellow Background --}}
<div class="bg-tasty-yellow {{ $featuredImage ? '' : 'pt-32' }} pb-16">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-10">
        <div class="flex flex-col gap-8 lg:gap-10 items-center text-center">
            {{-- Main Title --}}
            <div class="flex flex-col gap-4 items-center w-full">
                <h1 class="text-h1 text-tasty-blue-black {{ $isRtl ? 'text-right' : '' }}">
                    {{ $title }}
                </h1>
                @if($subtitle)
                    <p class="text-h2 text-tasty-blue-black {{ $isRtl ? 'text-right' : '' }}">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>

            {{-- Category Tags --}}
            @if($category)
                <div class="flex items-center justify-center gap-5 text-body-sm uppercase text-tasty-blue-black">
                    <span>tasty feature</span>
                    <span>&bull;</span>
                    <span>{{ $category }}</span>
                </div>
            @endif

            {{-- Description (using subtitle as description if no separate field) --}}
            @if($post['description'] ?? null)
                <p class="text-body-lg text-tasty-blue-black text-center max-w-[649px] {{ $isRtl ? 'text-right' : '' }}">
                    {{ $post['description'] }}
                </p>
            @endif
        </div>
    </div>
</div>
