<div class="group relative flex flex-col bg-off-white rounded-xl overflow-hidden p-1 pb-6 w-[426px] max-lg:w-full" @if($productId) data-product-id="{{ $productId }}" @endif>
    {{-- Main card link (covers entire card including image area) --}}
    <a href="{{ $url }}" class="absolute inset-0 z-[1]" aria-label="{{ $title }}"></a>

    {{-- Image container --}}
    <div class="relative aspect-[4/4] max-lg:aspect-[4/4] bg-white rounded-lg flex items-end justify-center p-6 mb-4">
        <img
            src="{{ $image }}"
            alt="{{ $imageAlt }}"
            loading="lazy"
            decoding="async"
            class="absolute inset-0 w-full h-full object-contain p-5"
        >
        @if(count($tags) > 0)
            <span class="tag relative z-[2] inline-flex items-center gap-1 whitespace-nowrap">
                @foreach($tags as $index => $tag)
                    @if($index > 0)
                        <span>•</span>
                    @endif
                    @if($tag['url'] ?? null)
                        <a href="{{ $tag['url'] }}" class="hover:underline relative z-10">{{ $tag['name'] }}</a>
                    @else
                        <span>{{ $tag['name'] }}</span>
                    @endif
                @endforeach
            </span>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex flex-col items-center gap-3 px-8 max-lg:px-6 text-center">
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

        <h3 class="text-h4 text-blue-black line-clamp-2">{{ $title }}</h3>
        @if($description)
            <p class="text-body-md text-blue-black line-clamp-3">{{ $description }}</p>
        @endif
        @if($showPrice && $price)
            <div class="flex items-center gap-2">
                @if($compareAtPrice)
                    <span class="text-body-sm text-gray-500 line-through">{{ $compareAtPrice }}</span>
                @endif
                <span class="text-body-md font-semibold text-blue-black">{{ $price }}</span>
            </div>
        @endif

        @if($isPurchasable && $hasVariants)
            <div x-data="{ open: false, loading: false, added: false, error: '', selectedId: null }" @click.away="open = false" class="relative z-10 mt-2 w-full max-w-[260px]">
                {{-- Toggle button --}}
                <button @click="open = !open" type="button"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-2 bg-blue-black text-white text-body-sm font-medium rounded-full hover:bg-opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    <span x-text="added ? 'Added!' : 'Add to Cart'"></span>
                    <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                {{-- Dropdown --}}
                <div x-show="open" x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-20">
                    <div class="py-1">
                        @foreach($variants as $variant)
                            <button
                                type="button"
                                @click="
                                    if ({{ $variant['in_stock'] ? 'false' : 'true' }}) return;
                                    loading = true; error = ''; selectedId = {{ $variant['id'] }};
                                    fetch('{{ route('cart.add') }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                        body: JSON.stringify({ product_id: {{ $productId }}, variant_id: {{ $variant['id'] }} })
                                    })
                                    .then(r => r.json())
                                    .then(data => {
                                        loading = false; selectedId = null;
                                        if (data.success) { added = true; open = false; if (window.updateCartBadge) window.updateCartBadge(data.cartCount); setTimeout(() => added = false, 2000); }
                                        else { error = data.message || 'Could not add'; setTimeout(() => error = '', 3000); }
                                    })
                                    .catch(() => { loading = false; selectedId = null; error = 'Something went wrong'; setTimeout(() => error = '', 3000); })
                                "
                                class="w-full flex items-center justify-between px-4 py-2.5 text-left text-sm transition {{ $variant['in_stock'] ? 'hover:bg-off-white cursor-pointer' : 'opacity-50 cursor-not-allowed' }}"
                            >
                                <span class="font-medium text-blue-black">{{ $variant['name'] }}</span>
                                <span class="flex items-center gap-2">
                                    @if($variant['in_stock'])
                                        <span class="text-gray-500">{{ $variant['price'] }}</span>
                                        <template x-if="loading && selectedId === {{ $variant['id'] }}">
                                            <svg class="w-4 h-4 animate-spin text-blue-black" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        </template>
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
            <div x-data="{ loading: false, added: false, error: '' }" class="relative z-10 mt-2">
                <form @submit.prevent="
                    loading = true; error = '';
                    fetch('{{ route('cart.add') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ product_id: {{ $productId }} })
                    })
                    .then(r => r.json())
                    .then(data => {
                        loading = false;
                        if (data.success) { added = true; if (window.updateCartBadge) window.updateCartBadge(data.cartCount); setTimeout(() => added = false, 2000); }
                        else { error = data.message || 'Could not add to cart'; setTimeout(() => error = '', 3000); }
                    })
                    .catch(() => { loading = false; error = 'Something went wrong'; setTimeout(() => error = '', 3000); })
                ">
                    <button type="submit" :disabled="loading" class="inline-flex items-center gap-2 px-5 py-2 bg-blue-black text-white text-body-sm font-medium rounded-full hover:bg-opacity-90 transition disabled:opacity-70">
                        <template x-if="loading">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </template>
                        <template x-if="added">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="!loading && !added">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        </template>
                        <span x-text="loading ? 'Adding...' : (added ? 'Added!' : 'Add to Cart')"></span>
                    </button>
                </form>
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
    var token = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = token ? token.getAttribute('content') : '';

    window.__productViewObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            var id = entry.target.dataset.productId;
            if (!id || tracked[id]) return;
            tracked[id] = true;
            try { sessionStorage.setItem('_pv', JSON.stringify(tracked)); } catch(e) {}
            fetch('/products/view/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
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
</script>
@endpush
@endonce
