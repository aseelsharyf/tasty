@extends('layouts.app')

@section('content')
    <div class="h-[96px] md:h-[112px]"></div>

    <section class="w-full bg-white">
        <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 max-lg:px-5 max-lg:pt-10 max-lg:pb-16 flex flex-col gap-16 max-lg:gap-10">
            {{-- Header --}}
            <div class="flex flex-col gap-5 items-center text-center text-blue-black w-full max-w-[660px] mx-auto">
                <h1 class="text-h1 uppercase">{{ $currentCategory?->name ?? 'Products' }}</h1>
                <p class="text-body-md">{{ $currentCategory?->description ?? 'Ingredients, tools, and staples we actually use.' }}</p>
            </div>

            {{-- Category Filters --}}
            @if($categories->isNotEmpty())
                <div class="flex flex-wrap justify-center gap-3">
                    <a
                        href="{{ route('products.index') }}"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ !$currentCategory ? 'bg-blue-black text-white' : 'bg-off-white text-blue-black hover:bg-gray-200' }}"
                    >
                        All
                    </a>
                    @foreach($categories as $category)
                        <a
                            href="{{ route('products.category', $category) }}"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $currentCategory?->id === $category->id ? 'bg-blue-black text-white' : 'bg-off-white text-blue-black hover:bg-gray-200' }}"
                        >
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Products Grid --}}
            @if($products->isNotEmpty())
                <div class="grid grid-cols-3 gap-10 max-lg:grid-cols-2 max-sm:grid-cols-1 max-lg:gap-6">
                    @foreach($products as $product)
                        <x-cards.product :product="$product" />
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-500 py-16">
                    <p class="text-body-lg">No products found.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
