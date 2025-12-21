{{-- The Spread Section --}}
<section class="w-full max-w-[1880px] mx-auto {{ $bgColorClass }} py-16 max-md:py-8" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    {{-- Mobile Title - Shows only on mobile when layout is scroll --}}
    @if($mobileLayout === 'scroll')
        <div class="hidden max-md:flex flex-col items-center justify-center gap-5 px-5 pb-8">
            @if($introImage)
                <div class="w-full h-[250px]">
                    <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-full object-contain" style="mix-blend-mode: darken;">
                </div>
            @endif
            <div class="flex flex-col items-center text-center text-blue-black">
                <span class="text-h2">{{ $titleSmall }}</span>
                <h2 class="text-h1 uppercase">{{ $titleLarge }}</h2>
            </div>
            @if($description)
                <p class="text-body-large text-blue-black text-center">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{-- Horizontal Scroll with Title Inline (Desktop) or Grid (Mobile grid mode) --}}
    <div class="{{ $mobileLayout === 'scroll' ? 'scroll-container' : '' }} pb-32 max-md:pb-16 container-main">
        <div class="flex gap-0 pl-10 {{ $mobileLayout === 'scroll' ? 'min-w-max max-md:pl-5' : 'max-md:flex-wrap max-md:pl-0 max-md:gap-8' }}">
            {{-- Title Column - Inline with cards (Desktop only, or always visible in grid mode) --}}
            <div class="flex flex-col items-center justify-center gap-5 w-[424px] h-[889px] shrink-0 {{ $mobileLayout === 'scroll' ? 'max-md:hidden' : 'max-md:w-full max-md:h-auto max-md:py-8' }}">
                @if($introImage)
                    <div class="w-full h-[430px] max-md:h-[250px]">
                        <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-full object-contain" style="mix-blend-mode: darken;">
                    </div>
                @endif
                <div class="flex flex-col items-center text-center text-blue-black">
                    <span class="text-h2">{{ $titleSmall }}</span>
                    <h2 class="text-h1 uppercase">{{ $titleLarge }}</h2>
                </div>
                @if($description)
                    <p class="text-body-large text-blue-black text-center">{{ $description }}</p>
                @endif
            </div>

            {{-- Divider (Desktop only in scroll mode) --}}
            @if($showDividers && $mobileLayout === 'scroll')
                <div class="w-px {{ $dividerColor }} mx-10 shrink-0 max-md:hidden"></div>
            @endif

            {{-- Cards --}}
            @foreach($posts as $index => $post)
                {{-- Divider between cards --}}
                @if($showDividers && $index > 0 && $mobileLayout === 'scroll')
                    <div class="w-px {{ $dividerColor }} mx-10 shrink-0 max-md:hidden"></div>
                @endif

                <div class="{{ $mobileLayout === 'grid' ? 'max-md:w-full' : '' }}">
                    <x-cards.spread
                        :post="$post"
                        :reversed="$loop->even"
                    />
                </div>
            @endforeach
        </div>
    </div>
</section>
