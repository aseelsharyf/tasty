@extends('layouts.app')

@section('content')
    <div class="h-[96px] md:h-[112px]"></div>

    <section class="w-full">
        <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 max-lg:px-5 max-lg:pt-10 max-lg:pb-16 flex flex-col gap-16 max-lg:gap-10">
            {{-- Header --}}
            <div class="flex flex-col gap-5 items-center text-center text-blue-black w-full max-w-[660px] mx-auto">
                <h1 class="text-h1 uppercase">{{ $currentCategory->name }}</h1>
                @if($currentCategory->description)
                    <p class="text-body-md">{{ $currentCategory->description }}</p>
                @endif

                {{-- Search --}}
                <form action="{{ route('products.category', $currentCategory) }}" method="GET" class="w-full max-w-sm mt-2">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search ?? '' }}"
                            placeholder="Search in {{ $currentCategory->name }}..."
                            class="w-full pl-10 pr-9 py-2.5 bg-white border border-gray-200 rounded-full text-sm text-blue-black placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition"
                        />
                        @if(!empty($search))
                            <a href="{{ route('products.category', $currentCategory) }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </a>
                        @endif
                    </div>
                </form>
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
                    <div class="pointer-events-none absolute left-0 top-0 bottom-0 w-12 bg-gradient-to-r from-off-white to-transparent z-10 lg:hidden transition-opacity duration-150" :class="atStart ? 'opacity-0' : 'opacity-100'"></div>
                    {{-- Right fade (mobile only) --}}
                    <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-12 bg-gradient-to-l from-off-white to-transparent z-10 lg:hidden transition-opacity duration-150" :class="atEnd ? 'opacity-0' : 'opacity-100'"></div>
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
                    <p class="text-body-lg">{{ !empty($search) ? "No products found for \"{$search}\"." : 'No products found in this category.' }}</p>
                </div>
            @endif
        </div>
    </section>
@endsection
