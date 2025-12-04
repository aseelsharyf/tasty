{{-- resources/views/components/feature-destination.blade.php
     Featured destination section with curved overlay (Ceylon style)
--}}
@props([
    'image',
    'imageAlt' => '',
    'title' => '',
    'subtitle' => '',
    'category' => '',
    'tag' => '',
    'description' => '',
    'buttonText' => '',
    'buttonUrl' => '#',
    'bgColor' => 'bg-tasty-yellow',
])

{{-- Aspect ratio: ~9:16 mobile, ~4:3 desktop for good image visibility --}}
<section class="relative w-full aspect-[9/16] lg:aspect-[4/3] max-h-[700px] lg:max-h-[900px] flex flex-col items-center justify-end">
    {{-- Background Image --}}
    <div class="absolute inset-0 -z-10">
        @if($image)
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt ?: $title }}"
                class="w-full h-full object-cover object-center"
            />
        @else
            <div class="w-full h-full bg-gray-300"></div>
        @endif
    </div>

    {{-- Yellow Arch Content Area --}}
    <div class="relative w-full {{ $bgColor }} rounded-t-[1000px] lg:rounded-t-[3000px] pt-[60px] pb-[40px] px-[24px] lg:pt-[80px] lg:pb-[40px] lg:px-[100px] xl:px-[200px] flex flex-col items-center justify-center gap-4 lg:gap-8 text-tasty-blue-black">
        {{-- Title and Subtitle --}}
        <div class="flex flex-col items-center justify-center gap-2 lg:gap-4 text-center w-full">
            @if($title)
                <x-ui.heading
                    level="h1"
                    variant="hero"
                    :text="$title"
                    align="center"
                />
            @endif
            @if($subtitle)
                <x-ui.heading
                    level="h2"
                    :text="$subtitle"
                    align="center"
                />
            @endif
        </div>

        {{-- Category Tags --}}
        @if($category || $tag)
            <div class="flex items-center justify-center gap-4 lg:gap-5 uppercase">
                <x-ui.text variant="sm">
                    @if($category)
                        <span>{{ $category }}</span>
                    @endif
                    @if($category && $tag)
                        <span class="mx-2 lg:mx-3">•</span>
                    @endif
                    @if($tag)
                        <span>{{ $tag }}</span>
                    @endif
                </x-ui.text>
            </div>
        @endif

        {{-- Description --}}
        @if($description)
            <x-ui.text
                variant="lg"
                :text="$description"
                align="center"
            />
        @endif

        {{-- Button (optional) --}}
        @if($buttonText)
            <x-ui.button
                :url="$buttonUrl"
                :text="$buttonText"
                icon="plus"
                :iconRotate="true"
                bgColor="bg-white"
                paddingSize="md"
            />
        @endif
    </div>
</section>
