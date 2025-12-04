{{-- resources/views/components/cards/video.blade.php
     Video review card with play functionality
--}}
@props([
    'video',
    'poster' => null,
    'tags' => [], // e.g., ['on the menu', 'review']
    'title' => '',
    'subtitle' => '',
    'description' => '',
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'url' => '#',
])

<article x-data="{ playing: false }" class="w-full max-w-[966px] rounded-xl overflow-hidden">
    {{-- Video Section --}}
    <div class="relative h-[300px] md:h-[544px]">
        <div
            class="absolute inset-0 cursor-pointer"
            @click="playing = !playing; playing ? $refs.video.play() : $refs.video.pause()"
        >
            <video
                x-ref="video"
                class="absolute inset-0 w-full h-full object-cover"
                loop
                muted
                playsinline
                poster="{{ $poster ?? $video }}"
            >
                <source src="{{ $video }}" type="video/mp4">
            </video>
        </div>

        {{-- Gradient Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-b from-transparent from-60% via-tasty-yellow/50 via-80% to-tasty-yellow pointer-events-none"></div>

        {{-- Tag Badge --}}
        @if(count($tags) > 0)
            <div class="absolute top-6 left-6 z-10">
                <x-ui.tag :items="$tags" bgColor="bg-white" />
            </div>
        @endif

        {{-- Title Overlay --}}
        <div class="absolute bottom-0 left-0 right-0 p-6 md:p-10 z-10">
            <h3 class="font-display text-3xl md:text-5xl text-tasty-blue-black uppercase tracking-tight">
                {{ $title }}
            </h3>
            @if($subtitle)
                <p class="font-display text-xl md:text-3xl text-tasty-blue-black tracking-tight">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    </div>

    {{-- Content Section --}}
    <div class="bg-tasty-yellow px-6 md:px-10 py-8 md:py-12 flex flex-col md:flex-row gap-6 md:gap-20 items-center md:items-end">
        {{-- Description --}}
        <div class="flex-1 flex flex-col gap-6 text-center md:text-left">
            <p class="font-sans text-lg md:text-xl text-tasty-blue-black leading-relaxed">
                {{ $description }}
            </p>

            <div class="flex items-center justify-center md:justify-start gap-5 text-sm uppercase tracking-wider text-tasty-blue-black">
                <span>BY {{ $author }}</span>
                <span>•</span>
                <span>{{ strtoupper($date) }}</span>
            </div>
        </div>

        {{-- Watch Button --}}
        <a
            href="{{ $url }}"
            class="inline-flex items-center gap-2 bg-white px-5 py-3 rounded-full font-sans text-xl text-tasty-blue-black hover:bg-gray-50 transition-colors"
        >
            <i class="fa-solid fa-play"></i>
            <span>Watch</span>
        </a>
    </div>
</article>
