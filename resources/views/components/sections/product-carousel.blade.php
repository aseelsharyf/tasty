{{-- Product Carousel Section --}}
{{-- Horizontal scrollable carousel of products --}}
@props([
    'products',
    'title' => "Start Shopping",
])

@if($products->isNotEmpty())
<section class="w-full bg-off-white py-12 md:py-16">
    {{-- Centered title --}}
    <div class="mb-8">
        <h2 class="text-h2 text-blue-black text-center">{{ $title }}</h2>
    </div>

    {{-- Divider --}}
    <div class="max-w-[1880px] mx-auto px-5 md:px-10">
        <div class="w-full h-[2px] bg-blue-black mb-8"></div>
    </div>

    {{-- Scrollable product list (full-bleed, no side padding on desktop) --}}
    <div
        class="flex justify-center gap-3 md:gap-6 overflow-x-auto scrollbar-hide scroll-smooth px-4 md:px-0"
        style="-ms-overflow-style: none; scrollbar-width: none;"
    >
        @foreach($products as $product)
            <a
                href="{{ $product->url }}"
                class="group"
                style="flex: 0 0 calc((100vw - 56px) / 2.5); max-width: 220px;"
                @if($product->id) data-product-id="{{ $product->id }}" @endif
            >
                {{-- Product Image --}}
                <div class="h-[180px] bg-white rounded-lg overflow-hidden mb-3 flex items-center justify-center">
                    <img
                        src="{{ $product->featured_image_url }}"
                        alt="{{ $product->featuredMedia?->alt_text ?? $product->title }}"
                        loading="lazy"
                        decoding="async"
                        class="max-w-full max-h-full object-contain p-3"
                    >
                </div>

                {{-- Product Name --}}
                <h3 class="text-body-md font-semibold text-blue-black text-center line-clamp-2 mb-1">{{ $product->title }}</h3>

                {{-- Price --}}
                @if($product->formatted_price)
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-body-sm font-semibold text-blue-black">{{ $product->formatted_price }}</span>
                    </div>
                @endif
            </a>
        @endforeach
    </div>
</section>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
</style>
@endif
