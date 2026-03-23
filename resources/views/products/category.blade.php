@extends('layouts.app')

@section('content')
    <div class="h-[96px] md:h-[112px]"></div>

    <section class="w-full bg-white">
        <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 max-lg:px-5 max-lg:pt-10 max-lg:pb-16 flex flex-col gap-16 max-lg:gap-10">
            {{-- Header --}}
            <div class="flex flex-col gap-5 items-center text-center text-blue-black w-full max-w-[660px] mx-auto">
                <h1 class="text-h1 uppercase">{{ $currentCategory->name }}</h1>
                @if($currentCategory->description)
                    <p class="text-body-md">{{ $currentCategory->description }}</p>
                @endif
            </div>

            {{-- Category Filters --}}
            @if($categories->isNotEmpty())
                <div class="relative -mx-5 lg:mx-0" x-data="{
                    atStart: true,
                    atEnd: false,
                    check() {
                        const el = this.$refs.scroller;
                        this.atStart = el.scrollLeft <= 4;
                        this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
                    }
                }">
                    {{-- Left fade (mobile only) --}}
                    <div class="pointer-events-none absolute left-0 top-0 bottom-0 w-12 bg-gradient-to-r from-white to-transparent z-10 lg:hidden transition-opacity duration-150" :class="atStart ? 'opacity-0' : 'opacity-100'"></div>
                    {{-- Right fade (mobile only) --}}
                    <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-12 bg-gradient-to-l from-white to-transparent z-10 lg:hidden transition-opacity duration-150" :class="atEnd ? 'opacity-0' : 'opacity-100'"></div>
                    <div class="px-5 lg:px-0 overflow-x-auto lg:overflow-visible scrollbar-hide" x-ref="scroller" @scroll="check()" x-init="check()">
                        <div class="flex lg:flex-wrap lg:justify-center gap-2 lg:gap-3 w-max lg:w-auto px-3 lg:px-0">
                            <a
                                href="{{ route('products.index') }}"
                                class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors bg-off-white text-blue-black hover:bg-gray-200"
                            >
                                All
                            </a>
                            @foreach($categories as $category)
                                <a
                                    href="{{ route('products.category', $category) }}"
                                    class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors {{ $currentCategory->id === $category->id ? 'bg-blue-black text-white' : 'bg-off-white text-blue-black hover:bg-gray-200' }}"
                                >
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Products Grid --}}
            @if($products->isNotEmpty())
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6">
                    @foreach($products as $product)
                        <x-cards.product :product="$product" />
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($products->hasPages())
                    <div class="flex justify-center">
                        {{ $products->links() }}
                    </div>
                @endif
            @else
                <div class="text-center text-gray-500 py-16">
                    <p class="text-body-lg">No products found in this category.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
