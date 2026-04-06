<article {{ $attributes->merge(['class' => 'bg-white rounded-[12px] overflow-hidden p-10 flex gap-10 items-center w-full max-xl:flex-col max-xl:pt-4 max-xl:px-4 max-xl:pb-8 max-xl:gap-4 max-xl:items-start']) }}>
    {{-- Image --}}
    <div class="relative w-[200px] h-[200px] shrink-0 max-xl:w-full max-xl:!h-[206px] max-xl:shrink-0">
        <a href="{{ $url }}" class="block w-full h-full">
            <x-ui.image
                :src="$image"
                :alt="$imageAlt"
                :blurhash="$blurhash"
                :object-position="$imagePosition"
                class="w-full h-full rounded max-xl:rounded-[4px]"
                img-class="object-cover"
            />
        </a>
        @if($hasVideo)
            <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                <div class="w-10 h-10 border-2 border-white rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                </div>
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col flex-1 gap-6 justify-center min-w-0 max-xl:gap-5 max-xl:w-full max-xl:flex-none">
        {{-- Tag - visible on all screen sizes, below image on mobile --}}
        @if($category || $tag)
            <x-post.meta-tags
                :category="$category"
                :category-url="$categoryUrl"
                :tag="$tag"
                :tag-url="$tagUrl"
                class="self-start"
            />
        @endif

        {{-- Title --}}
        <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
            <h3 class="font-display text-[30px] leading-[1.1] tracking-[-0.04em] text-blue-black line-clamp-3
                max-xl:text-[24px] max-xl:leading-[24px] max-xl:tracking-[-0.96px]">{{ $title }}</h3>
        </a>

        {{-- Meta: Author & Date --}}
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
