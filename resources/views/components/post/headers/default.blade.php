{{-- Default Article Header - Split layout with image left, text right --}}
@props([
    'post',  // Post model
])

@php
    $category = $post->categories->first();
    $photographer = $post->getCustomField('photographer');
    $anchor = $post->featured_image_anchor ?? ['x' => 50, 'y' => 0];
    $objectPosition = ($anchor['x'] ?? 50) . '% ' . ($anchor['y'] ?? 50) . '%';
@endphp

<header class="w-full max-w-[1880px] mx-auto">
    <div class="flex flex-col lg:flex-row min-h-[854px] lg:min-h-[864px]">
        {{-- Image Section (Left) --}}
        <div class="hero-image-container w-full lg:w-[65%] lg:flex-none relative">
            @if($post->featured_image_url)
                <img
                    src="{{ $post->featured_image_url }}"
                    alt="{{ $post->title }}"
                    class="absolute inset-0 w-full h-full object-cover"
                    style="object-position: {{ $objectPosition }}"
                />
            @else
                <div class="absolute inset-0 bg-tasty-blue-black/10"></div>
            @endif
        </div>

        {{-- Text Section (Right) --}}
        <div class="w-full lg:w-[35%] bg-tasty-yellow flex flex-col justify-end p-6 sm:p-8 lg:p-10 lg:py-24 gap-8 lg:gap-10">
            {{-- Category Tag --}}
            @if($category)
                <div class="flex items-center">
                    <a href="{{ route('category.show', $category->slug) }}" class="text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans hover:underline">
                        {{ $category->name }}
                    </a>
                </div>
            @endif

            {{-- Kicker, Title & Excerpt --}}
            <div class="flex flex-col gap-4">
                @if($post->kicker)
                    <span class="text-h2 text-tasty-blue-black uppercase">
                        {{ $post->kicker }}
                    </span>
                @endif
                <h1 class="text-h3 text-tasty-blue-black">
                    {{ $post->title }}
                </h1>
                @if($post->excerpt)
                    <p class="text-body-lg text-tasty-blue-black">
                        {{ $post->excerpt }}
                    </p>
                @endif
            </div>

            {{-- Author/Photographer/Date Row --}}
            <div class="flex items-center gap-5 text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans flex-wrap">
                @if($post->author)
                    <a href="{{ $post->author->url ?? '#' }}" class="underline underline-offset-4 hover:no-underline">
                        BY {{ $post->author->name }}
                    </a>
                @endif

                @if($photographer)
                    @if($post->author)
                        <span>&bull;</span>
                    @endif
                    <span>PHOTO BY {{ $photographer }}</span>
                @endif

                @if($post->published_at)
                    @if($post->author || $photographer)
                        <span>&bull;</span>
                    @endif
                    <span>{{ $post->published_at->format('F j, Y') }}</span>
                @endif
            </div>

            {{-- Sponsor Badge --}}
            <x-article.sponsor-badge :sponsor="$post->sponsor" />
        </div>
    </div>
</header>
