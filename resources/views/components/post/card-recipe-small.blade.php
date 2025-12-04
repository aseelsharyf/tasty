{{-- resources/views/components/post/card-recipe-small.blade.php
     Small recipe card component
     Based on Figma node 158:946
     Photo section based on node 158:858
     Specs: w-310px (mobile) / w-426px (desktop), min-h-411px, rounded-12px, pb-40px, gap-32px
     Photo: flex-1, p-24px, rounded-t-4px, tag at bottom center
     Text: px-40px, gap-24px
--}}
@props([
    'image',
    'imageAlt' => '',
    'tags' => [], // e.g., ['recipe', 'vegan']
    'category' => null,
    'tag' => null,
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'title' => '',
    'url' => '#',
])

@php
    $tagItems = $tags;
    if (empty($tagItems)) {
        if ($category) $tagItems[] = $category;
        if ($tag) $tagItems[] = $tag;
    }
@endphp

{{-- Card: w-310px mobile, full width desktop, min-h-411px, rounded-12px, pb-40px, gap-32px --}}
<article class="w-[310px] lg:w-full min-h-[411px] bg-tasty-off-white rounded-[12px] overflow-hidden flex flex-col items-center gap-8 pb-10 shrink-0 lg:shrink">

    {{-- Photo Section: flex-1, p-24px, justify-end, items-center, rounded-t-4px --}}
    <a href="{{ $url }}" class="block relative w-full flex-1 min-h-[271px] lg:min-h-[424px] p-6 flex flex-col justify-end items-center hover:opacity-90 transition-opacity duration-200">
        {{-- Background Image --}}
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt ?: $title }}"
            class="absolute inset-0 w-full h-full object-cover object-center rounded-t-[4px]"
        >

        {{-- Tag Badge: bg-light-gray, p-12px, rounded-48px --}}
        @if(count($tagItems) > 0)
            <div class="relative z-10">
                <div class="bg-tasty-light-gray text-tasty-blue-black px-3 py-3 rounded-full inline-flex items-center justify-center gap-2.5 font-sans text-sm uppercase leading-3">
                    @foreach($tagItems as $index => $item)
                        <span>{{ $item }}</span>
                        @if($index < count($tagItems) - 1)
                            <span>•</span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </a>

    {{-- Text Section: px-40px, gap-24px --}}
    <div class="w-full px-6 lg:px-10 flex flex-col items-center gap-6">
        {{-- Title: H3 style - min-h ensures equal card heights --}}
        <a href="{{ $url }}" class="block w-full text-center hover:opacity-80 transition-opacity">
            <h3 class="text-h3 text-tasty-blue-black text-center min-h-[3.5em] flex items-center justify-center">
                {{ $title }}
            </h3>
        </a>

        {{-- Author/Date: 14px, uppercase, gap-20px --}}
        <x-post.author-date
            :author="$author"
            :authorUrl="$authorUrl"
            :date="$date"
            size="sm"
            layout="horizontal"
            align="center"
            color="text-tasty-blue-black"
            :hideDateOnMobile="true"
        />
    </div>
</article>
