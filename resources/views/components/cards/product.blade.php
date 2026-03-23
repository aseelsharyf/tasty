<div class="group relative flex flex-col bg-off-white rounded-xl overflow-hidden p-1 pb-4 lg:pb-6 w-full" @if($productId) data-product-id="{{ $productId }}" @endif>
    {{-- Main card link (covers entire card including image area) --}}
    <a href="{{ $url }}" class="absolute inset-0 z-[1]" aria-label="{{ $title }}"></a>

    {{-- Image wrapper --}}
    <div class="relative mb-2 lg:mb-4">
        {{-- White background container --}}
        <div class="bg-white rounded-lg aspect-square flex items-center justify-center p-5">
            @if($image)
                @if($blurhash)
                    @php $bhId = 'bh-' . uniqid(); @endphp
                    <div class="absolute inset-0 rounded-lg overflow-hidden" data-blurhash="{{ $blurhash }}" data-blurhash-id="{{ $bhId }}">
                        <canvas
                            id="{{ $bhId }}"
                            width="32"
                            height="32"
                            class="w-full h-full"
                        ></canvas>
                    </div>
                @endif
                <img
                    src="{{ $image }}"
                    alt="{{ $imageAlt }}"
                    loading="lazy"
                    decoding="async"
                    class="relative z-[1] max-w-full max-h-full object-contain {{ $blurhash ? 'opacity-0 transition-opacity duration-300' : '' }}"
                    @if($blurhash) onload="this.classList.remove('opacity-0'); this.classList.add('opacity-100'); var p=this.closest('.bg-white').querySelector('[data-blurhash]'); if(p) p.style.display='none';" @endif
                    onerror="this.onerror=null; this.src='/images/product-placeholder.svg'; this.classList.remove('opacity-0'); this.classList.add('opacity-100'); var p=this.closest('.bg-white').querySelector('[data-blurhash]'); if(p) p.style.display='none';"
                >
            @else
                <img src="/images/product-placeholder.svg" alt="{{ $imageAlt }}" class="max-w-full max-h-full object-contain">
            @endif
        </div>
        {{-- Tag overlapping bottom edge --}}
        @if(count($tags) > 0)
            <div class="flex justify-center -mt-3 relative z-[2]">
                <span class="tag inline-block truncate max-w-[calc(100%-1rem)]">
                    @foreach($tags as $index => $tag)
                        @if($index > 0)
                            <span class="hidden lg:inline">•</span>
                        @endif
                        @if($tag['url'] ?? null)
                            <a href="{{ $tag['url'] }}" class="hover:underline relative z-10 {{ $index > 0 ? 'hidden lg:inline' : '' }} truncate">{{ $tag['name'] }}</a>
                        @else
                            <span class="{{ $index > 0 ? 'hidden lg:inline' : '' }} truncate">{{ $tag['name'] }}</span>
                        @endif
                    @endforeach
                </span>
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col items-center gap-2 lg:gap-3 px-3 lg:px-8 text-center flex-1">
        {{-- Store Logo --}}
        @if($storeLogo)
            @if($storeUrl)
                <a href="{{ $storeUrl }}" class="relative z-10">
                    <img
                        src="{{ $storeLogo }}"
                        alt="{{ $storeName ?? 'Store' }}"
                        loading="lazy"
                        decoding="async"
                        class="max-w-[80px] max-h-[32px] object-contain grayscale hover:grayscale-0 transition-all duration-300"
                    >
                </a>
            @else
                <img
                    src="{{ $storeLogo }}"
                    alt="{{ $storeName ?? 'Store' }}"
                    loading="lazy"
                    decoding="async"
                    class="max-w-[80px] max-h-[32px] object-contain grayscale group-hover:grayscale-0 transition-all duration-300"
                >
            @endif
        @endif

        <h3 class="font-display text-[24px] leading-[1.1] tracking-[-0.96px] lg:text-[30px] lg:leading-[1.1] lg:tracking-[-0.04em] text-blue-black line-clamp-2">{{ $title }}</h3>
        @if($description)
            <div class="hidden lg:block">
                <p class="text-[16px] leading-[1.4] text-blue-black line-clamp-3">{{ $description }}</p>
            </div>
        @endif
        @if($showPrice && $price)
            <div class="flex items-center gap-2 flex-wrap justify-center">
                @if($compareAtPrice)
                    <span class="text-[16px] lg:text-[18px] text-gray-500 line-through">{{ $compareAtPrice }}</span>
                @endif
                <span class="text-[16px] lg:text-[18px] font-semibold text-blue-black">{{ $price }}</span>
            </div>
        @endif

        @if($isPurchasable && $hasVariants)
            {{-- Variant add-to-cart with quantity control --}}
            <div x-data="productCartBtn({{ $productId }}, true)" @click.away="open = false" class="relative z-10 mt-auto pt-2 w-full">
                {{-- Add to Cart button --}}
                <button x-show="!inCart" @click="open = !open" type="button"
                    class="w-[90%] mx-auto lg:w-full inline-flex items-center justify-center gap-1 lg:gap-2 px-2 lg:px-5 py-2.5 lg:py-2.5 bg-blue-black text-white text-xs lg:text-sm rounded-full hover:bg-opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    <span>Add to Cart</span>
                    <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                {{-- Quantity control (shown when in cart) --}}
                <div x-show="inCart" x-cloak class="w-full flex items-center rounded-full border border-blue-black/15 bg-blue-black/[0.03] overflow-hidden">
                    <button @click="adjustQty(-1)" :disabled="updating" class="flex-none w-7 h-7 lg:w-10 lg:h-10 flex items-center justify-center text-blue-black/40 hover:text-blue-black hover:bg-blue-black/5 transition active:scale-90 disabled:opacity-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>
                    <a href="{{ route('cart.index') }}" class="flex-1 text-center text-xs lg:text-sm text-blue-black py-1 lg:py-2 tabular-nums" :class="updating ? 'opacity-50' : ''" x-text="qty + ' in cart'"></a>
                    <button @click="adjustQty(1)" :disabled="updating" class="flex-none w-7 h-7 lg:w-10 lg:h-10 flex items-center justify-center text-blue-black/40 hover:text-blue-black hover:bg-blue-black/5 transition active:scale-90 disabled:opacity-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                {{-- Variant dropdown --}}
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
                                class="w-full flex items-center justify-between px-4 py-2.5 text-left text-sm transition {{ $variant['in_stock'] ? 'hover:bg-off-white cursor-pointer' : 'opacity-50 cursor-not-allowed' }}">
                                <span class="text-blue-black">{{ $variant['name'] }}</span>
                                <span class="flex items-center gap-2">
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
        @elseif($isPurchasable)
            {{-- Simple add-to-cart with quantity control --}}
            <div x-data="productCartBtn({{ $productId }}, false)" class="relative z-10 mt-auto pt-2 w-full">
                {{-- Add to Cart button --}}
                <button x-show="!inCart" @click="addSimple()" :disabled="loading" type="button"
                    class="w-[90%] mx-auto lg:w-full inline-flex items-center justify-center gap-1 lg:gap-2 px-2 lg:px-5 py-2.5 lg:py-2.5 bg-blue-black text-white text-xs lg:text-sm rounded-full hover:bg-opacity-90 transition disabled:opacity-70">
                    <template x-if="loading">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </template>
                    <template x-if="!loading">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    </template>
                    <span x-text="loading ? 'Adding...' : 'Add to Cart'"></span>
                </button>
                {{-- Quantity control (shown when in cart) --}}
                <div x-show="inCart" x-cloak class="w-full flex items-center rounded-full border border-blue-black/15 bg-blue-black/[0.03] overflow-hidden">
                    <button @click="adjustQty(-1)" :disabled="updating" class="flex-none w-7 h-7 lg:w-10 lg:h-10 flex items-center justify-center text-blue-black/40 hover:text-blue-black hover:bg-blue-black/5 transition active:scale-90 disabled:opacity-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>
                    <a href="{{ route('cart.index') }}" class="flex-1 text-center text-xs lg:text-sm text-blue-black py-1 lg:py-2 tabular-nums" :class="updating ? 'opacity-50' : ''" x-text="qty + ' in cart'"></a>
                    <button @click="adjustQty(1)" :disabled="updating" class="flex-none w-7 h-7 lg:w-10 lg:h-10 flex items-center justify-center text-blue-black/40 hover:text-blue-black hover:bg-blue-black/5 transition active:scale-90 disabled:opacity-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                <p x-show="error" x-text="error" x-cloak class="text-xs text-red-500 mt-1 text-center"></p>
            </div>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
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
            fetch('/products/view/' + id, {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            }).catch(function() {});
            window.__productViewObserver.unobserve(entry.target);
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('[data-product-id]').forEach(function(el) {
        window.__productViewObserver.observe(el);
    });

    // Re-observe after dynamic content loads
    new MutationObserver(function() {
        document.querySelectorAll('[data-product-id]').forEach(function(el) {
            if (!tracked[el.dataset.productId]) {
                window.__productViewObserver.observe(el);
            }
        });
    }).observe(document.body, { childList: true, subtree: true });
})();

// Product card cart button component
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
                // Increment — use add endpoint
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
                // Decrement or remove
                if (newQty === 0) {
                    // Remove all items for this product
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
                    // Decrease by 1 — update first cart item
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
</script>
@endpush
@endonce
