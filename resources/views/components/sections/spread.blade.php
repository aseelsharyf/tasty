{{-- The Spread Section --}}
<section class="w-full max-w-[1880px] mx-auto {{ $bgColorClass }} py-16 max-lg:py-8" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    {{-- Mobile Intro - Shows on mobile when intro is enabled --}}
    @if($showIntro)
        <div class="hidden max-lg:flex flex-col items-center justify-center gap-5 px-5 pb-8">
            @if($introImage)
                <div class="w-full h-[182px]">
                    <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-full object-contain" style="mix-blend-mode: darken;">
                </div>
            @endif
            <div class="flex flex-col items-center text-center text-blue-black">
                <span class="text-h2">{{ $titleSmall }}</span>
                <h2 class="text-h1 uppercase">{{ $titleLarge }}</h2>
            </div>
            @if($description)
                <p class="text-body-lg text-blue-black text-center">{{ $description }}</p>
            @endif
        </div>
    @endif

    @if($mobileLayout === 'grid')
        {{-- Grid Layout for Mobile, Scroll for Desktop --}}
        {{-- Desktop: Horizontal Scroll --}}
        <div class="scroll-container pb-8 container-main max-lg:hidden">
            <div class="flex pl-10 min-w-max">
                {{-- Intro Card (Desktop only) --}}
                @if($showIntro)
                    <div class="flex items-center shrink-0">
                        <div class="flex flex-col items-center justify-center gap-5 w-[424px] px-10">
                            @if($introImage)
                                <div class="w-full max-w-[320px] h-[429.5px]">
                                    <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-full object-contain" style="mix-blend-mode: darken;">
                                </div>
                            @endif
                            <div class="flex flex-col items-center text-center text-blue-black">
                                <span class="text-h3">{{ $titleSmall }}</span>
                                <h2 class="text-h2 uppercase">{{ $titleLarge }}</h2>
                            </div>
                            @if($description)
                                <p class="text-body-md text-blue-black text-center max-w-[300px]">{{ $description }}</p>
                            @endif
                        </div>
                        @if($showDividers)
                            <div class="w-px self-stretch {{ $dividerColor }} shrink-0"></div>
                        @endif
                    </div>
                @endif

                {{-- Cards with dividers --}}
                @foreach($posts as $index => $post)
                    <div class="flex items-center shrink-0 {{ $loop->last ? 'pr-10' : '' }}">
                        <x-cards.spread
                            :post="$post"
                            :reversed="$loop->even"
                        />
                        @if(!$loop->last && $showDividers)
                            <div class="w-px self-stretch {{ $dividerColor }} shrink-0"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Mobile: Grid Layout --}}
        <div class="hidden max-lg:block px-5 pb-6">
            <div class="grid grid-cols-2 gap-5">
                @foreach($posts as $index => $post)
                    <x-cards.spread
                        :post="$post"
                        :reversed="false"
                        mobile
                    />
                @endforeach
            </div>
        </div>
    @else
        {{-- Scroll Layout (Default) --}}
        <div class="scroll-container pb-8 max-lg:pb-6 container-main">
            <div class="flex pl-10 min-w-max max-lg:pl-5 max-lg:gap-8">
                {{-- Intro Card (Desktop only) --}}
                @if($showIntro)
                    <div class="flex items-center shrink-0 max-lg:hidden">
                        <div class="flex flex-col items-center justify-center gap-5 w-[424px] px-10">
                            @if($introImage)
                                <div class="w-full max-w-[320px] h-[429.5px]">
                                    <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-full object-contain" style="mix-blend-mode: darken;">
                                </div>
                            @endif
                            <div class="flex flex-col items-center text-center text-blue-black">
                                <span class="text-h3">{{ $titleSmall }}</span>
                                <h2 class="text-h2 uppercase">{{ $titleLarge }}</h2>
                            </div>
                            @if($description)
                                <p class="text-body-md text-blue-black text-center max-w-[300px]">{{ $description }}</p>
                            @endif
                        </div>
                        @if($showDividers)
                            <div class="w-px self-stretch {{ $dividerColor }} shrink-0"></div>
                        @endif
                    </div>
                @endif

                {{-- Cards with dividers --}}
                @foreach($posts as $index => $post)
                    <div class="flex items-center shrink-0 {{ $loop->last ? 'pr-10 max-lg:pr-5' : '' }}">
                        <x-cards.spread
                            :post="$post"
                            :reversed="$loop->even"
                        />
                        @if(!$loop->last && $showDividers)
                            <div class="w-px self-stretch {{ $dividerColor }} shrink-0 max-lg:hidden"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
