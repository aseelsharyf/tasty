{{-- resources/views/components/restaurant/card.blade.php --}}

@props([
    'image',
    'imageAlt' => '',
    'name' => '',
    'subtitle' => '',
    'description' => '',
    'rating' => 5, // Star rating out of 5
    'articleUrl' => '#',
])

<a href="{{ $articleUrl }}" class="group w-full max-w-[310px] md:max-w-[400px] flex flex-col justify-start items-center gap-8">
    {{-- Image Section with Badge --}}
    <div class="w-full h-[362px] md:h-[482.5px] rounded-xl overflow-hidden relative">
        <div
            class="absolute inset-0 bg-cover bg-center group-hover:opacity-80 transition-opacity duration-200"
            style="background-image: url('{{ $image }}');"
        ></div>
        <div class="relative z-10 w-full h-full p-6 flex flex-col justify-end items-center gap-2.5">
            {{-- Review Badge with Stars --}}
            <div class="inline-flex justify-start items-start gap-5">
                <div class="p-3 bg-white rounded-full flex justify-center items-center gap-2.5">
                    <span class="text-slate-950 text-sm font-normal uppercase leading-3">review</span>
                    <span class="text-slate-950 text-sm font-normal uppercase leading-3">•</span>

                    {{-- Star Rating --}}
                    <div class="flex justify-start items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $rating)
                                <i class="fa-solid fa-star text-orange-500 text-xs"></i>
                            @else
                                <i class="fa-regular fa-star text-gray-300 text-xs"></i>
                            @endif
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content Section --}}
    <div class="w-full flex flex-col justify-start items-center gap-5">
        {{-- Title and Subtitle --}}
        <div class="w-full flex flex-col justify-start items-center">
            <x-ui.heading
                level="h3"
                :text="$name"
                align="center"
                :uppercase="true"
            />

            <x-ui.heading
                level="h4"
                :text="$subtitle"
                align="center"
            />
        </div>

        {{-- Description --}}
        <x-content.description
            :description="$description"
            size="xl"
            align="center"
        />
    </div>
</a>
