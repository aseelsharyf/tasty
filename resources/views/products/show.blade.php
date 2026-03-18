@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-white">
    <div class="max-w-5xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Product Images -->
            <div>
                <div class="aspect-square bg-gray-50 rounded-xl flex items-center justify-center p-8 mb-4">
                    @if($product->featured_image_url)
                        <img src="{{ $product->featured_image_url }}" alt="{{ $product->title }}" class="max-w-full max-h-full object-contain">
                    @else
                        <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    @endif
                </div>
                @if($product->images->isNotEmpty())
                    <div class="flex gap-3 overflow-x-auto">
                        @foreach($product->images as $image)
                            <div class="w-20 h-20 shrink-0 bg-gray-50 rounded-lg overflow-hidden">
                                <img src="{{ $image->thumbnail_url ?? $image->url }}" alt="" class="w-full h-full object-contain p-1">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Info -->
            <div>
                @if($product->category)
                    <p class="text-sm text-gray-500 mb-2">{{ $product->category->name }}</p>
                @endif

                <h1 class="text-h2 text-blue-black mb-4">{{ $product->title }}</h1>

                @if($product->price)
                    <div class="flex items-center gap-3 mb-6">
                        <span class="text-2xl font-bold text-blue-black">{{ number_format($product->price, 2) }} {{ $product->currency }}</span>
                        @if($product->hasDiscount())
                            <span class="text-lg text-gray-400 line-through">{{ number_format($product->compare_at_price, 2) }}</span>
                        @endif
                    </div>
                @endif

                @if($product->short_description)
                    <p class="text-gray-600 mb-6">{{ $product->short_description }}</p>
                @endif

                @if($product->isPurchasable())
                    <div x-data="{ selectedVariant: null, quantity: 1, loading: false, added: false, error: '' }" class="space-y-4 mb-8">
                        @if($product->variants->isNotEmpty())
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Option</label>
                                <div class="space-y-2">
                                    @foreach($product->variants as $variant)
                                        <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:border-blue-400 transition"
                                            :class="selectedVariant === {{ $variant->id }} ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                            <div class="flex items-center gap-3">
                                                <input type="radio" name="variant_id" value="{{ $variant->id }}"
                                                    x-model.number="selectedVariant" class="text-blue-600" required>
                                                <span class="font-medium">{{ $variant->name }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-semibold">{{ number_format($variant->price, 2) }} {{ $product->currency }}</span>
                                                @if(!$variant->isInStock())
                                                    <span class="block text-xs text-red-500">Out of stock</span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <form @submit.prevent="
                            loading = true; error = '';
                            let body = { product_id: {{ $product->id }}, quantity: quantity };
                            if (selectedVariant) body.variant_id = selectedVariant;
                            fetch('{{ route('cart.add') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-XSRF-TOKEN': window.getCsrfToken() },
                                body: JSON.stringify(body)
                            })
                            .then(r => r.json())
                            .then(data => {
                                loading = false;
                                if (data.success) { added = true; if (window.updateCartBadge) window.updateCartBadge(data.cartCount); setTimeout(() => added = false, 2000); }
                                else { error = data.message || 'Could not add to cart'; setTimeout(() => error = '', 3000); }
                            })
                            .catch(() => { loading = false; error = 'Something went wrong'; setTimeout(() => error = '', 3000); })
                        ">
                            <div class="flex items-center gap-3">
                                <input type="number" x-model.number="quantity" min="1" max="99"
                                    class="w-20 text-center border border-gray-300 rounded-lg py-2">
                                <button type="submit" :disabled="loading" class="flex-1 py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition inline-flex items-center justify-center gap-2 disabled:opacity-70">
                                    <template x-if="loading">
                                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </template>
                                    <template x-if="added">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <span x-text="loading ? 'Adding...' : (added ? 'Added!' : 'Add to Cart')"></span>
                                </button>
                            </div>
                            <p x-show="error" x-text="error" x-cloak class="text-sm text-red-500 mt-2"></p>
                        </form>
                    </div>
                @elseif($product->isReferral() && $product->affiliate_url)
                    <a href="{{ route('products.redirect', $product) }}" target="_blank"
                        class="inline-flex items-center justify-center w-full py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition mb-8">
                        Visit Store
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @endif

                @if($product->description)
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="font-semibold text-blue-black mb-3">Description</h3>
                        <div class="text-gray-600 text-sm leading-relaxed">{{ $product->description }}</div>
                    </div>
                @endif

                @if($product->brand)
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <p class="text-sm"><span class="text-gray-500">Brand:</span> {{ $product->brand }}</p>
                    </div>
                @endif

                @if($product->sku)
                    <p class="text-sm mt-2"><span class="text-gray-500">SKU:</span> {{ $product->sku }}</p>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
