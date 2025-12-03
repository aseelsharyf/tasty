{{-- resources/views/components/post/card-news-small.blade.php --}}

@props([
    'image',
    'imageAlt' => '',
    'category' => '',
    'categoryUrl' => '#',
    'tag' => null,
    'tagUrl' => '#',
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'title' => '',
    'description' => null,
    'articleUrl' => '#',
])

<div class="w-full bg-white rounded-xl mx-auto">
    <div class="flex flex-col md:flex-row p-10 gap-4 md:gap-6">

        {{-- Image Section (Left) --}}
        <div class="w-full md:w-[200px] h-[206px] relative rounded overflow-hidden bg-[#333] flex-shrink-0">
            <a href="{{ $articleUrl }}" class="block absolute inset-0">
                <img src="{{ $image }}"
                     alt="{{ $imageAlt }}"
                     class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 hover:scale-105">
            </a>
        </div>

        {{-- Content Section (Right) --}}
        <div class="w-full md:flex-1 md:h-[206px] flex flex-col justify-between py-2">

            {{-- Metadata Tags (Top) --}}
            <div class="flex justify-start">
                <x-post.metadata-badge
                    :category="$category"
                    :categoryUrl="$categoryUrl"
                    :tag="$tag"
                    :tagUrl="$tagUrl"
                />
            </div>

            {{-- Title (Middle) --}}
            <div class="w-full flex-1 py-5">
                <x-post.title
                    :title="$title"
                    :url="$articleUrl"
                    size="small"
                    lineClamp="line-clamp-3"
                />
            </div>

            {{-- Author and Date Metadata (Bottom) --}}
            <x-post.author-date
                :author="$author"
                :authorUrl="$authorUrl"
                :date="$date"
                size="xs"
            />
        </div>

    </div>
</div>
