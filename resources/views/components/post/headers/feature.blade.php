{{-- Featured Article Header - Full-width hero with gradient to yellow --}}
@props([
    'post',  // Post model
])

@php
    $category = $post->categories->first();
    $photographer = $post->getCustomField('photographer');
@endphp

<header class="w-full max-w-[1880px] mx-auto">
    {{-- Hero Image with Gradient --}}
    <div class="relative w-full h-[400px] sm:h-[600px] lg:h-[840px]">
        @if($post->featured_image_url)
            <img
                src="{{ $post->featured_image_url }}"
                alt="{{ $post->title }}"
                class="absolute inset-0 w-full h-full object-cover object-center"
            />
        @else
            <div class="absolute inset-0 bg-tasty-blue-black/20"></div>
        @endif
        {{-- Gradient overlay fading to yellow --}}
        <div class="absolute inset-0 bg-gradient-to-b from-transparent from-40% via-tasty-yellow/50 via-70% to-tasty-yellow"></div>
    </div>

    {{-- Title Section on Yellow --}}
    <div class="bg-tasty-yellow pb-12 lg:pb-16">
        <div class="flex flex-col gap-8 lg:gap-10 items-center text-center px-4 lg:px-10">
            {{-- Title & Subtitle --}}
            <div class="flex flex-col gap-4 items-center w-full">
                <h1 class="text-h1 text-tasty-blue-black uppercase">
                    {{ $post->title }}
                </h1>
                @if($post->subtitle)
                    <p class="text-h2 text-tasty-blue-black">
                        {{ $post->subtitle }}
                    </p>
                @endif
            </div>

            {{-- Category Tags --}}
            @if($category)
                <div class="flex items-center justify-center gap-5 text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans">
                    <span>tasty feature</span>
                    <span>&bull;</span>
                    <a href="{{ route('category.show', $category->slug) }}" class="hover:underline">
                        {{ $category->name }}
                    </a>
                </div>
            @endif

            {{-- Description/Excerpt --}}
            @if($post->excerpt)
                <p class="text-body-lg text-tasty-blue-black text-center max-w-[649px]">
                    {{ $post->excerpt }}
                </p>
            @endif

            {{-- Author/Photographer/Date Row --}}
            <div class="flex items-center justify-center gap-5 text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans flex-wrap">
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
            <div class="bg-white px-6 py-3 rounded-full flex items-center gap-5">
                <span class="font-sans text-[20px] leading-[26px] font-normal text-tasty-blue-black">Powered by</span>
                <img src="{{ Vite::asset('resources/images/dhiraagu.png') }}" alt="Dhiraagu" class="w-[204px] h-[51px] aspect-[4/1] object-contain" />
            </div>

            {{-- Share Icons --}}
            <x-article.share-icons :url="request()->url()" :title="$post->title" />
        </div>
    </div>
</header>
