{{-- Featured Person Section --}}
@if($post)
@php
    // Handle both Post model and static array
    $isStatic = is_array($post);
    $kicker = $isStatic ? ($post['kicker'] ?? '') : $post->kicker;
    $title = $isStatic ? ($post['title'] ?? '') : $post->title;
    $excerpt = $isStatic ? ($post['excerpt'] ?? $post['description'] ?? '') : $post->excerpt;
    $url = $isStatic ? ($post['url'] ?? '#') : $post->url;
    $image = $isStatic ? ($post['image'] ?? '') : $post->featured_image_url;
    $category = $isStatic ? null : $post->categories->first();
@endphp

{{-- Curved Hero Image Section --}}
@if($image)
<section class="featured-person-image-wrapper">
    <a href="{{ $url }}" class="featured-person-image-container" style="--featured-person-image: url('{{ $image }}');"></a>
</section>
@endif

{{-- Content Section --}}
<section class="w-full max-w-[1880px] mx-auto">
    <div class="{{ $bgColorClass }} pb-24 max-lg:pb-16" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
        <div class="container-main flex flex-col items-center gap-10 text-center w-full px-10 max-lg:px-5 max-lg:gap-6">
            {{-- Kicker & Title --}}
            <div class="flex flex-col gap-3 max-lg:gap-2">
                @if($kicker)
                    <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
                        <p class="font-display text-[80px] leading-[1] tracking-[-0.04em] text-blue-black uppercase max-lg:text-[48px]">{{ $kicker }}</p>
                    </a>
                @endif
                @if($title)
                    <h2 class="font-display text-[36px] leading-[1.1] tracking-[-0.04em] text-blue-black max-lg:text-[24px]">{{ $title }}</h2>
                @endif
            </div>

            {{-- Tags --}}
            <div class="flex items-center gap-5 text-body-sm uppercase text-blue-black">
                @if($tag1)
                    @if($tag1Slug)
                        <a href="{{ route('tag.show', $tag1Slug) }}" class="hover:underline">{{ $tag1 }}</a>
                    @else
                        <span>{{ $tag1 }}</span>
                    @endif
                    <span>•</span>
                @endif
                @if($tag2)
                    @if($tag2Slug)
                        <a href="{{ route('category.show', $tag2Slug) }}" class="hover:underline">{{ strtoupper($tag2) }}</a>
                    @else
                        <span>{{ strtoupper($tag2) }}</span>
                    @endif
                @endif
            </div>

            {{-- Description --}}
            @if($excerpt)
                <p class="text-body-md text-blue-black max-w-[650px]">{{ $excerpt }}</p>
            @endif

            {{-- Button --}}
            <a href="{{ $url }}" class="btn btn-white">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ $buttonText }}</span>
            </a>
        </div>
    </div>
</section>
@endif
