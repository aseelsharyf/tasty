{{-- Spread Card --}}
<article class="flex {{ $reversed ? 'flex-col-reverse' : 'flex-col' }} gap-6 {{ $mobile ? 'w-full' : 'w-[480px] px-10 max-lg:w-[300px] max-lg:px-0' }} shrink-0">
    {{-- Image --}}
    <div class="relative aspect-[3/4] rounded-xl overflow-hidden">
        <a href="{{ $url }}" class="absolute inset-0 z-0 group">
            <x-ui.image
                :src="$image"
                :alt="$imageAlt"
                :blurhash="$blurhash"
                class="w-full h-full"
                img-class="object-cover"
            />
        </a>
        @if($category || $tag)
            <div class="absolute inset-0 flex items-end justify-center p-6 z-10 pointer-events-none">
                <x-post.meta-tags
                    :category="$category"
                    :category-url="$categoryUrl"
                    :tag="$tag"
                    :tag-url="$tagUrl"
                    class="pointer-events-auto"
                />
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col gap-4">
        <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
            <h3 class="text-h4 text-blue-black line-clamp-3">{{ $title }}</h3>
        </a>
        @if($description)
            <p class="text-body-md text-blue-black line-clamp-3">{{ $description }}</p>
        @endif

        {{-- Author/date - Same pattern as horizontal card --}}
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
