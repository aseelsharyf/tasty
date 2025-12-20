{{-- Minimal Article Header - Clean typography-only --}}
@props([
    'post' => [],
    'isRtl' => false,
    'readingTime' => null,
])

@php
    $title = $post['title'] ?? 'Untitled';
    $subtitle = $post['subtitle'] ?? null;
    $category = $post['category'] ?? null;
@endphp

{{-- Minimal Header Section --}}
<div class="bg-white pt-24 lg:pt-32 pb-16 lg:pb-24">
    <div class="max-w-[894px] mx-auto px-4 lg:px-0">
        <div class="flex flex-col gap-8 {{ $isRtl ? 'text-right' : 'text-left' }}">
            {{-- Category Tags --}}
            @if($category)
                <div class="flex items-center gap-5 text-body-sm uppercase text-tasty-blue-black/60 {{ $isRtl ? 'justify-end' : '' }}">
                    <span>tasty feature</span>
                    <span>&bull;</span>
                    <span>{{ $category }}</span>
                </div>
            @endif

            {{-- Main Title --}}
            <h1 class="text-h1 text-tasty-blue-black">
                {{ $title }}
            </h1>

            @if($subtitle)
                <p class="text-h3 text-tasty-blue-black/80">
                    {{ $subtitle }}
                </p>
            @endif

            {{-- Description --}}
            @if($post['description'] ?? null)
                <p class="text-body-lg text-tasty-blue-black/70">
                    {{ $post['description'] }}
                </p>
            @endif
        </div>
    </div>
</div>
