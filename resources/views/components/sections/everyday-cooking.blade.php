{{-- Everyday Cooking Section --}}
<div class="w-full max-w-[1880px] mx-auto pt-16 pb-16 lg:pb-32" style="background: linear-gradient(to bottom, var(--color-yellow) 0%, white 15%);">
    <div class="container-main px-5 lg:px-10">
        {{-- Row 1: Title + Featured Card --}}
        <div class="flex flex-col lg:grid lg:grid-cols-2 gap-10 lg:gap-16 mb-10">
            {{-- Title Column --}}
            <div class="flex items-center justify-center">
                <div class="w-full max-w-[310px] lg:max-w-[400px] flex flex-col justify-start items-center gap-5 lg:gap-8">
                    @if($introImage)
                        <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full max-h-[156px] lg:max-h-[430px] object-contain" style="mix-blend-mode: darken;">
                    @endif
                    <div class="w-full flex flex-col justify-start items-center gap-6">
                        <div class="w-full flex flex-col justify-start items-center">
                            <span class="text-h2 text-blue-black text-center">{{ $titleSmall }}</span>
                            <h2 class="text-h1 text-blue-black text-center uppercase">{{ $titleLarge }}</h2>
                        </div>
                        @if($description)
                            <p class="text-body-large text-blue-black text-center">{{ $description }}</p>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Featured Recipe Card --}}
            @if($featuredPost)
                <div class="w-full">
                    <x-cards.recipe
                        :post="$featuredPost"
                        variant="featured"
                    />
                </div>
            @endif
        </div>

        {{-- Row 2: Recipe Cards - Grid on desktop --}}
        @if($posts->isNotEmpty())
            <div class="hidden lg:grid lg:grid-cols-3 gap-10">
                @foreach($posts as $post)
                    <x-cards.recipe
                        :post="$post"
                        variant="grid"
                    />
                @endforeach
            </div>
        @endif
    </div>

    {{-- Mobile: Horizontal scroll (breaks out of container) --}}
    @if($posts->isNotEmpty())
        <div class="lg:hidden scroll-container">
            <div class="flex gap-5 px-5 min-w-max">
                @foreach($posts as $post)
                    <x-cards.recipe
                        :post="$post"
                        variant="mobile"
                    />
                @endforeach
            </div>
        </div>
    @endif
</div>
