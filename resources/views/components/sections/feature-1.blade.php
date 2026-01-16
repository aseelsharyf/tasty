{{-- Feature 1 Section --}}
{{-- Full-width feature section with header image and content overlay --}}
@if($post || ($kicker || $title))
@php
    // Handle both Post model and static array
    $isStatic = is_array($post);
@endphp

{{-- Curved Hero Image Section --}}
@if($image)
<section class="feature-1-image-wrapper">
    <a href="{{ $buttonUrl }}" class="feature-1-image-container" style="--feature-1-image: url('{{ $image }}');"></a>
</section>
@endif

{{-- Content Section --}}
<section class="w-full max-w-[1880px] mx-auto">
    <div class="{{ $bgColorClass }} pb-24 max-lg:pb-16" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
        <div class="container-main flex flex-col items-center gap-10 text-center w-full px-10 max-lg:px-5 max-lg:gap-6">
            {{-- Kicker & Title --}}
            <div class="flex flex-col gap-3 max-lg:gap-2">
                @if($kicker)
                    <a href="{{ $buttonUrl }}" class="hover:opacity-80 transition-opacity">
                        <p class="font-display text-[80px] leading-[1] tracking-[-0.04em] text-{{ $textColor }} uppercase max-lg:text-[48px]">{{ $kicker }}</p>
                    </a>
                @endif
                @if($title)
                    <h2 class="font-display text-[36px] leading-[1.1] tracking-[-0.04em] text-{{ $textColor }} max-lg:text-[24px]">{{ $title }}</h2>
                @endif
            </div>

            {{-- Tags --}}
            @if($tag1 || $tag2)
            <div class="flex items-center gap-5 text-body-sm uppercase text-{{ $textColor }}">
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
                <p class="text-body-md text-{{ $textColor }} max-w-[650px]">{{ $description }}</p>
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
@endif
