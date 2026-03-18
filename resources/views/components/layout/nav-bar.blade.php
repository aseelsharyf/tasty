<div x-data="searchNav()">
    <nav
        :class="navVisible ? 'translate-y-0' : '-translate-y-full'"
        class="fixed top-0 left-0 right-0 z-50 flex justify-center pt-6 md:pt-[40px] px-4 md:px-10 pointer-events-none transition-all duration-300">
        <div class="pointer-events-auto w-full max-w-[1360px] h-[72px] bg-white/70 backdrop-blur-md rounded-xl border border-white/20 shadow-lg relative transition-all duration-300 flex items-center justify-between">

            <!-- LEFT: Logo + Divider -->
            <div class="flex items-center h-full flex-shrink-0">
                <a href="/" class="h-full px-6 md:px-8 flex items-center justify-center hover:opacity-80 transition-opacity">
                    <x-layout.logo />
                </a>
                <div class="hidden lg:block h-[40px] w-[1px] bg-stone-300/50"></div>
            </div>

            <!-- MIDDLE: Primary links (hidden on mobile) -->
            <div class="hidden lg:flex flex-1 items-center justify-between h-full px-8">
                <div class="flex items-center gap-6 xl:gap-8">
                    @foreach($primaryLinks as $link)
                        @php
                            $label = $link['label'] ?? '';
                            $href  = $link['href'] ?? '#';
                            $extra = $link['class'] ?? '';
                        @endphp
                        <a href="{{ $href }}" class="font-mono text-base hover:text-yellow-600 transition uppercase tracking-wide {{ $extra }}">
                            {{ strtoupper($label) }}
                        </a>
                    @endforeach
                </div>

                <div class="flex items-center gap-6 xl:gap-8">
                    @foreach($secondaryLinks as $link)
                        @php
                            $label = $link['label'] ?? '';
                            $href  = $link['href'] ?? '#';
                            $extra = $link['class'] ?? '';
                        @endphp
                        <a href="{{ $href }}" class="font-mono text-base hover:text-yellow-600 transition uppercase tracking-wide {{ $extra }}">
                            {{ strtoupper($label) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- RIGHT: Divider + Search/Menu -->
            <div class="flex items-center h-full flex-shrink-0">
                <div class="hidden lg:block h-[40px] w-[1px] bg-stone-300/50"></div>

                <div class="hidden lg:flex h-full items-center relative" id="cart-icon-desktop"
                    x-data="miniCart()" @click.away="open = false">
                    <button @click="toggle()" class="h-full px-4 flex items-center justify-center hover:opacity-70 transition relative">
                        <svg class="w-5 h-5 text-blue-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        <span id="cart-badge-desktop" class="absolute top-4 right-2 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full items-center justify-center hidden">0</span>
                    </button>

                    {{-- Mini Cart Dropdown --}}
                    <div x-show="open" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="absolute top-[calc(100%+8px)] right-0 w-[360px] bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-50">

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                            <h3 class="font-semibold text-blue-black text-sm">Cart</h3>
                            <span class="text-xs text-gray-400" x-show="items.length > 0" x-text="itemCount + (itemCount === 1 ? ' item' : ' items')"></span>
                        </div>

                        {{-- Loading --}}
                        <div x-show="loading" class="py-10 flex justify-center">
                            <div class="w-5 h-5 border-2 border-blue-black/20 border-t-blue-black rounded-full animate-spin"></div>
                        </div>

                        {{-- Empty State --}}
                        <div x-show="!loading && items.length === 0" class="py-10 text-center px-5">
                            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            <p class="text-sm text-gray-400">Your cart is empty</p>
                        </div>

                        {{-- Items --}}
                        <div x-show="!loading && items.length > 0" class="max-h-[300px] overflow-y-auto">
                            <template x-for="item in items" :key="item.id">
                                <div class="flex gap-3 px-5 py-3 border-b border-gray-50">
                                    <div class="w-12 h-12 bg-gray-50 rounded-lg overflow-hidden shrink-0 flex items-center justify-center">
                                        <template x-if="item.product.featured_image_url">
                                            <img :src="item.product.featured_image_url" :alt="item.product.title" class="w-full h-full object-contain p-1">
                                        </template>
                                        <template x-if="!item.product.featured_image_url">
                                            <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-blue-black truncate" x-text="item.product.title"></p>
                                        <template x-if="item.variant">
                                            <p class="text-xs text-gray-400" x-text="item.variant.name"></p>
                                        </template>
                                        <div class="flex items-center justify-between mt-1">
                                            <span class="text-xs text-gray-400" x-text="'Qty: ' + item.quantity"></span>
                                            <span class="text-sm font-medium text-blue-black" x-text="parseFloat(item.total).toFixed(2)"></span>
                                        </div>
                                    </div>
                                    <button @click="removeItem(item.id)" class="self-start text-gray-300 hover:text-red-400 transition shrink-0 mt-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Footer --}}
                        <div x-show="!loading && items.length > 0" class="px-5 py-4 bg-gray-50/50 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-gray-500">Total</span>
                                <span class="text-base font-bold text-blue-black" x-text="parseFloat(total).toFixed(2) + ' MVR'"></span>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('cart.index') }}" class="flex-1 py-2 text-center text-sm font-medium text-blue-black border border-gray-200 rounded-full hover:bg-gray-100 transition">
                                    View Cart
                                </a>
                                <a href="{{ route('checkout.index') }}" class="flex-1 py-2 text-center text-sm font-medium text-white bg-blue-black rounded-full hover:bg-opacity-90 transition">
                                    Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div @click="openSearch()" class="hidden lg:flex h-full px-6 md:px-8 items-center justify-center cursor-pointer hover:opacity-70 transition gap-3">
                    <span class="font-mono text-base text-blue-black uppercase tracking-wide">Search</span>
                    <svg class="w-5 h-5 text-blue-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>

                <div class="lg:hidden flex items-center h-full px-4 gap-4">
                    <button @click="openSearch()" class="text-blue-black focus:outline-none" aria-label="Search">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </button>
                    <a href="{{ route('cart.index') }}" class="relative text-blue-black focus:outline-none" aria-label="Cart">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        <span id="cart-badge-mobile" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full items-center justify-center hidden">0</span>
                    </a>
                    <button @click="menuOpen = !menuOpen" class="flex items-center gap-3 text-blue-black focus:outline-none">
                        <span class="font-mono text-base tracking-widest uppercase">Menu</span>
                        <svg x-show="!menuOpen" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <svg x-show="menuOpen" x-cloak class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- MOBILE DROPDOWN -->
            <div
                x-show="menuOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="absolute top-[80px] left-0 w-full bg-tasty-off-white rounded-xl shadow-xl p-6 lg:hidden flex flex-col gap-4 border border-white/40 backdrop-blur-md"
            >
                @foreach($primaryLinks as $link)
                    <a href="{{ $link['href'] ?? '#' }}" class="font-mono text-lg text-blue-black border-b border-stone-200 pb-2">
                        {{ strtoupper($link['label'] ?? '') }}
                    </a>
                @endforeach

                <div class="flex gap-4 mt-2">
                    @foreach($mobileActions as $action)
                        @php
                            $variant = $action['variant'] ?? 'primary';
                            $label = $action['label'] ?? '';
                            $href = $action['href'] ?? '#';
                            $isSearch = strtolower($label) === 'search';
                        @endphp

                        @if($isSearch)
                            @if($variant === 'primary')
                                <button @click="openSearch(); menuOpen = false" class="text-xs font-bold uppercase bg-yellow-400 px-4 py-2 rounded-full">
                                    {{ $label }}
                                </button>
                            @else
                                <button @click="openSearch(); menuOpen = false" class="text-xs font-bold uppercase border border-black px-4 py-2 rounded-full">
                                    {{ $label }}
                                </button>
                            @endif
                        @else
                            @if($variant === 'primary')
                                <a href="{{ $href }}" class="text-xs font-bold uppercase bg-yellow-400 px-4 py-2 rounded-full">
                                    {{ $label }}
                                </a>
                            @else
                                <a href="{{ $href }}" class="text-xs font-bold uppercase border border-black px-4 py-2 rounded-full">
                                    {{ $label }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>

        </div>
    </nav>

    <!-- SEARCH MODAL -->
    <div
        x-show="searchOpen"
        x-cloak
        @click="closeSearch()"
        @keydown.escape.window="closeSearch()"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-blue-black/40 backdrop-blur-sm z-[100] flex items-center justify-center px-5"
    >
        <!-- Close Button -->
        <button
            @click="closeSearch()"
            aria-label="Close search"
            class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-full bg-black/10 hover:bg-black/20 transition"
        >
            <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div
            @click.stop
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-[680px] bg-white rounded-2xl shadow-2xl overflow-hidden"
        >
            <!-- Search Input -->
            <form @submit.prevent="goToSearch()">
                <div class="flex items-center gap-4 px-5 py-4">
                    <svg class="w-5 h-5 text-blue-black/40 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        x-ref="searchInput"
                        x-model="query"
                        @input="debounceSearch()"
                        @keydown.down.prevent="navigateResults('down')"
                        @keydown.up.prevent="navigateResults('up')"
                        @keydown.enter.prevent="selectResult()"
                        type="text"
                        placeholder="Search"
                        class="flex-1 bg-transparent border-none outline-none text-xl text-blue-black placeholder-blue-black/40"
                    >
                </div>
            </form>

            <!-- Results -->
            <div x-show="query.length >= 2" x-cloak class="border-t border-blue-black/10">
                <!-- Loading -->
                <div x-show="loading" class="p-6 text-center">
                    <div class="w-5 h-5 border-2 border-blue-black/20 border-t-blue-black rounded-full animate-spin mx-auto"></div>
                </div>

                <!-- Results List -->
                <div x-show="!loading && results.length > 0" class="max-h-[50vh] overflow-y-auto">
                    <template x-for="(result, index) in results" :key="index">
                        <a
                            :href="result.url"
                            @mouseenter="selectedIndex = index"
                            :class="selectedIndex === index ? 'bg-tasty-yellow/30' : 'bg-transparent'"
                            class="flex items-center gap-4 px-5 py-3 hover:bg-tasty-yellow/20 transition"
                        >
                            <div x-show="result.image" class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-blue-black/5">
                                <img :src="result.image" :alt="result.title" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-blue-black text-base truncate" x-text="result.title"></p>
                                <p class="text-sm text-blue-black/50" x-text="result.subtitle"></p>
                            </div>
                        </a>
                    </template>

                    <!-- View All -->
                    <a
                        :href="`/search?q=${encodeURIComponent(query)}`"
                        class="block px-5 py-3 text-sm text-blue-black/50 hover:text-blue-black hover:bg-blue-black/5 transition border-t border-blue-black/10"
                    >
                        View all results →
                    </a>
                </div>

                <!-- No Results -->
                <div x-show="!loading && query.length >= 2 && results.length === 0" class="px-5 py-6 text-center">
                    <p class="text-blue-black/50">No results found</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function searchNav() {
    return {
        menuOpen: false,
        searchOpen: false,
        query: '',
        results: [],
        loading: false,
        selectedIndex: -1,
        debounceTimer: null,
        navVisible: true,
        lastScrollY: 0,
        scrollThreshold: 10,

        init() {
            window.addEventListener('scroll', () => this.handleScroll());
        },

        handleScroll() {
            const scrollY = window.scrollY;

            if (scrollY < 50) {
                this.navVisible = true;
                this.lastScrollY = scrollY;
                return;
            }

            const scrollDiff = scrollY - this.lastScrollY;

            if (Math.abs(scrollDiff) > this.scrollThreshold) {
                this.navVisible = scrollDiff < 0;
                this.lastScrollY = scrollY;
            }
        },

        openSearch() {
            this.searchOpen = true;
            this.$nextTick(() => {
                this.$refs.searchInput.focus();
            });
        },

        closeSearch() {
            this.searchOpen = false;
            this.query = '';
            this.results = [];
            this.selectedIndex = -1;
        },

        goToSearch() {
            if (this.query.length > 0) {
                window.location.href = `/search?q=${encodeURIComponent(this.query)}`;
            }
        },

        debounceSearch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.performSearch();
            }, 300);
        },

        async performSearch() {
            if (this.query.length < 2) {
                this.results = [];
                return;
            }

            this.loading = true;
            this.selectedIndex = -1;

            try {
                const response = await fetch(`/api/search?q=${encodeURIComponent(this.query)}`);
                const data = await response.json();
                this.results = data.results.slice(0, 5);
            } catch (error) {
                console.error('Search error:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        navigateResults(direction) {
            if (this.results.length === 0) return;

            if (direction === 'down') {
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1);
            } else {
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
            }
        },

        selectResult() {
            if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                window.location.href = this.results[this.selectedIndex].url;
            } else if (this.query.length > 0) {
                this.goToSearch();
            }
        }
    }
}

