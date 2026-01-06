<article {{ $attributes->merge(['class' => 'bg-white rounded-xl overflow-hidden p-10 flex gap-10 items-center w-full max-xl:flex-col max-xl:p-0 max-xl:pt-4 max-xl:px-4 max-xl:pb-8 max-xl:gap-4 max-xl:items-start']) }}>
    <div class="w-[200px] h-[200px] flex-shrink-0 relative
        max-xl:w-full max-xl:h-auto max-xl:aspect-[4/5] max-xl:p-4">
        <a href="{{ $url }}" class="block w-full h-full max-xl:absolute max-xl:inset-0">
            <x-ui.image
                :src="$image"
                :alt="$imageAlt"
                :blurhash="$blurhash"
                class="w-full h-full rounded"
                img-class="object-cover object-center"
            />
        </a>
        @if($category || $tag)
            <div class="hidden max-xl:flex max-xl:justify-center relative z-10">
                <x-post.meta-tags
                    :category="$category"
                    :category-url="$categoryUrl"
                    :tag="$tag"
                    :tag-url="$tagUrl"
                />
            </div>
        @endif
    </div>

    <div class="flex flex-col flex-1 gap-6 justify-center min-w-0
        max-xl:gap-4 max-xl:w-full">
        @if($category || $tag)
            <div class="max-xl:hidden">
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
                max-xl:text-[24px]">{{ $title }}</h3>
        </a>

        <div class="flex flex-wrap items-center gap-5 text-[14px] leading-[12px] uppercase text-blue-black
            max-xl:flex-col max-xl:items-start max-xl:gap-4 max-xl:text-[12px]">
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
