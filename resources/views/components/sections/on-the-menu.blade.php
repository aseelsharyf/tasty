{{-- On the Menu Section --}}
<div class="w-full py-16 max-md:py-8">
    <div class="container-main px-5 lg:px-10">
        {{-- Desktop: 2 rows of 3 columns with dividers --}}
        <div class="max-md:hidden flex flex-col gap-10">
            {{-- Row 1: Title + First 2 restaurants --}}
            <div class="flex gap-10">
                {{-- Title Column --}}
                <div class="flex-1 flex flex-col gap-5 items-center justify-center">
                    <div class="w-full max-w-[280px]">
                        <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-auto object-contain" style="mix-blend-mode: darken;">
                    </div>
                    <div class="flex flex-col items-center text-center text-blue-black">
                        <span class="text-h2">{{ $titleSmall }}</span>
                        <h2 class="text-h1 uppercase">{{ $titleLarge }}</h2>
                    </div>
                    <p class="text-body-large text-blue-black text-center">{{ $description }}</p>
                </div>

                @foreach(array_slice($restaurants, 0, 2) as $index => $restaurant)
                    {{-- Divider --}}
                    <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.5px] outline-white"></div>
                    {{-- Restaurant Card --}}
                    <x-cards.restaurant
                        :post="$restaurant"
                        variant="grid"
                    />
                @endforeach
            </div>

            {{-- Row 2: Remaining restaurants --}}
            @if(count($restaurants) > 2)
                <div class="flex gap-10">
                    @foreach(array_slice($restaurants, 2) as $index => $restaurant)
                        @if($index > 0)
                            {{-- Divider --}}
                            <div class="w-0 self-stretch outline outline-1 outline-offset-[-0.5px] outline-white"></div>
                        @endif
                        {{-- Restaurant Card --}}
                        <x-cards.restaurant
                            :post="$restaurant"
                            variant="grid"
                        />
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Mobile: Title Section (inside container) --}}
        <div class="md:hidden flex flex-col gap-5 items-center justify-center">
            <div class="w-full max-w-[200px]">
                <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-auto object-contain" style="mix-blend-mode: darken;">
            </div>
            <div class="flex flex-col items-center text-center text-blue-black">
                <span class="text-h2">{{ $titleSmall }}</span>
                <h2 class="text-h1 uppercase">{{ $titleLarge }}</h2>
            </div>
            <p class="text-body-large text-blue-black text-center">{{ $description }}</p>
        </div>
    </div>

    {{-- Mobile: Horizontal scroll (outside container for full-width) --}}
    <div class="md:hidden scroll-container mt-8">
        <div class="flex gap-5 px-5 min-w-max">
            @foreach($restaurants as $restaurant)
                <x-cards.restaurant
                    :post="$restaurant"
                    variant="mobile"
                />
            @endforeach
        </div>
    </div>
</div>
