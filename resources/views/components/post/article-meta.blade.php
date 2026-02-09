{{-- Article Meta Section - Sponsor Badge & Share Icons --}}
@props([
    'post',
    'showSponsor' => true,
    'showShare' => true,
])

@php
    $url = request()->url();
    $sponsor = $post->sponsor;
    $sponsorLabel = $sponsor?->label ?: __('Supported by');
    $photographer = $post->getCustomField('photographer');
@endphp

<div class="pt-16 pb-12">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-10">
        <div class="flex flex-col gap-8 items-center">
            


            {{-- Sponsor Badge --}}
            <x-article.sponsor-badge :sponsor="$post->sponsor" />

            {{-- Author/Photographer/Date Row --}}
            <div class="flex items-center gap-5 text-[14px] leading-[12px] uppercase text-tasty-blue-black font-sans flex-wrap">
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

            @if($showShare)
                {{-- Share Icons --}}
                <x-article.share-icons :url="$url" :title="$post->title" />
            @endif
        </div>
    </div>
</div>
