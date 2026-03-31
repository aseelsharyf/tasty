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
        <a href="{{ route('products.index') }}" class="block text-h2 text-blue-black text-center hover:underline">{{ $title }}</a>
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
            @php
                $hasVariants = $product->variants->isNotEmpty();
                $variants = $hasVariants ? $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'price' => number_format((float) $v->price, 2) . ' ' . $product->currency,
                    'in_stock' => $v->isInStock(),
                ])->all() : [];
            @endphp
            <div
                class="group flex flex-col"
                style="flex: 0 0 calc((100vw - 56px) / 2.5); max-width: 220px;"
                @if($product->id) data-product-id="{{ $product->id }}" @endif
            >
                <a href="{{ $product->url }}">
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

                {{-- Add to Cart --}}
                @if($product->isPurchasable() && $hasVariants)
                    <div x-data="productCartBtn({{ $product->id }}, true)" @click.away="open = false" class="relative mt-2 flex flex-col items-center">
                        <button x-show="!inCart" @click="open = !open" type="button"
                            class="w-8 h-8 inline-flex items-center justify-center bg-blue-black text-white rounded-full hover:bg-opacity-90 transition" title="Add to Cart">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        </button>
                        <div x-show="inCart" x-cloak class="flex items-center rounded-full border border-blue-black/15 bg-blue-black/[0.03] overflow-hidden">
                            <button @click="adjustQty(-1)" :disabled="updating" class="flex-none w-6 h-6 flex items-center justify-center text-blue-black/40 hover:text-blue-black hover:bg-blue-black/5 transition active:scale-90 disabled:opacity-50">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            </button>
                            <a href="{{ route('cart.index') }}" class="px-2 text-center text-[10px] text-blue-black py-0.5 tabular-nums" :class="updating ? 'opacity-50' : ''" x-text="qty + ' in cart'"></a>
                            <button @click="adjustQty(1)" :disabled="updating" class="flex-none w-6 h-6 flex items-center justify-center text-blue-black/40 hover:text-blue-black hover:bg-blue-black/5 transition active:scale-90 disabled:opacity-50">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-20">
                            <div class="py-1">
                                @foreach($variants as $variant)
                                    <button type="button"
                                        @click="addVariant({{ $variant['id'] }}, {{ $variant['in_stock'] ? 'true' : 'false' }})"
                                        class="w-full flex items-center justify-between px-3 py-2 text-left text-xs transition {{ $variant['in_stock'] ? 'hover:bg-off-white cursor-pointer' : 'opacity-50 cursor-not-allowed' }}">
                                        <span class="text-blue-black">{{ $variant['name'] }}</span>
                                        <span>
                                            @if($variant['in_stock'])
                                                <span class="text-gray-500">{{ $variant['price'] }}</span>
                                            @else
                                                <span class="text-xs text-red-400">Out of stock</span>
                                            @endif
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <p x-show="error" x-text="error" x-cloak class="text-xs text-red-500 mt-1 text-center"></p>
                    </div>
                @elseif($product->isPurchasable())
                    <div x-data="productCartBtn({{ $product->id }}, false)" class="relative mt-2 flex flex-col items-center">
                        <button x-show="!inCart" @click="addSimple()" :disabled="loading" type="button"
                            class="w-8 h-8 inline-flex items-center justify-center bg-blue-black text-white rounded-full hover:bg-opacity-90 transition disabled:opacity-70" title="Add to Cart">
                            <template x-if="loading">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            <template x-if="!loading">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            </template>
                        </button>
                        <div x-show="inCart" x-cloak class="flex items-center rounded-full border border-blue-black/15 bg-blue-black/[0.03] overflow-hidden">
                            <button @click="adjustQty(-1)" :disabled="updating" class="flex-none w-6 h-6 flex items-center justify-center text-blue-black/40 hover:text-blue-black hover:bg-blue-black/5 transition active:scale-90 disabled:opacity-50">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            </button>
                            <a href="{{ route('cart.index') }}" class="px-2 text-center text-[10px] text-blue-black py-0.5 tabular-nums" :class="updating ? 'opacity-50' : ''" x-text="qty + ' in cart'"></a>
                            <button @click="adjustQty(1)" :disabled="updating" class="flex-none w-6 h-6 flex items-center justify-center text-blue-black/40 hover:text-blue-black hover:bg-blue-black/5 transition active:scale-90 disabled:opacity-50">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                        <p x-show="error" x-text="error" x-cloak class="text-xs text-red-500 mt-1 text-center"></p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</section>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
</style>

