{{-- Horizontal Card --}}
{{-- Desktop: flex-1 to share grid space --}}
{{-- Tablet/Mobile: vertical layout, p-4, gap-4, image h-[206px] with tag overlay --}}
<article class="bg-white rounded-xl overflow-hidden p-10 flex gap-10 items-center w-full
    max-lg:flex-col max-lg:px-4 max-lg:pt-4 max-lg:pb-8 max-lg:gap-4 max-lg:items-start">
    {{-- Image - Desktop: 200px wide with auto height, centered vertically --}}
    {{-- Tablet/Mobile: full width h-[206px] with tag overlay --}}
    <div class="w-[200px] flex-shrink-0 relative flex items-center justify-center
        max-lg:w-full max-lg:h-[206px] max-lg:p-4">
        <a href="{{ $url }}" class="block w-full max-lg:absolute max-lg:inset-0">
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                class="w-full h-auto object-cover rounded max-lg:h-full"
            >
        </a>
        {{-- Tag overlay - only visible on tablet/mobile --}}
        @if($category || $tag)
            <div class="hidden max-lg:block relative z-10">
                <x-post.meta-tags
                    :category="$category"
                    :category-url="$categoryUrl"
                    :tag="$tag"
                    :tag-url="$tagUrl"
                />
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col flex-1 gap-6 justify-center min-w-0
        max-lg:gap-5 max-lg:w-full">
        {{-- Tag - only visible on desktop --}}
        @if($category || $tag)
            <div class="max-lg:hidden">
                <x-post.meta-tags
                    :category="$category"
                    :category-url="$categoryUrl"
                    :tag="$tag"
                    :tag-url="$tagUrl"
                />
            </div>
        @endif

        <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
            <h3 class="font-display text-[28px] leading-[1.1] tracking-[-0.04em] text-blue-black line-clamp-3
                max-lg:text-[24px]">{{ $title }}</h3>
        </a>

        {{-- Meta - Desktop: inline row, Tablet/Mobile: stacked vertically --}}
        <div class="flex flex-wrap items-center gap-5 text-[14px] leading-[12px] uppercase text-blue-black
            max-lg:flex-col max-lg:items-start max-lg:gap-4 max-lg:text-[12px]">
            @if($author)
                <a href="{{ $authorUrl }}" class="underline underline-offset-4 hover:opacity-80 transition-opacity">BY {{ strtoupper($author) }}</a>
                <span class="max-lg:hidden">•</span>
            @endif
            @if($date)
                <span>{{ strtoupper($date) }}</span>
            @endif
        </div>
    </div>
</article>
