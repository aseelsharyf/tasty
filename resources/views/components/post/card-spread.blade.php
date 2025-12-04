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

<div class="w-full max-w-[310px] md:max-w-[480px] flex flex-col justify-start items-center gap-8">

    @if($imagePosition === 'top')
        {{-- Image Section at Top --}}
        <div class="w-full h-[393px] md:h-[620px] rounded-xl overflow-hidden relative">
            <a href="{{ $articleUrl }}" class="group absolute inset-0 block">
                <div class="absolute inset-0 group-hover:opacity-80 transition-opacity duration-200">
                    <img src="{{ $image }}"
                         alt="{{ $imageAlt }}"
                         class="absolute inset-0 w-full h-full object-cover object-center">
                </div>
            </a>

            {{-- Metadata Badge --}}
            <div class="relative z-10 w-full h-full p-6 flex flex-col justify-end items-center pointer-events-none">
                <div class="inline-flex justify-start items-start gap-5 pointer-events-auto">
                    <x-post.metadata-badge
                        :category="$category"
                        :categoryUrl="$categoryUrl"
                        :tag="$tag"
                        :tagUrl="$tagUrl"
                        :bgColor="$badgeBgColor"
                    />
                </div>
            </div>
        </div>

        {{-- Content Section --}}
        <div class="w-full flex flex-col justify-start items-start gap-6">
            <x-post.title
                :title="$title"
                :url="$articleUrl"
                size="large"
                lineClamp="line-clamp-4"
            />

            <x-post.description
                :description="$description"
                lineClamp="line-clamp-4"
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
                size="large"
                lineClamp="line-clamp-4"
            />

            <x-post.description
                :description="$description"
                lineClamp="line-clamp-4"
            />

            <x-post.author-date
                :author="$author"
                :authorUrl="$authorUrl"
                :date="$date"
            />
        </div>

        {{-- Image Section at Bottom --}}
        <div class="w-full h-[393px] md:h-[620px] rounded-xl overflow-hidden relative">
            <a href="{{ $articleUrl }}" class="group absolute inset-0 block">
                <div class="absolute inset-0 group-hover:opacity-80 transition-opacity duration-200">
                    <img src="{{ $image }}"
                         alt="{{ $imageAlt }}"
                         class="absolute inset-0 w-full h-full object-cover object-center">
                </div>
            </a>

            {{-- Metadata Badge --}}
            <div class="relative z-10 w-full h-full p-6 flex flex-col justify-end items-center pointer-events-none">
                <div class="inline-flex justify-center items-start gap-5 pointer-events-auto">
                    <x-post.metadata-badge
                        :category="$category"
                        :categoryUrl="$categoryUrl"
                        :tag="$tag"
                        :tagUrl="$tagUrl"
                        :bgColor="$badgeBgColor"
                    />
                </div>
            </div>
        </div>
    @endif

</div>
