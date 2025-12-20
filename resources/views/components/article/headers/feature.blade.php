{{-- Feature Article Header - Full-bleed hero with title overlay --}}
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
    $author = $post['author'] ?? null;
    $publishedAt = $post['published_at'] ?? null;
@endphp

{{-- Full-bleed Hero Section with Title Overlay --}}
<div class="relative w-full min-h-[600px] lg:min-h-[100vh] flex items-end">
    {{-- Background Image --}}
    @if($featuredImage)
        <div class="absolute inset-0">
            <img
                src="{{ $featuredImage }}"
                alt="{{ $title }}"
                class="w-full h-full object-cover object-center"
            />
        </div>
        {{-- Dark Gradient Overlay for text readability --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
    @else
        {{-- Fallback solid background if no image --}}
        <div class="absolute inset-0 bg-tasty-blue-black"></div>
    @endif

    {{-- Content Overlay --}}
    <div class="relative z-10 w-full pb-16 lg:pb-24">
        <div class="max-w-[1440px] mx-auto px-4 lg:px-10">
            <div class="flex flex-col gap-6 lg:gap-10 {{ $isRtl ? 'items-end text-right' : 'items-start text-left' }}">
                {{-- Category Tags --}}
                @if($category)
                    <div class="flex items-center gap-5 text-body-sm uppercase text-white/80">
                        <span>tasty feature</span>
                        <span>&bull;</span>
                        <span>{{ $category }}</span>
                    </div>
                @endif

                {{-- Main Title --}}
                <h1 class="text-h1-hero text-white max-w-[1000px]">
                    {{ $title }}
                </h1>

                @if($subtitle)
                    <p class="text-h3 text-white/90 max-w-[800px]">
                        {{ $subtitle }}
                    </p>
                @endif

                {{-- Author & Date Row --}}
                <div class="flex flex-wrap items-center gap-5 text-body-sm uppercase text-white/80 mt-4">
                    @if($author && ($author['name'] ?? null))
                        <a href="#" class="underline underline-offset-4 hover:text-white transition-colors">
                            BY {{ $author['name'] }}
                        </a>
                    @endif
                    @if($publishedAt)
                        <span>&bull;</span>
                        <span>{{ \Carbon\Carbon::parse($publishedAt)->format('F j, Y') }}</span>
                    @endif
                    @if($readingTime)
                        <span>&bull;</span>
                        <span>{{ $readingTime }} MIN READ</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
