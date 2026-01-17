{{-- Minimal Article Header - Side-by-side with rounded image, includes meta --}}
@props([
    'post',  // Post model
    'includeMeta' => true,  // Whether to include author/date/share (since it's built into this header)
])

@php
    $category = $post->categories->first();
    $photographer = $post->getCustomField('photographer');
    $url = request()->url();
    $anchor = $post->featured_image_anchor ?? ['x' => 50, 'y' => 0];
    $objectPosition = ($anchor['x'] ?? 50) . '% ' . ($anchor['y'] ?? 50) . '%';
@endphp

<header class="w-full bg-tasty-yellow pt-[96px] md:pt-[112px]">
    <div class="max-w-[1440px] mx-auto p-6 sm:p-8 lg:p-10">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-10 items-center">
            {{-- Image (Left) --}}
            <div class="w-full lg:flex-1 aspect-square lg:aspect-auto lg:h-[500px] relative rounded-xl overflow-hidden">
                @if($post->featured_image_url)
                    <img
                        src="{{ $post->featured_image_url }}"
                        alt="{{ $post->title }}"
                        class="absolute inset-0 w-full h-full object-cover rounded-xl"
                        style="object-position: {{ $objectPosition }}"
                    />
                @else
                    <div class="absolute inset-0 bg-tasty-blue-black/10 rounded-xl"></div>
                @endif
            </div>

            {{-- Content (Right) --}}
            <div class="w-full lg:flex-1 flex flex-col gap-12 lg:gap-16">
                {{-- Category Tag --}}
                @if($category)
                    <div class="flex items-center">
                        <a href="{{ route('category.show', $category->slug) }}" class="text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans hover:underline">
                            {{ $category->name }}
                        </a>
                    </div>
                @endif

                {{-- Kicker, Title & Excerpt --}}
                <div class="flex flex-col gap-3">
                    @if($post->kicker)
                        <span class="text-h2 text-tasty-blue-black uppercase">
                            {{ $post->kicker }}
                        </span>
                    @endif
                    <h1 class="text-h3 text-tasty-blue-black">
                        {{ $post->title }}
                    </h1>
                    @if($post->excerpt)
                        <p class="text-[18px] leading-[24px] text-black/90 font-sans">
                            {{ $post->excerpt }}
                        </p>
                    @endif
                </div>

                {{-- Credits & Share (in card, aligned left) --}}
                @if($includeMeta)
                    <div class="flex flex-col gap-8">
                        {{-- Sponsor Badge --}}
                        <x-article.sponsor-badge :sponsor="$post->sponsor" />

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

                        {{-- Share Icons (left-aligned in card) --}}
                        <x-article.share-icons :url="$url" :title="$post->title" align="left" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</header>
