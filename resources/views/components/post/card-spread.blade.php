{{-- resources/views/components/post/card-spread.blade.php --}}

@props([
    'image',
    'imageAlt' => '',
    'category' => 'The Spread',
    'categoryUrl' => '#',
    'tag' => null,
    'tagUrl' => '#',
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'title' => '',
    'description' => '',
    'articleUrl' => '#',
    'imagePosition' => 'top', // Options: 'top', 'bottom'
])

@php
    // Normalize the imagePosition value
    $imagePosition = strtolower(trim($imagePosition ?? 'top'));

    // Determine badge background color based on image position
    $badgeBgColor = $imagePosition === 'top' ? 'bg-white' : 'bg-white';
@endphp

<div class="flex flex-col justify-start items-center gap-8">

    @if($imagePosition === 'top')
        {{-- Image Section at Top --}}
        <div class="w-full h-[358px] md:h-[681px] relative rounded-xl overflow-hidden bg-[#333] flex flex-col justify-end items-center p-6">
            <a href="{{ $articleUrl }}" class="block absolute inset-0">
                <img src="{{ $image }}"
                     alt="{{ $imageAlt }}"
                     class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 hover:scale-105">
            </a>

            {{-- Metadata Badge --}}
            <div class="relative z-10 inline-flex justify-start items-start gap-5">
                <x-post.metadata-badge
                    :category="$category"
                    :categoryUrl="$categoryUrl"
                    :tag="$tag"
                    :tagUrl="$tagUrl"
                    :bgColor="$badgeBgColor"
                />
            </div>
        </div>

        {{-- Content Section --}}
        <div class="w-full flex flex-col justify-start items-start gap-6">
            <x-post.title
                :title="$title"
                :url="$articleUrl"
            />

            <x-post.description
                :description="$description"
            />

            <x-post.author-date
                :author="$author"
                :authorUrl="$authorUrl"
                :date="$date"
            />
        </div>
    @else
        {{-- Content Section at Top --}}
        <div class="w-full flex flex-col justify-start items-start gap-6">
            <x-post.title
                :title="$title"
                :url="$articleUrl"
            />

            <x-post.description
                :description="$description"
            />

            <x-post.author-date
                :author="$author"
                :authorUrl="$authorUrl"
                :date="$date"
            />
        </div>

        {{-- Image Section at Bottom --}}
        <div class="w-full h-[358px] md:h-[681px] relative rounded-xl overflow-hidden bg-[#333] flex flex-col justify-end items-center p-6">
            <a href="{{ $articleUrl }}" class="block absolute inset-0">
                <img src="{{ $image }}"
                     alt="{{ $imageAlt }}"
                     class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 hover:scale-105">
            </a>

            {{-- Metadata Badge --}}
            <div class="relative z-10 inline-flex justify-center items-start gap-5">
                <x-post.metadata-badge
                    :category="$category"
                    :categoryUrl="$categoryUrl"
                    :tag="$tag"
                    :tagUrl="$tagUrl"
                    :bgColor="$badgeBgColor"
                />
            </div>
        </div>
    @endif

</div>
