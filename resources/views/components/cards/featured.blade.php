<article class="bg-white rounded-[12px] overflow-hidden flex flex-col
    p-10 gap-8 w-[660px] flex-shrink-0
    max-xl:w-full max-xl:max-w-[660px] max-xl:pt-4 max-xl:px-4 max-xl:pb-8 max-xl:gap-4">

    {{-- Image - on mobile: fixed height, no overlay --}}
    <div class="relative rounded overflow-hidden h-[580px] max-xl:h-[206px] max-xl:shrink-0">
        <a href="{{ $url }}" class="block w-full h-full">
            <x-ui.image
                :src="$image"
                :alt="$imageAlt"
                :blurhash="$blurhash"
                class="w-full h-full rounded max-xl:rounded-[4px]"
                img-class="object-cover"
            />
        </a>
        {{-- Tag overlay - only on desktop --}}
        @if($category || $tag)
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10 max-xl:hidden">
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
    <div class="flex flex-col gap-5 text-center px-10
        max-xl:px-0 max-xl:text-left max-xl:gap-5">

        {{-- Tag - only on mobile, below image --}}
        @if($category || $tag)
            <div class="hidden max-xl:block">
                <x-post.meta-tags
                    :category="$category"
                    :category-url="$categoryUrl"
                    :tag="$tag"
                    :tag-url="$tagUrl"
                    class="self-start"
                />
            </div>
        @endif

        {{-- Title --}}
        <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
            <h3 class="font-display text-[36px] leading-[1] tracking-[-0.04em] text-blue-black
                max-xl:text-[24px] max-xl:leading-[24px] max-xl:tracking-[-0.96px]">{{ $title }}</h3>
        </a>

        @if($description)
            <p class="text-body-md text-blue-black max-xl:hidden">{{ $description }}</p>
        @endif

        {{-- Meta: Author & Date --}}
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
