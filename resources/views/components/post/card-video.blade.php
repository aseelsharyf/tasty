{{-- resources/views/components/post/card-video.blade.php --}}

@props([
    'video',
    'videoPoster' => null,
    'title' => '',
    'subtitle' => null,
    'description' => '',
    'author' => '',
    'authorUrl' => '#',
    'date' => '',
    'articleUrl' => '#',
])

<div
    x-data="{ playing: false }"
    class="w-full max-w-[966px] rounded-xl flex flex-col justify-start items-center overflow-hidden"
>
    {{-- Video Section --}}
    <div class="relative w-full h-[297px] md:h-[544px] bg-gradient-to-b from-tasty-yellow/0 from-60% via-yellow-300/50 via-80% to-yellow-300 flex flex-col justify-end md:justify-center items-start p-6 md:p-10">

        {{-- Video Element --}}
        <video
            x-ref="video"
            @click="playing = !playing; playing ? $refs.video.play() : $refs.video.pause()"
            class="absolute inset-0 w-full h-full object-cover cursor-pointer"
            loop
            muted
            playsinline
            poster="{{ $videoPoster }}"
        >
            <source src="{{ $video }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>

        {{-- Gradient Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-b from-tasty-yellow/0 from-60% via-yellow-300/50 via-80% to-yellow-300 pointer-events-none"></div>

        {{-- Title and Subtitle (Overlay) --}}
        <div class="relative z-10 w-full py-2.5 flex flex-col justify-start items-center md:items-start gap-2">
            <h2 class="w-full text-slate-950 text-3xl md:text-5xl font-normal font-serif uppercase leading-tight md:leading-[48px] text-center md:text-left">
                {{ $title }}
            </h2>

            @if($subtitle)
                <h3 class="w-full text-slate-950 text-xl md:text-3xl font-normal font-serif leading-tight md:leading-9 text-center md:text-left">
                    {{ $subtitle }}
                </h3>
            @endif
        </div>
    </div>

    {{-- Content Section --}}
    <div class="w-full px-6 md:px-10 pt-6 pb-8 md:pb-12 bg-yellow-300 flex flex-col md:flex-row justify-center md:justify-start items-center md:items-end gap-6 md:gap-20">

        {{-- Description and Metadata --}}
        <div class="w-full md:flex-1 flex flex-col justify-end items-center md:items-start gap-6">
            <x-post.description
                :description="$description"
                size="xl"
                align="center md:left"
                color="text-slate-950"
            />

            <div class="w-full flex justify-center md:justify-start">
                <x-post.author-date
                    :author="$author"
                    :authorUrl="$authorUrl"
                    :date="$date"
                    color="text-slate-950"
                />
            </div>
        </div>

        {{-- Watch Button --}}
        <div class="flex flex-col justify-center md:justify-start items-center md:items-start gap-2.5">
            <x-ui.button
                :url="$articleUrl"
                text="Watch"
                icon="play"
                paddingSize="lg"
                bgColor="bg-white"
                textColor="text-slate-950"
                hoverBg="hover:bg-stone-50"
                textStyle="normal"
            />
        </div>
    </div>
</div>
