{{-- resources/views/components/feature-destination.blade.php
     Featured destination section with curved overlay (Ceylon style)
     Based on Figma Frame 117 (node 158:1028)
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

<section class="relative w-full h-[750px] md:min-h-[1058px] flex flex-col items-center justify-end">
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
    {{-- Mobile: pt-[96px] pb-[64px] px-[40px] gap-[24px] --}}
    {{-- Desktop: h-[735px], pt-[110px], px-[362px], gap-[40px], rounded-t-[5000px] --}}
    <div class="relative w-full {{ $bgColor }} rounded-t-[2000px] md:rounded-t-[5000px] pt-[96px] pb-[64px] px-[40px] md:pt-[110px] md:pb-0 md:px-[100px] lg:px-[200px] xl:px-[362px] md:h-[735px] flex flex-col items-center justify-center gap-6 md:gap-10 text-tasty-blue-black">
        {{-- Title and Subtitle --}}
        <div class="flex flex-col items-center justify-center gap-3 md:gap-4 text-center w-full font-display">
            @if($title)
                {{-- Desktop: 200px/160px, tracking -8px, uppercase --}}
                <h1 class="text-[60px] leading-[50px] tracking-[-2.4px] md:text-[200px] md:leading-[160px] md:tracking-[-8px] uppercase">
                    {{ $title }}
                </h1>
            @endif
            @if($subtitle)
                {{-- Desktop: 64px/66px, tracking -2.56px --}}
                <h2 class="text-[32px] leading-[36px] tracking-[-1.28px] md:text-[64px] md:leading-[66px] md:tracking-[-2.56px]">
                    {{ $subtitle }}
                </h2>
            @endif
        </div>

        {{-- Category Tags --}}
        {{-- Desktop: 14px/12px, uppercase, gap 20px --}}
        @if($category || $tag)
            <div class="flex items-center justify-center gap-4 md:gap-5 text-xs md:text-[14px] leading-3 font-sans uppercase">
                @if($category)
                    <span>{{ $category }}</span>
                @endif
                @if($category && $tag)
                    <span>•</span>
                @endif
                @if($tag)
                    <span>{{ $tag }}</span>
                @endif
            </div>
        @endif

        {{-- Description --}}
        {{-- Desktop: 24px/26px --}}
        @if($description)
            <p class="text-[18px] leading-[24px] md:text-[24px] md:leading-[26px] text-center w-full font-sans">
                {{ $description }}
            </p>
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
