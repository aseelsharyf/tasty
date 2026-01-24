{{-- Hero Section --}}
@php
    // Determine if we have content to display (either from post or manual mode)
    $hasContent = $manual || $post;

    if (!$hasContent) {
        return;
    }

    // Get values from post or manual props
    $heroKicker = $manual ? $kicker : $post->kicker;
    $heroTitle = $manual ? $title : $post->title;
    $heroImage = $manual ? $image : $post->featured_image_url;
    $heroImageAlt = $manual ? $imageAlt : $post->title;
    $heroCategory = $manual ? $category : $post->categories->first()?->name;
    $heroCategoryUrl = $manual ? $categoryUrl : ($post->categories->first() ? route('category.show', $post->categories->first()->slug) : null);
    $heroAuthor = $manual ? $author : $post->author?->name;
    $heroAuthorUrl = $manual ? $authorUrl : $post->author?->url;
    $heroDate = $manual ? $date : $post->published_at?->format('F j, Y');
    $heroUrl = $manual ? $buttonUrl : $post->url;

    // Content alignment classes
    // Center: vertically centered, text centered
    // Bottom: aligned to bottom, text left-aligned
    $contentAlignment = $alignment === 'bottom'
        ? 'justify-end items-start text-left'
        : 'justify-center items-center text-center';

    // Meta and title alignment
    $metaAlignment = $alignment === 'bottom' ? 'justify-start' : 'justify-center';
@endphp

<section class="w-full relative z-0">
    <div class="flex w-full lg:h-[calc(100vh-80px)]
        max-lg:flex-col max-lg:items-start max-lg:w-full">
        {{-- Hero Image --}}
        @if($manual)
            <div class="hero-image-container block relative w-full lg:w-1/2 lg:flex-none overflow-hidden">
                <img
                    src="{{ $heroImage }}"
                    alt="{{ $heroImageAlt }}"
                    class="absolute inset-0 w-full h-full object-cover object-top"
                >
            </div>
        @else
            <a href="{{ $heroUrl }}" class="hero-image-container block relative w-full lg:w-1/2 lg:flex-none overflow-hidden">
                <img
                    src="{{ $heroImage }}"
                    alt="{{ $heroImageAlt }}"
                    class="absolute inset-0 w-full h-full object-cover"
                >
            </a>
        @endif

        {{-- Hero Content --}}
        <div class="w-full lg:w-1/2 self-stretch {{ $bgColorClass }} pt-8 pb-16 px-5 lg:p-10 lg:py-24 flex flex-col {{ $contentAlignment }} gap-6 lg:gap-10
            max-lg:shrink-0 max-lg:items-center max-lg:justify-center max-lg:text-center" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
          <div class="flex flex-col {{ $contentAlignment }} gap-6 lg:gap-10 max-w-3xl">
            {{-- Meta: Category • Author • Date --}}
            <div class="flex flex-wrap items-center gap-2.5 text-[12px] leading-[12px] lg:text-body-sm uppercase text-blue-black {{ $metaAlignment }} max-lg:justify-center">
                @if($heroCategory)
                    @if($heroCategoryUrl)
                        <a href="{{ $heroCategoryUrl }}" class="hover:underline">{{ $heroCategory }}</a>
                    @else
                        <span>{{ $heroCategory }}</span>
                    @endif
                    <span class="text-blue-black/50">•</span>
                @endif
                @if($heroAuthor)
                    @if($heroAuthorUrl)
                        <a href="{{ $heroAuthorUrl }}" class="hover:underline">BY {{ strtoupper($heroAuthor) }}</a>
                    @else
                        <span>BY {{ strtoupper($heroAuthor) }}</span>
                    @endif
                    <span class="text-blue-black/50">•</span>
                @endif
                @if($heroDate)
                    <span>{{ strtoupper($heroDate) }}</span>
                @endif
            </div>

            {{-- Kicker & Title --}}
            <div class="flex flex-col gap-3 lg:gap-4 w-full">
                @if($manual)
                    @if($heroKicker)
                        <h1 class="font-display text-[60px] leading-[50px] tracking-[-2.4px] lg:text-[72px] lg:leading-[1] lg:tracking-[-0.04em] uppercase text-blue-black">{{ $heroKicker }}</h1>
                    @endif
                    @if($heroTitle)
                        <p class="font-display text-[40px] leading-[44px] tracking-[-1.6px] lg:text-[48px] lg:leading-[1.1] lg:tracking-[-0.04em] text-blue-black">{{ $heroTitle }}</p>
                    @endif
                @else
                    <a href="{{ $heroUrl }}" class="hover:opacity-80 transition-opacity">
                        @if($heroKicker)
                            <h1 class="font-display text-[60px] leading-[50px] tracking-[-2.4px] lg:text-[72px] lg:leading-[1] lg:tracking-[-0.04em] uppercase text-blue-black">{{ $heroKicker }}</h1>
                        @endif
                        <p class="font-display text-[40px] leading-[44px] tracking-[-1.6px] lg:text-[48px] lg:leading-[1.1] lg:tracking-[-0.04em] text-blue-black">{{ $heroTitle }}</p>
                    </a>
                @endif
            </div>

            {{-- Button --}}
            <a href="{{ $heroUrl }}" class="btn btn-{{ $buttonColor }} !pl-[18px] !pr-5 !py-3 !gap-2 !text-[20px] !leading-[26px] lg:!px-8 lg:!py-4 lg:!gap-3 lg:!text-base lg:!leading-normal">
                <svg class="!w-6 !h-6 lg:!w-5 lg:!h-5" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ $buttonText }}</span>
            </a>
          </div>
        </div>
    </div>
</section>
