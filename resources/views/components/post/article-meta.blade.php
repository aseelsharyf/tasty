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
@endphp

<div class="pt-16">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-10">
        <div class="flex flex-col gap-8 items-center">
            @if($showSponsor && $sponsor)
                {{-- Sponsor Badge --}}
                <div class="bg-white px-6 py-3 rounded-full flex items-center gap-5">
                    <span class="font-sans text-[20px] leading-[26px] font-normal text-tasty-blue-black">{{ $sponsorLabel }}</span>
                    @if($sponsor->featuredMedia)
                        <a href="{{ $sponsor->url }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $sponsor->featuredMedia->url }}" alt="{{ $sponsor->name }}" class="max-w-[204px] max-h-[51px] object-contain" />
                        </a>
                    @else
                        <a href="{{ $sponsor->url }}" target="_blank" rel="noopener noreferrer" class="font-sans text-[20px] leading-[26px] font-semibold text-tasty-blue-black hover:text-primary transition-colors">
                            {{ $sponsor->name }}
                        </a>
                    @endif
                </div>
            @endif

            @if($showShare)
                {{-- Share Icons --}}
                <x-article.share-icons :url="$url" :title="$post->title" />
            @endif
        </div>
    </div>
</div>
