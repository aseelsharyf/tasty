{{-- Article Meta Section - Sponsor, Author/Date, Share Icons --}}
{{-- Based on Figma node 2048-800 --}}
@props([
    'post',  // Post model
])

@php
    $url = request()->url();
    $photographer = $post->getCustomField('photographer');
@endphp

<div class="py-4">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-10">
        <div class="flex flex-col gap-8 items-center">
            {{-- Sponsor Badge --}}
            <div class="bg-white px-6 py-3 rounded-full flex items-center gap-5">
                <span class="font-sans text-[20px] leading-[26px] font-normal text-tasty-blue-black">Powered by</span>
                <img src="{{ Vite::asset('resources/images/dhiraagu.png') }}" alt="Dhiraagu" class="w-[204px] h-[51px] aspect-[4/1] object-contain" />
            </div>

            {{-- Author/Photographer/Date Row --}}
            <div class="flex items-center gap-5 text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans flex-wrap justify-center">
                @if($post->author)
                    <a href="{{ $post->author->url ?? '#' }}" class="underline underline-offset-4 hover:no-underline">
                        BY {{ $post->author->name }}
                    </a>
                @endif

                @if($photographer)
                    @if($post->author)
                        <span>&bull;</span>
                    @endif
                    <span>PHOTO BY {{ $photographer }}</span>
                @endif

                @if($post->published_at)
                    @if($post->author || $photographer)
                        <span>&bull;</span>
                    @endif
                    <span>{{ $post->published_at->format('F j, Y') }}</span>
                @endif
            </div>

            {{-- Share Icons --}}
            <x-article.share-icons :url="$url" :title="$post->title" />
        </div>
    </div>
</div>
