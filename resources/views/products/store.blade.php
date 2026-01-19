@extends('layouts.app')

@section('content')
    <div class="h-[96px] md:h-[112px]"></div>

    {{-- Page Header --}}
    <section class="w-full bg-white">
        <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-12 max-lg:px-5 max-lg:pt-10 max-lg:pb-8">
            <div class="flex flex-col gap-5 items-center text-center text-blue-black w-full max-w-[660px] mx-auto">
                @if($store->logo_url)
                    <img
                        src="{{ $store->logo_url }}"
                        alt="{{ $store->name }}"
                        class="h-16 w-auto object-contain mb-2"
                    >
                @endif
                <h1 class="text-h1 uppercase">{{ $store->name }}</h1>
                @if($store->address)
                    <p class="text-body-md">{{ $store->location_label ?? $store->address }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Products Section --}}
    <section class="w-full bg-white">
        <div class="w-full max-w-[1440px] mx-auto px-10 pb-32 max-lg:px-5 max-lg:pb-16 flex flex-col gap-16 max-lg:gap-10">
            {{-- Products Grid --}}
            @if($products->isNotEmpty())
                <div class="flex flex-wrap justify-center gap-10 max-lg:flex-col max-lg:items-center max-lg:gap-6">
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
                    <p class="text-body-lg">No products found.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
