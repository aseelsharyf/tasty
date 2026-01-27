{{-- EditorJS Quote Block --}}

@props([
    'text' => '',
    'caption' => '',
    'alignment' => 'left',
    'author' => null, // { name, title, photo }
    'displayType' => 'default', // 'default', 'large', 'small'
    'isRtl' => false,
])

@php
    $alignClass = match($alignment) {
        'center' => 'text-center',
        'right' => 'text-right',
        default => $isRtl ? 'text-right' : 'text-left',
    };

    $authorName = $author['name'] ?? $caption ?? null;
    $authorTitle = $author['title'] ?? null;
    $authorPhoto = $author['photo'] ?? null;
    $hasPhoto = $authorPhoto && ($authorPhoto['url'] ?? $authorPhoto['thumbnail_url'] ?? null);
    $photoUrl = $authorPhoto['url'] ?? $authorPhoto['thumbnail_url'] ?? '';
@endphp

@if($displayType === 'large' && $hasPhoto)
    {{-- Large: Side-by-side with big photo --}}
    <div class="bg-tasty-off-white w-full">
        <div class="max-w-[1440px] mx-auto">
            <div class="flex flex-col lg:flex-row gap-10 items-center justify-center px-4 lg:px-10 py-12 lg:py-16">
                {{-- Left: Large photo with caption --}}
                <div class="flex flex-col gap-8 w-full lg:w-[582px] shrink-0">
                    <div class="aspect-[582/750] w-full overflow-hidden">
                        <img
                            src="{{ $photoUrl }}"
                            alt="{{ $authorPhoto['alt_text'] ?? $authorName ?? 'Quote image' }}"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    @if($authorPhoto['caption'] ?? $caption)
                        <p class="text-caption text-black/50 text-center">
                            {{ $authorPhoto['caption'] ?? $caption }}
                        </p>
                    @endif
                </div>

                {{-- Vertical divider (hidden on mobile) --}}
                <div class="hidden lg:block w-px h-[400px] bg-white self-center"></div>

                {{-- Right: Quote text --}}
                <blockquote class="flex-1 flex flex-col justify-center min-w-0 {{ $isRtl ? 'text-right' : '' }}">
                    <p class="font-display text-black" style="font-size: 42px; line-height: 42px; letter-spacing: -1.68px;">
                        {!! $text !!}
                    </p>
                </blockquote>
            </div>
        </div>
    </div>

@elseif($displayType === 'small' && $hasPhoto)
    {{-- Small: Thumbnail photo with yellow accent --}}
    <div class="bg-tasty-off-white w-full py-12 lg:py-16">
        <div class="max-w-[1440px] mx-auto px-4 lg:px-10 xl:px-[147px]">
            <div class="flex flex-col sm:flex-row gap-10 items-start">
                {{-- Left: Photo with yellow accent behind --}}
                <div class="relative shrink-0 w-[310px]">
                    <div class="absolute top-[54px] left-0  w-[192px] h-[226px]"></div>
                    {{-- Image --}}
                    <div class="relative w-[310px] h-[226px] overflow-hidden">
                        <img
                            src="{{ $photoUrl }}"
                            alt="{{ $authorPhoto['alt_text'] ?? $authorName ?? 'Quote image' }}"
                            class="w-full h-full object-cover object-center"
                        />
                    </div>
                </div>

                {{-- Right: Quote text --}}
                <blockquote class="flex-1 flex flex-col justify-center min-w-0 {{ $isRtl ? 'text-right' : '' }}">
                    <p class="font-display text-black" style="font-size: 42px; line-height: 42px; letter-spacing: -1.68px;">
                        {!! $text !!}
                    </p>
                </blockquote>
            </div>
        </div>
    </div>

@else
    {{-- Default: Centered pull quote (no photo or fallback) --}}
    <blockquote class="{{ $alignClass }}">
        <p class="font-display text-tasty-blue-black mb-6" style="font-size: 42px; line-height: 42px; letter-spacing: -1.68px;">
            {!! $text !!}
        </p>

        @if($authorName)
            <footer class="flex items-center {{ $alignment === 'center' ? 'justify-center' : '' }} gap-4 mt-8">
                <div>
                    <cite class="text-body-md text-tasty-blue-black not-italic font-medium">
                        — {{ $authorName }}
                    </cite>
                    @if($authorTitle)
                        <p class="text-body-sm text-tasty-blue-black/50">
                            {{ $authorTitle }}
                        </p>
                    @endif
                </div>
            </footer>
        @endif
    </blockquote>
@endif
