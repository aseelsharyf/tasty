@php
    // Alignment classes
    $isLeftAligned = $alignment === 'left';
    $alignItems = $isLeftAligned ? 'items-start' : 'items-center';
    $textAlign = $isLeftAligned ? 'text-left' : 'text-center';
    $justifyMeta = $isLeftAligned ? 'justify-start' : 'justify-center';

    $wrapperClass = match($variant) {
        'featured' => "w-full bg-[#F7F7F7] rounded-[12px] overflow-hidden flex flex-col {$alignItems} gap-8 pb-10",
        'grid' => 'w-full bg-[#F7F7F7] rounded-[12px] overflow-hidden flex flex-col items-center gap-8 pb-10',
        'mobile' => 'w-full bg-[#F7F7F7] rounded-[12px] overflow-hidden flex flex-col items-center gap-6 pb-8 shrink-0',
        'scroll' => 'w-full h-full bg-[#F7F7F7] rounded-[12px] overflow-hidden flex flex-col items-center',
        default => 'w-full bg-[#F7F7F7] rounded-[12px] overflow-hidden flex flex-col items-center gap-8 pb-10',
    };

    $imageWrapperClass = match($variant) {
        'featured' => 'block relative w-full h-[599px] max-lg:h-[400px] p-6 flex flex-col justify-end items-center',
        'grid' => 'block relative w-full aspect-square p-6 flex flex-col justify-end items-center',
        'mobile' => 'block relative w-full aspect-square p-5 flex flex-col justify-end items-center',
        'scroll' => 'block relative w-full aspect-square p-5 flex flex-col justify-end items-center',
        default => 'block relative w-full aspect-square p-6 flex flex-col justify-end items-center',
    };

    $contentClass = match($variant) {
        'featured' => "w-full px-10 max-lg:px-6 flex flex-col {$alignItems} gap-6",
        'grid' => 'w-full px-10 max-lg:px-6 flex flex-col items-center gap-6',
        'mobile' => 'w-full px-6 flex flex-col items-center gap-4',
        'scroll' => 'w-full flex-1 px-6 py-6 flex flex-col items-center justify-between',
        default => 'w-full px-10 max-lg:px-6 flex flex-col items-center gap-6',
    };

    $isFeatured = $variant === 'featured';
@endphp

<article class="{{ $wrapperClass }}">
    {{-- Image with tag --}}
    <a href="{{ $url }}" class="{{ $imageWrapperClass }}">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            class="absolute inset-0 w-full h-full object-cover rounded-t-[4px]"
        >
        @if(count($tags) > 0)
            <div class="relative z-10">
                <span class="tag">{{ implode(' • ', $tags) }}</span>
            </div>
        @endif
    </a>

    {{-- Content --}}
    <div class="{{ $contentClass }}">
        {{-- Title --}}
        <a href="{{ $url }}" class="hover:opacity-80 transition-opacity">
            <h3 class="text-h4 text-blue-black {{ $isFeatured ? $textAlign : 'text-center' }} line-clamp-2">{{ $title }}</h3>
        </a>

        {{-- Description - Only on featured variant --}}
        @if($isFeatured && $description)
            <p class="text-body-md text-blue-black {{ $textAlign }} line-clamp-4">{{ $description }}</p>
        @endif

        {{-- Author/date --}}
        <div class="flex flex-wrap items-center {{ $isFeatured ? $justifyMeta : 'justify-center' }} gap-5 text-[14px] leading-[12px] uppercase text-blue-black">
            @if($author)
                <a href="#" class="underline underline-offset-4 hover:opacity-80 transition-opacity">BY {{ strtoupper($author) }}</a>
                <span>•</span>
            @endif
            @if($date)
                <span>{{ strtoupper($date) }}</span>
            @endif
        </div>
    </div>
</article>
