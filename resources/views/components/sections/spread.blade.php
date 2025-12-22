{{-- The Spread Section --}}
<section class="w-full max-w-[1880px] mx-auto {{ $bgColorClass }} py-16 max-md:py-8" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    {{-- Mobile Title - Shows only on mobile when layout is scroll --}}
    @if($mobileLayout === 'scroll' && ($introImage || $titleSmall || $titleLarge))
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

    {{-- Horizontal Scroll --}}
    <div class="scroll-container pb-8 max-md:pb-6 container-main">
        @php
            $hasIntro = $introImage || $titleSmall || $titleLarge;
        @endphp
        <div class="flex pl-10 min-w-max max-md:pl-5 max-md:gap-8">
            {{-- Title Column (Desktop only) --}}
            @if($hasIntro)
                <div class="flex items-center shrink-0 max-md:hidden">
                    <div class="flex flex-col items-center justify-center gap-4 w-[320px]">
                        @if($introImage)
                            <div class="w-full max-w-[280px]">
                                <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-auto object-contain" style="mix-blend-mode: darken;">
                            </div>
                        @endif
                        <div class="flex flex-col items-center text-center text-blue-black">
                            <span class="text-h3">{{ $titleSmall }}</span>
                            <h2 class="text-h2 uppercase">{{ $titleLarge }}</h2>
                        </div>
                        @if($description)
                            <p class="text-body-md text-blue-black text-center">{{ $description }}</p>
                        @endif
                    </div>
                    {{-- Divider after title --}}
                    @if($showDividers)
                        <div class="w-px self-stretch {{ $dividerColor }} shrink-0 mx-10"></div>
                    @else
                        <div class="shrink-0 w-16"></div>
                    @endif
                </div>
            @endif

            {{-- Cards with dividers --}}
            @foreach($posts as $index => $post)
                <div class="flex items-center shrink-0 {{ $loop->last ? 'pr-10 max-md:pr-5' : '' }}">
                    <x-cards.spread
                        :post="$post"
                        :reversed="$loop->even"
                    />
                    {{-- Divider after card (except last) --}}
                    @if(!$loop->last)
                        @if($showDividers)
                            <div class="w-px self-stretch {{ $dividerColor }} shrink-0 max-md:hidden mx-10"></div>
                        @else
                            <div class="shrink-0 max-md:hidden w-12"></div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
