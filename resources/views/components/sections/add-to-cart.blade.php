{{-- Add to Cart Section --}}
<section class="w-full {{ $bgColorClass }}" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 max-lg:px-5 max-lg:pt-10 max-lg:pb-16 flex flex-col gap-16 max-lg:gap-10">
        {{-- Header --}}
        <div class="flex flex-col gap-5 items-center text-center text-blue-black w-full max-w-[660px] mx-auto">
            <h2 class="font-display text-[82px] leading-[1] tracking-[-0.04em] uppercase max-lg:text-[48px]">{{ $title }}</h2>
            <p class="text-[22px] leading-[1.4]">{{ $description }}</p>
        </div>

        {{-- Products Grid --}}
        @if($products->count() > 0)
            <div class="flex flex-wrap justify-center gap-10 max-lg:flex-col max-lg:items-center max-lg:gap-6">
                @foreach($products as $product)
                    <x-cards.product :product="$product" :show-price="false" />
                @endforeach
            </div>
        @else
            <div class="text-center py-12 text-gray-500">
                <p>No products available yet.</p>
            </div>
        @endif
    </div>
</section>
