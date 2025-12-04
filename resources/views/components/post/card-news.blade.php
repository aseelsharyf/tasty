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

<div class="w-full md:max-w-none bg-white rounded-xl mx-auto md:mx-0">
    <div class="flex flex-col p-4 md:p-10 gap-4 md:gap-8">

        {{-- Image Section --}}
        <div class="w-full h-[206px] md:h-[607px] rounded overflow-hidden relative">
            <a href="{{ $articleUrl }}" class="block absolute inset-0 hover:opacity-80 transition-opacity duration-200">
                <img src="{{ $image }}"
                     alt="{{ $imageAlt }}"
                     class="absolute inset-0 w-full h-full object-cover object-center">
            </a>

            {{-- Metadata Tags (Desktop Only) --}}
            <div class="hidden md:block absolute bottom-6 left-1/2 transform -translate-x-1/2 z-10">
                <x-post.metadata-badge
                    :category="$category"
                    :categoryUrl="$categoryUrl"
                    :tag="$tag"
                    :tagUrl="$tagUrl"
                    bgColor="bg-white"
                    textSize="text-sm"
                    padding="px-8 py-3"
                    gap="gap-5"
                    shadow="shadow-sm hover:shadow-md"
                    hoverBg="hover:bg-stone-50"
                    :hideTagOnMobile="false"
                    :uppercase="false"
                />
            </div>
        </div>

        {{-- Content Section --}}
        <div class="w-full flex flex-col items-start md:items-center gap-4 md:gap-6">
            {{-- Metadata Tags (Mobile Only) --}}
            <div class="md:hidden flex justify-start">
                <x-post.metadata-badge
                    :category="$category"
                    :categoryUrl="$categoryUrl"
                    :tag="$tag"
                    :tagUrl="$tagUrl"
                />
            </div>

            {{-- Title --}}
            <x-post.title
                :title="$title"
                :url="$articleUrl"
                tag="h4"
                align="left md:center"
                lineClamp="line-clamp-3"
            />

            {{-- Description (Desktop Only) --}}
            @if($description)
                <div class="hidden md:block w-full">
                    <x-post.description
                        :description="$description"
                        align="center"
                        :hideOnMobile="false"
                    />
                </div>
            @endif

            {{-- Author and Date Metadata --}}
            <x-post.author-date
                :author="$author"
                :authorUrl="$authorUrl"
                :date="$date"
                size="xs"
                layout="horizontal md:horizontal"
                align="left md:center"
                :hideSeparatorOnMobile="false"
            />
        </div>

    </div>
</div>
