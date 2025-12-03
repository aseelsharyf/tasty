{{-- resources/views/components/post/card-news.blade.php --}}

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
    <div class="flex flex-col p-10 space-y-8">

        {{-- Image Section --}}
        <div class="w-full h-[350px] lg:h-[700px] relative rounded overflow-hidden bg-[#333]">
            <a href="{{ $articleUrl }}" class="block absolute inset-0">
                <img src="{{ $image }}"
                     alt="{{ $imageAlt }}"
                     class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 hover:scale-105">
            </a>

            {{-- Metadata Tags --}}
            <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 z-10">
                <x-post.metadata-badge
                    :category="$category"
                    :categoryUrl="$categoryUrl"
                    :tag="$tag"
                    :tagUrl="$tagUrl"
                    bgColor="bg-white"
                    textSize="text-sm"
                    padding="px-5 md:px-8 py-2 md:py-3"
                    gap="gap-2.5 md:gap-5"
                    shadow="shadow-sm hover:shadow-md"
                    hoverBg="hover:bg-stone-50"
                    :hideTagOnMobile="true"
                    :uppercase="false"
                />
            </div>
        </div>

        {{-- Content Section --}}
        <div class="w-full flex flex-col items-start md:items-center px-0 md:px-10 space-y-6">

            {{-- Title --}}
            <a href="{{ $articleUrl }}" class="w-full">
                <h1 class="w-full font-serif text-4xl md:text-5xl color-tasty-blue-black text-left md:text-center font-normal m-0 hover:opacity-70 transition-opacity duration-200">
                    {{ $title }}
                </h1>
            </a>

            {{-- Description --}}
            @if($description)
                <p class="hidden md:block text-lg md:text-xl text-[#202020] text-center mt-5">
                    {{ $description }}
                </p>
            @endif

            {{-- Author and Date Metadata --}}
            <div class="w-full flex flex-col md:flex-row items-start md:items-center justify-start md:justify-center md:space-x-5 text-sm text-stone-800">

                <a href="{{ $authorUrl }}" class="hover:text-stone-600 transition-colors duration-200 uppercase underline mb-2 md:mb-0">
                    BY {{ $author }}
                </a>

                {{-- Dot separator (hidden on mobile) --}}
                <span class="color-tasty-blue-black hidden md:block">•</span>

                {{-- Date --}}
                <span class="color-tasty-blue-black">
                    {{ strtoupper($date) }}
                </span>
            </div>
        </div>

    </div>
</div>