// Mini cart component
function miniCart() {
    return {
        open: false,
        loading: false,
        items: [],
        total: 0,
        itemCount: 0,

        toggle() {
            this.open = !this.open;
            if (this.open) this.fetchCart();
        },

        async fetchCart() {
            this.loading = true;
            try {
                const r = await fetch('/cart/preview', { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                this.items = data.items;
                this.total = data.total;
                this.itemCount = data.itemCount;
            } catch (e) {
                this.items = [];
            } finally {
                this.loading = false;
            }
        },

        async removeItem(cartItemId) {
            try {
                const r = await fetch('{{ route("cart.remove") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': window.getCsrfToken()
                    },
                    body: JSON.stringify({ cart_item_id: cartItemId })
                });
                const data = await r.json();
                if (data.success) {
                    window.updateCartBadge(data.cartCount);
                    this.fetchCart();
                }
            } catch (e) {}
        }
    };
}

// CSRF token helper — reads from XSRF-TOKEN cookie (always fresh, even on cached pages)
window.getCsrfToken = function() {
    var match = document.cookie.match(new RegExp('(^| )XSRF-TOKEN=([^;]+)'));
    if (match) return decodeURIComponent(match[2]);
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
};

// Cart badge updater
(function() {
    function updateCartBadge(count) {
        document.querySelectorAll('#cart-badge-desktop, #cart-badge-mobile').forEach(function(badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
                badge.classList.add('flex');
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('flex');
            }
        });
    }

    // Fetch count on page load
    fetch('/cart/count', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) { updateCartBadge(data.count); })
        .catch(function() {});

    // Expose globally so add-to-cart forms can call it
    window.updateCartBadge = updateCartBadge;
    window.refreshCartBadge = function() {
        fetch('/cart/count', { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) { updateCartBadge(data.count); })
            .catch(function() {});
    };
    // Expose mini cart refresh so add-to-cart can trigger it
    window.refreshMiniCart = function() {
        var el = document.querySelector('#cart-icon-desktop');
        if (el && el.__x) el.__x.$data.fetchCart();
    };
})();
</script>
@endpush
