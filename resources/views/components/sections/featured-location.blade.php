{{-- Featured Location Section --}}
{{-- Full-bleed background image with curved yellow content overlay --}}
@if($image && ($kicker || $title))

{{-- Mobile: Full-bleed image with curved content overlay --}}
<div class="lg:hidden">
    {{-- Background image with curved yellow content on top --}}
    <section class="featured-location-mobile" style="--featured-location-image: url('{{ $image }}'); --featured-location-position: {{ $imagePosition }};">
        <div class="featured-location-mobile-content {{ $bgColorClass }}" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="flex flex-col items-center text-center gap-6 w-full max-w-[100vw] px-5 pt-[100px] pb-20">
                {{-- Kicker & Title --}}
                <div class="flex flex-col gap-3 items-center w-full max-w-4xl">
                    @if($kicker)
                        <p class="font-display text-[56px] leading-[1] tracking-[-0.04em] uppercase text-{{ $textColor }}">
                            {{ $kicker }}
                        </p>
                    @endif
                    @if($title)
                        <h2 class="font-display text-[24px] leading-[1.1] tracking-[-0.04em] text-{{ $textColor }}">
                            {{ $title }}
                        </h2>
                    @endif
                </div>

                {{-- Tags --}}
                <div class="flex items-center gap-5 text-[12px] leading-[12px] uppercase text-{{ $textColor }}">
                    @if($tag1Slug)
                        <a href="{{ route('tag.show', $tag1Slug) }}" class="hover:underline">{{ $tag1 }}</a>
                    @else
                        <span>{{ $tag1 }}</span>
                    @endif
                    <span>•</span>
                    @if($tag2Slug)
                        <a href="{{ route('category.show', $tag2Slug) }}" class="hover:underline">{{ $tag2 }}</a>
                    @else
                        <span>{{ $tag2 }}</span>
                    @endif
                </div>

                {{-- Description --}}
                @if($description)
                    <p class="text-body-md text-{{ $textColor }} max-w-4xl text-center">
                        {{ $description }}
                    </p>
                @endif

                {{-- Button --}}
                @if($buttonText && $buttonUrl && $buttonUrl !== '#')
                    <a href="{{ $buttonUrl }}" class="btn btn-{{ $buttonVariant }}">
                        <x-ui.icons.arrow-right />
                        <span>{{ $buttonText }}</span>
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- Carousel of additional posts --}}
    @if($carouselPosts->isNotEmpty())
        <section class="w-full {{ $bgColorClass }} pb-[50px]" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="scroll-container pb-6">
                <div class="flex items-start justify-center min-w-max px-5 gap-8">
                    @foreach($carouselPosts as $carouselPost)
                        <div class="flex items-start shrink-0 {{ $loop->last ? 'pr-5' : '' }}">
                            <x-cards.spread
                                :post="$carouselPost"
                                :reversed="$loop->even"
                            />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>

{{-- Desktop: Original layout (image behind, curved content overlay) --}}
<section class="featured-location-wrapper hidden lg:flex">
    <div
        class="featured-location-container"
        style="--featured-location-image: url('{{ $image }}'); --featured-location-position: {{ $imagePosition }};"
    >
        {{-- Yellow curved content area --}}
        <div class="featured-location-content {{ $bgColorClass }}" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="flex flex-col items-center justify-center text-center gap-10 w-full h-full max-w-[100vw] px-10 -mt-[100px]">
                {{-- Kicker & Title --}}
                <div class="flex flex-col gap-3 items-center w-full max-w-4xl 2xl:gap-5">
                    @if($kicker)
                        <p class="font-display text-[145px] leading-[1] tracking-[-0.04em] uppercase text-{{ $textColor }}">
                            {{ $kicker }}
                        </p>
                    @endif
                    @if($title)
                        <h2 class="font-display text-[56px] leading-[1.1] tracking-[-0.04em] text-{{ $textColor }}">
                            {{ $title }}
                        </h2>
                    @endif
                </div>

                {{-- Tags --}}
                <div class="flex items-center gap-5 text-body-sm uppercase text-{{ $textColor }}">
                    @if($tag1Slug)
                        <a href="{{ route('tag.show', $tag1Slug) }}" class="hover:underline">{{ $tag1 }}</a>
                    @else
                        <span>{{ $tag1 }}</span>
                    @endif
                    <span>•</span>
                    @if($tag2Slug)
                        <a href="{{ route('category.show', $tag2Slug) }}" class="hover:underline">{{ $tag2 }}</a>
                    @else
                        <span>{{ $tag2 }}</span>
                    @endif
                </div>

                {{-- Description --}}
                @if($description)
                    <p class="text-body-md text-{{ $textColor }} max-w-4xl text-center">
                        {{ $description }}
                    </p>
                @endif

                {{-- Button --}}
                @if($buttonText && $buttonUrl && $buttonUrl !== '#')
                    <a href="{{ $buttonUrl }}" class="btn btn-{{ $buttonVariant }}">
                        <x-ui.icons.arrow-right />
                        <span>{{ $buttonText }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Carousel of additional posts --}}
    @if($carouselPosts->isNotEmpty())
        <div class="w-full {{ $bgColorClass }} pb-16 pt-16 -mt-[450px] relative z-10" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="scroll-container pb-8">
                <div class="flex items-start justify-center min-w-max px-10">
                    @foreach($carouselPosts as $carouselPost)
                        <div class="flex items-start shrink-0 {{ $loop->last ? 'pr-10' : '' }}">
                            <x-cards.spread
                                :post="$carouselPost"
                                :reversed="$loop->even"
                            />
                            @if(!$loop->last)
                                <div class="w-px h-[600px] bg-white shrink-0"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</section>

@endif
