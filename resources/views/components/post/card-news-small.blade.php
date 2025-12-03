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
        <div class="w-full md:w-[250px] h-[250px] relative rounded overflow-hidden bg-[#333] flex-shrink-0">
            <a href="{{ $articleUrl }}" class="block absolute inset-0">
                <img src="{{ $image }}"
                     alt="{{ $imageAlt }}"
                     class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 hover:scale-105">
            </a>
        </div>

        {{-- Content Section (Right) --}}
        <div class="w-full md:flex-1 md:h-[250px] flex flex-col justify-between py-2">

            {{-- Metadata Tags (Top) --}}
            <div class="flex justify-start">
                <div class="bg-tasty-off-white px-4 py-2 rounded-full flex items-center justify-center gap-2.5 transition-all duration-300 whitespace-nowrap">
                    <a href="{{ $categoryUrl }}" class="text-xs text-black uppercase hover:opacity-70 transition-opacity duration-200">
                        {{ $category }}
                    </a>

                    @if($tag)
                        {{-- Dot separator --}}
                        <span class="text-xs text-black">•</span>

                        {{-- Tag --}}
                        <a href="{{ $tagUrl }}" class="text-xs text-black uppercase hover:opacity-70 transition-opacity duration-200">
                            {{ $tag }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Title (Middle) --}}
            <a href="{{ $articleUrl }}" class="w-full flex-1 py-5">
                <h2 class="w-full font-serif text-2xl md:text-3xl color-tasty-blue-black text-left font-normal m-0 hover:opacity-70 transition-opacity duration-200 line-clamp-3">
                    {{ $title }}
                </h2>
            </a>

            {{-- Author and Date Metadata (Bottom) --}}
            <div class="w-full flex flex-row items-center gap-2 text-xs text-stone-800">

                <span class="truncate">
                    <a href="{{ $authorUrl }}" class="hover:text-stone-600 transition-colors duration-200 uppercase underline whitespace-nowrap">
                        BY {{ $author }}
                    </a>
                </span>

                {{-- Dot separator --}}
                <span class="color-tasty-blue-black flex-shrink-0">•</span>

                {{-- Date --}}
                <span class="color-tasty-blue-black truncate whitespace-nowrap">
                    {{ strtoupper($date) }}
                </span>
            </div>
        </div>

    </div>
</div>
