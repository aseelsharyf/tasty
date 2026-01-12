{{-- Sponsor Badge - Displays sponsor logo with optional link --}}
@props([
    'sponsor',  // Sponsor model (can be null)
])

@if($sponsor)
    @php
        $sponsorUrl = $sponsor->url;
        $sponsorName = $sponsor->name;
        $sponsorLogo = $sponsor->featured_image_url;
    @endphp

    <div class="bg-white px-6 py-3 rounded-full flex items-center gap-5">
        <span class="font-sans text-[20px] leading-[26px] font-normal text-tasty-blue-black">Supported by</span>
        @if($sponsorUrl)
            <a href="{{ $sponsorUrl }}" target="_blank" rel="noopener noreferrer" class="hover:opacity-80 transition-opacity">
                @if($sponsorLogo)
                    <img src="{{ $sponsorLogo }}" alt="{{ $sponsorName }}" class="h-[40px] object-contain" />
                @else
                    <span class="font-sans text-[20px] leading-[26px] font-semibold text-tasty-blue-black">{{ $sponsorName }}</span>
                @endif
            </a>
        @else
            @if($sponsorLogo)
                <img src="{{ $sponsorLogo }}" alt="{{ $sponsorName }}" class="h-[40px] object-contain" />
            @else
                <span class="font-sans text-[20px] leading-[26px] font-semibold text-tasty-blue-black">{{ $sponsorName }}</span>
            @endif
        @endif
    </div>
@endif
