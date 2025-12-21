<article class="card-horizontal bg-white rounded-xl overflow-hidden">
    {{-- Image --}}
    <a href="{{ $url }}" class="card-horizontal-image group">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        >
    </a>

    {{-- Content --}}
    <div class="flex flex-col gap-4 flex-1 min-w-0">
        @if($category || $tag)
            <x-post.meta-tags
                :category="$category"
                :category-url="$categoryUrl"
                :tag="$tag"
                :tag-url="$tagUrl"
            />
        @endif
        <a href="{{ $url }}" class="hover:text-tasty-yellow transition-colors">
            <h3 class="text-h4 text-blue-black line-clamp-2">{{ $title }}</h3>
        </a>

        {{-- Meta row --}}
        <x-post.author-date
            :author="$author"
            :author-url="$authorUrl"
            :date="$date"
        />
    </div>
</article>
