<article class="flex flex-col gap-5 bg-white rounded-xl overflow-hidden p-5">
    {{-- Image with overlay tag --}}
    <div class="relative aspect-[4/3] rounded-lg overflow-hidden">
        <a href="{{ $url }}" class="absolute inset-0 z-0 group">
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt }}"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
            >
        </a>
        @if($category || $tag)
            <x-post.meta-tags
                :category="$category"
                :category-url="$categoryUrl"
                :tag="$tag"
                :tag-url="$tagUrl"
                class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10"
            />
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col gap-4 text-center">
        <a href="{{ $url }}" class="hover:text-tasty-yellow transition-colors">
            <h3 class="text-h4 text-blue-black">{{ $title }}</h3>
        </a>
        @if($description)
            <p class="text-body-md text-blue-black line-clamp-3">{{ $description }}</p>
        @endif

        {{-- Meta row --}}
        <x-post.author-date
            :author="$author"
            :author-url="$authorUrl"
            :date="$date"
            :centered="true"
        />
    </div>
</article>