@push('scripts')
<script>
if (!window.productCartBtn) {
    (function() {
        if (window.__productViewObserver) return;
        var tracked = {};
        try { var s = sessionStorage.getItem('_pv'); if (s) tracked = JSON.parse(s); } catch(e) {}
        var csrfToken = window.getCsrfToken ? window.getCsrfToken() : '';

        window.__productViewObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (!entry.isIntersecting) return;
                var id = entry.target.dataset.productId;
                if (!id || tracked[id]) return;
                tracked[id] = true;
                try { sessionStorage.setItem('_pv', JSON.stringify(tracked)); } catch(e) {}
                fetch('/store/view/' + id, {
                    method: 'POST',
                    headers: { 'X-XSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                }).catch(function() {});
                window.__productViewObserver.unobserve(entry.target);
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('[data-product-id]').forEach(function(el) {
            window.__productViewObserver.observe(el);
        });

        new MutationObserver(function() {
            document.querySelectorAll('[data-product-id]').forEach(function(el) {
                if (!tracked[el.dataset.productId]) {
                    window.__productViewObserver.observe(el);
                }
            });
        }).observe(document.body, { childList: true, subtree: true });
    })();

    window.productCartBtn = function(productId, hasVariants) {
        var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-XSRF-TOKEN': window.getCsrfToken() };
        return {
            productId: productId,
            open: false,
            loading: false,
            updating: false,
            error: '',
            inCart: false,
            qty: 0,
            cartItemIds: [],

            init() {
                var self = this;
                window.addEventListener('cart-updated', function() { self.syncFromCart(); });
                setTimeout(function() { self.syncFromCart(); }, 300);
            },

            syncFromCart() {
                var map = window._cartMap || {};
                var entry = map[this.productId];
                if (entry && entry.qty > 0) {
                    this.inCart = true;
                    this.qty = entry.qty;
                    this.cartItemIds = entry.ids || [];
                } else {
                    this.inCart = false;
                    this.qty = 0;
                    this.cartItemIds = [];
                }
            },

            getTitle() {
                var card = this.$el.closest('[data-product-id]');
                if (card) {
                    var h3 = card.querySelector('h3');
                    return h3 ? h3.textContent.trim() : 'Item';
                }
                return 'Item';
            },

            getImage() {
                var card = this.$el.closest('[data-product-id]');
                if (card) {
                    var img = card.querySelector('img[src]:not([src*="placeholder"])');
                    return img ? img.src : '';
                }
                return '';
            },

            addSimple() {
                var self = this;
                self.loading = true; self.error = '';
                fetch('{{ route("cart.add") }}', {
                    method: 'POST', headers: headers,
                    body: JSON.stringify({ product_id: self.productId })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    self.loading = false;
                    if (data.success) {
                        self.qty = self.qty + 1; self.inCart = true;
                        if (window.updateCartBadge) window.updateCartBadge(data.cartCount);
                        if (window.showCartToast) window.showCartToast(self.getTitle(), self.getImage());
                        window.refreshCartMap();
                    } else {
                        self.error = data.message || 'Could not add';
                        setTimeout(function() { self.error = ''; }, 3000);
                    }
                })
                .catch(function() { self.loading = false; self.error = 'Something went wrong'; setTimeout(function() { self.error = ''; }, 3000); });
            },

            addVariant(variantId, inStock) {
                if (!inStock) return;
                var self = this;
                self.loading = true; self.error = '';
                fetch('{{ route("cart.add") }}', {
                    method: 'POST', headers: headers,
                    body: JSON.stringify({ product_id: self.productId, variant_id: variantId })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    self.loading = false;
                    if (data.success) {
                        self.open = false; self.qty = self.qty + 1; self.inCart = true;
                        if (window.updateCartBadge) window.updateCartBadge(data.cartCount);
                        if (window.showCartToast) window.showCartToast(self.getTitle(), self.getImage());
                        window.refreshCartMap();
                    } else {
                        self.error = data.message || 'Could not add';
                        setTimeout(function() { self.error = ''; }, 3000);
                    }
                })
                .catch(function() { self.loading = false; self.error = 'Something went wrong'; setTimeout(function() { self.error = ''; }, 3000); });
            },

            adjustQty(delta) {
                if (this.updating) return;
                var newQty = this.qty + delta;
                if (newQty < 0 || newQty > 99) return;

                var self = this;

                if (delta > 0) {
                    self.updating = true;
                    self.qty = newQty;
                    fetch('{{ route("cart.add") }}', {
                        method: 'POST', headers: headers,
                        body: JSON.stringify({ product_id: self.productId })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        self.updating = false;
                        if (data.success) {
                            if (window.updateCartBadge) window.updateCartBadge(data.cartCount);
                            window.refreshCartMap();
                        } else { self.qty = newQty - 1; }
                    })
                    .catch(function() { self.updating = false; self.qty = newQty - 1; });
                } else {
                    if (newQty === 0) {
                        self.updating = true;
                        var removePromises = self.cartItemIds.map(function(cid) {
                            return fetch('{{ route("cart.remove") }}', {
                                method: 'DELETE', headers: headers,
                                body: JSON.stringify({ cart_item_id: cid })
                            });
                        });
                        Promise.all(removePromises).then(function() {
                            self.updating = false;
                            self.inCart = false; self.qty = 0; self.cartItemIds = [];
                            window.refreshCartMap();
                        }).catch(function() { self.updating = false; });
                    } else {
                        var firstId = self.cartItemIds[0];
                        if (!firstId) return;
                        self.updating = true;
                        self.qty = newQty;
                        fetch('{{ route("cart.update") }}', {
                            method: 'PUT', headers: headers,
                            body: JSON.stringify({ cart_item_id: firstId, quantity: newQty })
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            self.updating = false;
                            if (data.success) {
                                if (window.updateCartBadge) window.updateCartBadge(data.cartCount);
                                window.refreshCartMap();
                            } else { self.qty = newQty + 1; }
                        })
                        .catch(function() { self.updating = false; self.qty = newQty + 1; });
                    }
                }
            }
        };
    };
}
</script>
@endpush
@endif
