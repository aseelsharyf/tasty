{{-- Article Meta Section - Sponsor Badge & Share Icons --}}
{{-- Based on Figma node 2048-800 --}}
@props([
    'post',
    'showSponsor' => true,
    'showShare' => true,
])

@php
    $url = request()->url();
@endphp

<div class="pt-16">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-10">
        <div class="flex flex-col gap-8 items-center">
            @if($showSponsor)
                {{-- Sponsor Badge --}}
                <div class="bg-white px-6 py-3 rounded-full flex items-center gap-5">
                    <span class="font-sans text-[20px] leading-[26px] font-normal text-tasty-blue-black">Powered by</span>
                    <img src="{{ Vite::asset('resources/images/dhiraagu.png') }}" alt="Dhiraagu" class="w-[204px] h-[51px] aspect-[4/1] object-contain" />
                </div>
            @endif

            @if($showShare)
                {{-- Share Icons --}}
                <x-article.share-icons :url="$url" :title="$post->title" />
            @endif
        </div>
    </div>
</div>
