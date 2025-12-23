{{-- Featured Location Section --}}
{{-- Full-bleed background image with curved yellow content overlay --}}
@if($image && ($kicker || $title))
<section class="featured-location-wrapper">
    <div
        class="featured-location-container"
        style="--featured-location-image: url('{{ $image }}');"
    >
        {{-- Yellow curved content area --}}
        <div class="featured-location-content {{ $bgColorClass }}" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="flex flex-col items-center text-center gap-10 max-lg:gap-6 w-full">
                {{-- Kicker (large) & Title (below) --}}
                <div class="flex flex-col gap-4 items-center w-full">
                    @if($kicker)
                        <p class="text-[200px] leading-[160px] tracking-[-8px] uppercase font-display text-{{ $textColor }} max-lg:text-[60px] max-lg:leading-[50px] max-lg:tracking-[-2.4px]">
                            {{ $kicker }}
                        </p>
                    @endif
                    @if($title)
                        <h2 class="text-h2 text-{{ $textColor }} max-w-[800px] max-lg:text-[32px] max-lg:leading-[32px] max-lg:tracking-[-1.28px]">
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
                    <p class="text-body-lg text-{{ $textColor }} max-w-[600px] text-center max-lg:text-[20px] max-lg:leading-[26px]">
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
</section>
@endif
