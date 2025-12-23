{{-- Hero Section - Split layout with image left, content right --}}
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

{{-- Pull hero up behind the navbar (desktop only, tablet/mobile has no overlap) --}}
<section class="w-full flex justify-center relative z-0 -mt-[96px] md:-mt-[112px] max-lg:mt-0">
    {{-- Hero container - max-width 1880px, height 968px at 1440px width (scales proportionally) --}}
    {{-- Mobile: uses CSS variable for viewport height with JS fallback --}}
    <div class="flex w-full max-w-[1880px] h-[clamp(500px,67.22vw,1265px)]
        max-lg:flex-col max-lg:h-[calc(var(--vh,1vh)*100)] max-lg:overflow-hidden"
        x-data x-init="
            const setVh = () => document.documentElement.style.setProperty('--vh', window.innerHeight * 0.01 + 'px');
            setVh();
            window.addEventListener('resize', setVh);
        ">
        {{-- Hero Image - Left 50% / Mobile: fills top portion --}}
        @if($manual)
            <div class="block relative w-1/2 h-full overflow-hidden flex-shrink-0
                max-lg:w-full max-lg:flex-1 max-lg:h-0">
                <img
                    src="{{ $heroImage }}"
                    alt="{{ $heroImageAlt }}"
                    class="w-full h-full object-cover object-center"
                >
            </div>
        @else
            <a href="{{ $heroUrl }}" class="block relative w-1/2 h-full overflow-hidden flex-shrink-0
                max-lg:w-full max-lg:flex-1 max-lg:h-0">
                <img
                    src="{{ $heroImage }}"
                    alt="{{ $heroImageAlt }}"
                    class="w-full h-full object-cover object-center"
                >
            </a>
        @endif

        {{-- Hero Content - Right 50% / Mobile: fixed height based on content --}}
        {{-- Mobile CSS (Figma): padding 32px 20px 64px 20px, gap 24px, centered --}}
        <div class="w-1/2 h-full {{ $bgColorClass }} px-16 py-24 flex flex-col {{ $contentAlignment }} gap-10
            max-lg:w-full max-lg:h-auto max-lg:flex-shrink-0 max-lg:px-5 max-lg:pt-8 max-lg:pb-16 max-lg:items-center max-lg:justify-center max-lg:text-center max-lg:gap-6" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            {{-- Meta: Category • Author • Date --}}
            <div class="flex flex-wrap items-center gap-5 text-body-sm uppercase text-blue-black {{ $metaAlignment }} max-lg:justify-center">
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

            {{-- Kicker (large) & Title (small) --}}
            {{-- Mobile (Figma 393px): Kicker 60px/50px/-2.4px, Title 40px/44px/-1.6px --}}
            {{-- Using clamp for responsive scaling on smaller devices --}}
            <div class="flex flex-col gap-4 w-full max-lg:gap-3">
                @if($manual)
                    @if($heroKicker)
                        <h1 class="font-display text-[104px] leading-[86px] tracking-[-4.16px] uppercase text-blue-black max-lg:text-[clamp(40px,15vw,60px)] max-lg:leading-[0.83] max-lg:tracking-[-0.04em]">{{ $heroKicker }}</h1>
                    @endif
                    @if($heroTitle)
                        <p class="font-display text-[64px] leading-[59px] tracking-[-2.56px] text-blue-black max-lg:text-[clamp(28px,10vw,40px)] max-lg:leading-[1.1] max-lg:tracking-[-0.04em]">{{ $heroTitle }}</p>
                    @endif
                @else
                    <a href="{{ $heroUrl }}" class="hover:opacity-80 transition-opacity">
                        @if($heroKicker)
                            <h1 class="font-display text-[104px] leading-[86px] tracking-[-4.16px] uppercase text-blue-black max-lg:text-[clamp(40px,15vw,60px)] max-lg:leading-[0.83] max-lg:tracking-[-0.04em]">{{ $heroKicker }}</h1>
                        @endif
                        <p class="font-display text-[64px] leading-[59px] tracking-[-2.56px] text-blue-black max-lg:text-[clamp(28px,10vw,40px)] max-lg:leading-[1.1] max-lg:tracking-[-0.04em]">{{ $heroTitle }}</p>
                    </a>
                @endif
            </div>

            {{-- Button --}}
            <a href="{{ $heroUrl }}" class="btn btn-{{ $buttonColor }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ $buttonText }}</span>
            </a>
        </div>
    </div>
</section>
