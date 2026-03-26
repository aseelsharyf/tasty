@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1">
    <div class="max-w-5xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Product Images -->
            <div x-data="{
                activeIndex: 0,
                items: [
                    @if($product->featuredMedia)
                        {
                            type: '{{ $product->featuredMedia->is_video ? 'video' : 'image' }}',
                            url: '{{ $product->featuredMedia->url }}',
                            thumbnail: '{{ $product->featuredMedia->thumbnail_url ?? $product->featuredMedia->url }}',
                            embedUrl: '{{ $product->featuredMedia->embed_url ?? '' }}',
                            videoType: '{{ $product->featuredMedia->type ?? '' }}'
                        },
                    @endif
                    @foreach($product->images as $image)
                        @if(!$product->featuredMedia || $image->id !== $product->featuredMedia->id)
                            {
                                type: '{{ $image->is_video ? 'video' : 'image' }}',
                                url: '{{ $image->url }}',
                                thumbnail: '{{ $image->thumbnail_url ?? $image->url }}',
                                embedUrl: '{{ $image->embed_url ?? '' }}',
                                videoType: '{{ $image->type ?? '' }}'
                            },
                        @endif
                    @endforeach
                ]
            }">
                <!-- Main Display -->
                <div class="aspect-square bg-white rounded-xl flex items-center justify-center p-8 mb-4 overflow-hidden border border-gray-100 relative">
                    <template x-if="items.length === 0">
                        <img src="/images/product-placeholder.svg" alt="{{ $product->title }}" class="w-full h-full object-cover rounded-xl">
                    </template>
                    <template x-if="items.length > 0 && items[activeIndex].type === 'image'">
                        <img :src="items[activeIndex].url" alt="{{ $product->title }}" class="max-w-full max-h-full object-contain">
                    </template>
                    <template x-if="items.length > 0 && items[activeIndex].type === 'video' && items[activeIndex].videoType === 'video_embed'">
                        <iframe :src="items[activeIndex].embedUrl" class="w-full h-full rounded-lg" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </template>
                    <template x-if="items.length > 0 && items[activeIndex].type === 'video' && items[activeIndex].videoType === 'video_local'">
                        <video :src="items[activeIndex].url" class="max-w-full max-h-full object-contain" controls playsinline></video>
                    </template>
                </div>

                <!-- Thumbnails -->
                <template x-if="items.length > 1">
                    <div class="flex gap-3 overflow-x-auto">
                        <template x-for="(item, index) in items" :key="index">
                            <button @click="activeIndex = index" class="w-20 h-20 shrink-0 bg-gray-50 rounded-lg overflow-hidden transition relative" :class="activeIndex === index ? 'border-2 border-gray-200' : 'border border-transparent hover:border-gray-100'">
                                <img :src="item.thumbnail" alt="" class="w-full h-full object-contain p-1">
                                <template x-if="item.type === 'video'">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 rounded-lg">
                                        <svg class="w-5 h-5 text-white drop-shadow" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </template>
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Product Info -->
            <div>
                @if($product->category)
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-3">{{ $product->category->name }}</p>
                @endif

                <h1 class="font-display text-[32px] leading-[1.1] tracking-[-0.02em] text-blue-black mb-4">{{ $product->title }}</h1>

                @if($product->price)
                    <div class="flex items-baseline gap-3 mb-6">
                        <span class="font-display text-[22px] tracking-[-0.02em] text-blue-black">{{ number_format($product->price, 2) }} {{ $product->currency }}</span>
                        @if($product->hasDiscount())
                            <span class="text-sm text-gray-400 line-through">{{ number_format($product->compare_at_price, 2) }}</span>
                        @endif
                    </div>
                @endif

                @if($product->short_description)
                    <p class="text-sm text-gray-500 leading-relaxed mb-6">{{ $product->short_description }}</p>
                @endif

                @if($product->isPurchasable())
                    <div x-data="{
                        selectedVariant: null,
                        quantity: 1,
                        loading: false,
                        added: false,
                        error: '',
                        inCart: !!(window._cartMap && window._cartMap[{{ $product->id }}]),
                        cartQty: (window._cartMap && window._cartMap[{{ $product->id }}]) ? window._cartMap[{{ $product->id }}].qty : 0,
                        init() {
                            window.addEventListener('cart-updated', () => {
                                const entry = window._cartMap && window._cartMap[{{ $product->id }}];
                                this.inCart = !!entry;
                                this.cartQty = entry ? entry.qty : 0;
                            });
                        }
                    }" class="space-y-4 mb-8">
                        @if($product->variants->isNotEmpty())
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-2">Select Option</label>
                                <div class="space-y-2">
                                    @foreach($product->variants as $variant)
                                        <label class="group/v flex items-center justify-between p-3 border border-gray-100 rounded-xl cursor-pointer hover:bg-gray-50/50 transition has-[:checked]:bg-gray-50 has-[:checked]:border-gray-200"
                                            @click="selectedVariant = {{ $variant->id }}">
                                            <div class="flex items-center gap-3">
                                                <input type="radio" name="variant_id" value="{{ $variant->id }}"
                                                    x-model.number="selectedVariant" class="sr-only" required>
                                                <div class="w-4 h-4 rounded-full border border-gray-200 shrink-0 group-has-[:checked]/v:hidden"></div>
                                                <div class="w-4 h-4 rounded-full bg-blue-black shrink-0 items-center justify-center hidden group-has-[:checked]/v:flex">
                                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                                <span class="text-sm text-blue-black">{{ $variant->name }}</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-sm text-blue-black">{{ number_format($variant->price, 2) }} {{ $product->currency }}</span>
                                                @if(!$variant->isInStock())
                                                    <span class="block text-xs text-red-400">Out of stock</span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div x-show="inCart && !added" x-cloak class="flex items-center gap-2 px-3 py-2 bg-green-50 text-green-700 rounded-xl text-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Already in your cart (<span x-text="cartQty"></span> item<span x-show="cartQty > 1">s</span>)</span>
                            <a href="{{ route('cart.index') }}" class="ml-auto text-green-800 underline underline-offset-2 hover:no-underline">View Cart</a>
                        </div>

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
                                if (data.success) { added = true; if (window.updateCartBadge) window.updateCartBadge(data.cartCount); if (window.showCartToast) window.showCartToast({{ json_encode($product->title) }}, {{ json_encode($product->featured_image_url) }}); if (window.refreshCartMap) window.refreshCartMap(); setTimeout(() => added = false, 2000); }
                                else { error = data.message || 'Could not add to cart'; setTimeout(() => error = '', 3000); }
                            })
                            .catch(() => { loading = false; error = 'Something went wrong'; setTimeout(() => error = '', 3000); })
                        ">
                            <div class="flex items-center gap-3">
                                <input type="number" x-model.number="quantity" min="1" max="99"
                                    class="w-20 text-center border border-gray-200 rounded-xl py-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition">
                                <button type="submit" :disabled="loading" class="flex-1 py-3 bg-blue-black text-white rounded-full text-sm hover:bg-opacity-90 transition inline-flex items-center justify-center gap-2 disabled:opacity-70">
                                    <template x-if="loading">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </template>
                                    <template x-if="added">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <span x-text="loading ? 'Adding...' : (added ? 'Added!' : (inCart ? 'Add More' : 'Add to Cart'))"></span>
                                </button>
                            </div>
                            <p x-show="error" x-text="error" x-cloak class="text-xs text-red-500 mt-2"></p>
                        </form>
                    </div>
                @elseif($product->isReferral() && $product->affiliate_url)
                    <a href="{{ route('products.redirect', $product) }}" target="_blank"
                        class="inline-flex items-center justify-center w-full py-3 bg-blue-black text-white rounded-full text-sm hover:bg-opacity-90 transition mb-8">
                        Visit Store
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @endif

                @if($product->description)
                    <div class="border-t border-gray-100 pt-6">
                        <h3 class="font-display text-[18px] tracking-[-0.02em] text-blue-black mb-3">Description</h3>
                        <div class="text-sm text-gray-500 leading-relaxed">{{ $product->description }}</div>
                    </div>
                @endif

                @if($product->brand || $product->sku)
                    <div class="border-t border-gray-100 pt-4 mt-4 space-y-1">
                        @if($product->brand)
                            <p class="text-sm text-gray-400"><span class="text-gray-500">Brand:</span> {{ $product->brand }}</p>
                        @endif
                        @if($product->sku)
                            <p class="text-sm text-gray-400"><span class="text-gray-500">SKU:</span> {{ $product->sku }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @if($relatedProducts->isNotEmpty())
        <div class="max-w-[1440px] mx-auto px-6 pb-16">
            <h2 class="font-display text-[24px] leading-[1.1] tracking-[-0.02em] text-blue-black mb-6">You may also like</h2>
            <div class="flex items-stretch gap-5 overflow-x-auto pb-4 -mx-6 px-6 snap-x snap-mandatory scrollbar-hide">
                @foreach($relatedProducts as $related)
                    <div class="w-[280px] shrink-0 snap-start flex">
                        <x-cards.product :product="$related" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</main>
@endsection

@push('scripts')
<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush
