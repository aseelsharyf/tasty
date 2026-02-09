{{-- Featured Location Section --}}
{{-- Full-bleed background image with curved yellow content overlay --}}
@if($image && ($kicker || $title))
<section class="featured-location-wrapper">
    <div
        class="featured-location-container"
        style="--featured-location-image: url('{{ $image }}'); --featured-location-position: {{ $imagePosition }};"
    >
        {{-- Yellow curved content area --}}
        <div class="featured-location-content pt-[100px] lg:pt-0 {{ $bgColorClass }}" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="flex flex-col items-center justify-start lg:justify-center text-center gap-10 max-lg:gap-6 w-full h-full max-w-[100vw] px-10 max-lg:px-5 lg:-mt-[100px]">
                {{-- Kicker (large) & Title (below) --}}
                <div class="flex flex-col gap-3 items-center w-full max-w-4xl 2xl:gap-5">
                    @if($kicker)
                        <p class="font-display text-[145px] leading-[1] tracking-[-0.04em] uppercase text-{{ $textColor }} max-lg:text-[56px]">
                            {{ $kicker }}
                        </p>
                    @endif
                    @if($title)
                        <h2 class="font-display text-[56px] leading-[1.1] tracking-[-0.04em] text-{{ $textColor }} max-lg:text-[24px]">
                            {{ $title }}
                        </h2>
                    @endif
                </div>

                {{-- Tags --}}
                <div class="flex items-center gap-5 text-body-sm uppercase text-{{ $textColor }} max-lg:text-[12px] max-lg:leading-[12px]">
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

                {{-- Button (optional) --}}
                @if($buttonText && $buttonUrl && $buttonUrl !== '#')
                    <a href="{{ $buttonUrl }}" class="btn btn-{{ $buttonVariant }}">
                        <x-ui.icons.arrow-right />
                        <span>{{ $buttonText }}</span>
                    </a>
                @endif

            </div>
        </div>
    </div>

    {{-- Carousel of additional posts - separate section below the curve --}}
    @if($carouselPosts->isNotEmpty())
        <div class="w-full {{ $bgColorClass }} pb-16 max-lg:pb-[50px] pt-16 max-lg:pt-20 -mt-[150px] lg:-mt-[450px] relative z-10" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="scroll-container pb-8 max-lg:pb-6">
                <div class="flex items-start justify-center min-w-max px-10 max-lg:px-5 max-lg:gap-8">
                    @foreach($carouselPosts as $carouselPost)
                        <div class="flex items-start shrink-0 {{ $loop->last ? 'pr-10 max-lg:pr-5' : '' }}">
                            <x-cards.spread
                                :post="$carouselPost"
                                :reversed="$loop->even"
                            />
                            @if(!$loop->last)
                                <div class="w-px h-[600px] bg-white shrink-0 max-lg:hidden"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</section>
@endif
