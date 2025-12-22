{{-- Featured Card - Large vertical card --}}
{{-- Desktop: Fixed width 660px, total height ~895px (580px image + content) --}}
{{-- Tablet/Mobile: card layout with p-4, gap-4, image h-[206px] --}}
<article class="bg-white rounded-xl overflow-hidden flex flex-col
    p-10 gap-8 w-[660px] flex-shrink-0
    max-lg:w-full max-lg:p-0 max-lg:pt-4 max-lg:px-4 max-lg:pb-8 max-lg:gap-4">

    {{-- Image with overlay tag at bottom center --}}
    {{-- Desktop: Fixed height to ensure consistent layout --}}
    <div class="relative rounded overflow-hidden flex items-end justify-center p-6
        h-[580px]
        max-lg:h-[206px] max-lg:p-4">
        <a href="{{ $url }}" class="absolute inset-0 z-0">
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                class="w-full h-full object-cover rounded"
            >
        </a>
        @if($category || $tag)
            <x-post.meta-tags
                :category="$category"
                :category-url="$categoryUrl"
                :tag="$tag"
                :tag-url="$tagUrl"
                class="relative z-10"
            />
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col gap-6 text-center px-10
        max-lg:px-0 max-lg:text-left max-lg:gap-5">
        <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
            <h3 class="font-display text-[48px] leading-[48px] tracking-[-1.92px] text-blue-black
                max-lg:text-[24px] max-lg:leading-[24px] max-lg:tracking-[-0.96px]">{{ $title }}</h3>
        </a>
        @if($description)
            <p class="font-sans text-[20px] leading-[28px] text-blue-black max-lg:hidden">{{ $description }}</p>
        @endif

        {{-- Meta --}}
        <div class="flex flex-wrap items-center justify-center gap-5 text-[14px] leading-[12px] uppercase text-blue-black
            max-lg:flex-col max-lg:items-start max-lg:justify-start max-lg:gap-4 max-lg:text-[12px]">
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
