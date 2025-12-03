{{-- resources/views/components/post/section-intro.blade.php --}}

@props([
    'image',
    'imageAlt' => '',
    'title' => '',
    'titleLarge' => '',
    'description' => ''
])

<div class="flex flex-col justify-start items-center gap-8">
    <img class="w-full h-[358px] md:h-[429.5px] object-cover rounded-xl"
         src="{{ $image }}"
         alt="{{ $imageAlt }}" />

    <div class="w-full flex flex-col justify-start items-start gap-6">
        <div class="w-full flex flex-col justify-start items-center">
            @if($title)
                <div class="text-center text-slate-950 text-3xl md:text-6xl font-serif leading-tight md:leading-[66px]">
                    {{ $title }}
                </div>
            @endif

            @if($titleLarge)
                <div class="w-full text-center text-slate-950 text-4xl md:text-8xl font-serif uppercase leading-tight md:leading-[86px]">
                    {{ $titleLarge }}
                </div>
            @endif
        </div>

        @if($description)
            <div class="w-full text-center text-slate-950 text-base md:text-2xl font-normal leading-6">
                {{ $description }}
            </div>
        @endif
    </div>
</div>
