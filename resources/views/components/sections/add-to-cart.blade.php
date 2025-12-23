{{-- Add to Cart Section --}}
@props([
    'title' => 'ADD TO CART',
    'description' => 'Ingredients, tools, and staples we actually use.',
    'bgColor' => 'white',
    'products' => [],
])

@php
    $bgClass = \App\View\Colors::bg($bgColor, 'white');
@endphp

<section class="w-full {{ $bgClass }}">
    <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 max-lg:px-5 max-lg:pt-10 max-lg:pb-16 flex flex-col gap-16 max-lg:gap-10">
        {{-- Header --}}
        <div class="flex flex-col gap-5 items-center text-center text-blue-black w-full max-w-[660px] mx-auto">
            <h2 class="text-h1 uppercase">{{ $title }}</h2>
            <p class="text-body-lg">{{ $description }}</p>
        </div>

        {{-- Products Grid --}}
        <div class="grid grid-cols-3 gap-10 max-lg:grid-cols-1 max-lg:gap-6">
            @foreach($products as $product)
                <x-cards.product
                    :title="$product['title']"
                    :description="$product['description']"
                    :image="$product['image']"
                    :imageAlt="$product['imageAlt']"
                    :tags="$product['tags']"
                    :url="$product['url']"
                />
            @endforeach
        </div>
    </div>
</section>
