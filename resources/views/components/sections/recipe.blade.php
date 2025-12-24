{{-- Recipe Section --}}
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

    @if($mobileLayout === 'scroll')
        {{-- Scroll Layout - Horizontal scroll on both desktop and mobile --}}
        <div class="scroll-container pb-8 max-lg:pb-6 container-main">
            <div class="flex pl-10 min-w-max max-lg:pl-5 max-lg:gap-6">
                {{-- Intro Card (Desktop only) --}}
                @if($showIntro)
                    <div class="flex items-start shrink-0 max-lg:hidden">
                        <div class="flex flex-col items-center justify-center gap-5 w-[424px] px-10 pt-8">
                            @if($introImage)
                                <div class="w-full max-w-[320px] h-[476px]">
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

                {{-- Featured Recipe Card --}}
                @if($featuredPost)
                    <div class="flex items-start shrink-0 pt-8 max-lg:pt-0">
                        <div class="w-[480px] max-lg:w-[320px]">
                            <x-cards.recipe
                                :post="$featuredPost"
                                variant="featured"
                            />
                        </div>
                        @if($showDividers && $posts->isNotEmpty())
                            <div class="w-px self-stretch {{ $dividerColor }} shrink-0 max-lg:hidden"></div>
                        @endif
                    </div>
                @endif

                {{-- Recipe Cards --}}
                @foreach($posts as $index => $post)
                    <div class="flex items-start shrink-0 pt-8 max-lg:pt-0 {{ $loop->last ? 'pr-10 max-lg:pr-5' : '' }}">
                        <div class="w-[320px] max-lg:w-[280px]">
                            <x-cards.recipe
                                :post="$post"
                                variant="default"
                            />
                        </div>
                        @if(!$loop->last && $showDividers)
                            <div class="w-px self-stretch {{ $dividerColor }} shrink-0 max-lg:hidden"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @else
        {{-- Grid Layout (Default) --}}
        <div class="container-main px-10 max-lg:px-5">
            <div class="flex flex-col gap-10 max-lg:gap-6">
                {{-- Top Row: Intro + Featured - 2 equal columns --}}
                <div class="grid grid-cols-2 gap-10 max-lg:grid-cols-1 max-lg:gap-6">
                    {{-- Intro Card (Desktop only) --}}
                    @if($showIntro)
                        <div class="flex flex-col items-center justify-center gap-5 max-lg:hidden">
                            @if($introImage)
                                <div class="w-full max-w-[320px] h-[476px]">
                                    <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-full object-contain" style="mix-blend-mode: darken;">
                                </div>
                            @endif
                            <div class="flex flex-col items-center text-center text-blue-black">
                                <span class="text-h3">{{ $titleSmall }}</span>
                                <h2 class="text-h2 uppercase">{{ $titleLarge }}</h2>
                            </div>
                            @if($description)
                                <p class="text-body-md text-blue-black text-center max-w-[320px]">{{ $description }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- Featured Recipe Card --}}
                    @if($featuredPost)
                        <div class="max-lg:w-full">
                            <x-cards.recipe
                                :post="$featuredPost"
                                variant="featured"
                            />
                        </div>
                    @endif
                </div>

                {{-- Bottom Row: Recipe Cards Grid (3 items) --}}
                @if($posts->isNotEmpty())
                    <div class="grid grid-cols-3 gap-6 max-lg:grid-cols-2 max-sm:grid-cols-1">
                        @foreach($posts->take(3) as $post)
                            <x-cards.recipe
                                :post="$post"
                                variant="grid"
                            />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</section>
