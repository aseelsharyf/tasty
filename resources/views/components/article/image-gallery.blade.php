{{-- Article Two Images Side by Side Block --}}
@props([
    'images' => [], // Array of ['src', 'alt', 'caption']
])

<div class="w-full py-8 md:py-16 px-4 md:px-10">
    <div class="flex flex-col md:flex-row gap-8 md:gap-10 items-start justify-center max-w-[1360px] mx-auto">
        @foreach($images as $image)
            <div class="w-full md:flex-1 flex flex-col gap-6 md:gap-8">
                <div class="relative aspect-[582/750] w-full overflow-hidden">
                    <img
                        src="{{ $image['src'] ?? '' }}"
                        alt="{{ $image['alt'] ?? '' }}"
                        class="absolute inset-0 w-full h-full object-cover"
                    >
                </div>
                @if(!empty($image['caption']))
                    <p class="article-caption">{{ $image['caption'] }}</p>
                @endif
            </div>

            {{-- Add divider between images (not after the last one) --}}
            @if(!$loop->last)
                <div class="hidden md:block w-px bg-white self-stretch"></div>
            @endif
        @endforeach
    </div>
</div>
