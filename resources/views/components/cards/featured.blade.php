<article class="bg-white rounded-xl overflow-hidden flex flex-col
    p-10 gap-8 w-[660px] flex-shrink-0
    max-xl:w-full max-xl:max-w-[660px] max-xl:p-0 max-xl:pt-4 max-xl:px-4 max-xl:pb-8 max-xl:gap-4">

    <div class="relative rounded overflow-hidden flex items-end justify-center p-6
        h-[580px]
        max-xl:aspect-[3/4] max-xl:h-auto max-xl:p-4 max-xl:items-start max-xl:justify-center">
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

    <div class="flex flex-col gap-5 text-center px-10
        max-xl:px-0 max-xl:text-left max-xl:gap-4">
        <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
            <h3 class="font-display text-[36px] leading-[1] tracking-[-0.04em] text-blue-black
                max-xl:text-[24px]">{{ $title }}</h3>
        </a>
        @if($description)
            <p class="text-body-md text-blue-black max-xl:hidden">{{ $description }}</p>
        @endif

        <div class="flex flex-wrap items-center justify-center gap-5 text-[14px] leading-[12px] uppercase text-blue-black
            max-xl:flex-col max-xl:items-start max-xl:justify-start max-xl:gap-4 max-xl:text-[12px]">
            @if($author)
                <a href="{{ $authorUrl }}" class="underline underline-offset-4 hover:opacity-80 transition-opacity">BY {{ strtoupper($author) }}</a>
                <span class="max-xl:hidden">•</span>
            @endif
            @if($date)
                <span>{{ strtoupper($date) }}</span>
            @endif
        </div>
    </div>
</article>
