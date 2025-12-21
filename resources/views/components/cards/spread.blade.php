<article class="flex {{ $reversed ? 'flex-col-reverse' : 'flex-col' }} gap-8 w-[480px] max-md:w-[310px] shrink-0">
    {{-- Image --}}
    <div class="relative h-[580px] max-md:h-[362px] rounded-xl overflow-hidden">
        <a href="{{ $url }}" class="absolute inset-0 z-0 group">
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
            >
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
    <div class="flex flex-col gap-6">
        <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
            <h3 class="text-h3 text-blue-black line-clamp-3">{{ $title }}</h3>
        </a>
        @if($description)
            <p class="text-body-md text-blue-black line-clamp-3">{{ $description }}</p>
        @endif

        {{-- Author/date --}}
        <x-post.author-date
            :author="$author"
            :author-url="$authorUrl"
            :date="$date"
        />
    </div>
</article>
