{{-- Article Credits Section - Based on Figma node 2048-800 --}}
@props([
    'post' => [],
    'url' => null,
    'isRtl' => false,
])

@php
    $title = $post['title'] ?? 'Untitled';
    $author = $post['author'] ?? null;
    $photographer = $post['photographer'] ?? null;
    $publishedAt = $post['published_at'] ?? null;
    $url = $url ?? request()->url();
@endphp

<div class="bg-tasty-yellow py-8">
    <div class="container px-4 lg:px-10">
        <div class="flex flex-col gap-8 items-center">
            {{-- Sponsor Badge --}}
            <div class="bg-white px-6 py-3 rounded-full flex items-center gap-5">
                <span class="font-sans text-[20px] leading-[26px] font-normal text-tasty-blue-black">Powered by</span>
                {{-- Logo --}}
                <img src="{{Vite::asset('resources/images/dhiraagu.png')}}" alt="Dhiraagu" class="w-[204px] h-[51px] aspect-[4/1] object-contain" />
            </div>

            {{-- Author/Photographer/Date Row --}}
            <div class="flex items-center gap-5 text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans flex-wrap justify-center">
                @if($author)
                    <a href="{{ $author['url'] ?? '#' }}" class="underline underline-offset-4 hover:no-underline">
                        BY {{ $author['name'] }}
                    </a>
                @endif

                @if($photographer)
                    <span>&bull;</span>
                    <span>PHOTO BY {{ $photographer }}</span>
                @endif

                @if($publishedAt)
                    <span>&bull;</span>
                    <span>{{ \Carbon\Carbon::parse($publishedAt)->format('F j, Y') }}</span>
                @endif
            </div>

            {{-- Share Icons --}}
            <x-article.share-icons :url="$url" :title="$title" />
        </div>
    </div>
</div>
