{{-- Add to Cart Section --}}
<section class="w-full {{ $bgColorClass }}" @if($bgColorStyle) style="{{ $bgColorStyle }}" @endif>
    <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-16 max-lg:px-5 max-lg:pt-10 max-lg:pb-10 flex flex-col gap-16 max-lg:gap-10">
        {{-- Header --}}
        <div class="flex flex-col gap-5 items-center text-center text-blue-black w-full max-w-[660px] mx-auto">
            <h2 class="font-display text-[82px] leading-[1] tracking-[-0.04em] uppercase max-lg:text-[48px]">{{ $title }}</h2>
            <p class="text-[22px] leading-[1.4]">{{ $description }}</p>
        </div>

        {{-- Products Grid --}}
        @if($products->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <x-cards.product :product="$product" :show-price="true" />
                @endforeach
            </div>
            {{-- More Products Button --}}
            <div class="flex justify-center">
                <a href="{{ route('products.index') }}" class="btn btn-yellow">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>More Products</span>
                </a>
            </div>
        @else
            <div class="text-center py-12 text-gray-500">
                <p>No products available yet.</p>
            </div>
        @endif
    </div>
</section>
