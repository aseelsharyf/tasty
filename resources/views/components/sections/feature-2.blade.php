{{-- Feature 2 Section --}}
{{-- Full-bleed background image with curved content overlay (like featured-location) --}}
@if($image && ($kicker || $title))
<section class="feature-2-wrapper">
    <div
        class="feature-2-container"
        style="--feature-2-image: url('{{ $image }}'); --feature-2-position: {{ $imagePosition }};"
    >
        {{-- Curved content area --}}
        <div class="feature-2-content {{ $bgColorClass }}" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
            <div class="flex flex-col items-center justify-center text-center gap-10 max-lg:gap-6 w-full h-full max-w-[100vw] px-10 max-lg:px-5 lg:-mt-[100px]">
                {{-- Kicker (large) & Title (below) --}}
                <div class="flex flex-col gap-3 items-center w-full max-w-4xl 2xl:gap-5">
                    @if($kicker)
                        <a href="{{ $buttonUrl }}" class="hover:opacity-80 transition-opacity">
                            <p class="font-display text-[145px] leading-[1] tracking-[-0.04em] uppercase text-{{ $textColor }} max-lg:text-[56px]">
                                {{ $kicker }}
                            </p>
                        </a>
                    @endif
                    @if($title)
                        <h2 class="font-display text-[56px] leading-[1.1] tracking-[-0.04em] text-{{ $textColor }} max-lg:text-[24px]">
                            {{ $title }}
                        </h2>
                    @endif
                </div>

                {{-- Tags --}}
                @if($tag1 || $tag2)
                <div class="flex items-center gap-5 text-body-sm uppercase text-{{ $textColor }} max-lg:text-[12px] max-lg:leading-[12px]">
                    @if($tag1)
                        @if($tag1Slug)
                            <a href="{{ route('tag.show', $tag1Slug) }}" class="hover:underline">{{ $tag1 }}</a>
                        @else
                            <span>{{ $tag1 }}</span>
                        @endif
                        @if($tag2)
                            <span>•</span>
                        @endif
                    @endif
                    @if($tag2)
                        @if($tag2Slug)
                            <a href="{{ route('category.show', $tag2Slug) }}" class="hover:underline">{{ strtoupper($tag2) }}</a>
                        @else
                            <span>{{ strtoupper($tag2) }}</span>
                        @endif
                    @endif
                </div>
                @endif

                {{-- Description --}}
                @if($description)
                    <p class="text-[20px] leading-[1.3] text-{{ $textColor }} max-w-4xl text-center">
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
</section>
@endif
