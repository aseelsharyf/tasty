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
        <div class="w-full h-[350px] lg:h-[700px] rounded overflow-hidden relative">
            <a href="{{ $articleUrl }}" class="block absolute inset-0 hover:opacity-80 transition-opacity duration-200">
                <img src="{{ $image }}"
                     alt="{{ $imageAlt }}"
                     class="absolute inset-0 w-full h-full object-cover object-center">
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
            <x-post.title
                :title="$title"
                :url="$articleUrl"
                tag="h1"
                align="left md:center"
            />

            {{-- Description --}}
            @if($description)
                <x-post.description
                    :description="$description"
                    align="center"
                    :hideOnMobile="true"
                />
            @endif

            {{-- Author and Date Metadata --}}
            <x-post.author-date
                :author="$author"
                :authorUrl="$authorUrl"
                :date="$date"
                layout="vertical md:horizontal"
                align="center"
                :hideSeparatorOnMobile="true"
            />
        </div>

    </div>
</div>
