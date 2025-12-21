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

                <div @click="openSearch()" class="hidden lg:flex h-full px-6 md:px-8 items-center justify-center cursor-pointer hover:opacity-70 transition gap-3">
                    <span class="font-mono text-base text-blue-black uppercase tracking-wide">Search</span>
                    <i class="fas fa-search text-blue-black text-base"></i>
                </div>

                <div class="lg:hidden flex items-center h-full px-4 gap-4">
                    <button @click="openSearch()" class="text-blue-black focus:outline-none">
                        <i class="fas fa-search text-base"></i>
                    </button>
                    <button @click="menuOpen = !menuOpen" class="flex items-center gap-3 text-blue-black focus:outline-none">
                        <span class="font-mono text-base tracking-widest uppercase">Menu</span>
                        <i class="fas text-base" :class="menuOpen ? 'fa-xmark' : 'fa-bars'"></i>
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
                            {{-- Search button triggers modal --}}
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
                            {{-- Regular link --}}
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

    <!-- SEARCH MODAL (Outside nav, sibling element) -->
    <div
        x-show="searchOpen"
        x-cloak
        @click="closeSearch()"
        @keydown.escape.window="closeSearch()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] flex items-start justify-center pt-[120px] px-4"
    >
        <div
            @click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 -translate-y-4"
            class="w-full max-w-3xl bg-white/95 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl overflow-hidden"
        >
            <!-- Search Input -->
            <div class="flex items-center gap-4 p-6 border-b border-stone-200">
                <i class="fas fa-search text-stone-400 text-xl flex-shrink-0"></i>
                <input
                    x-ref="searchInput"
                    x-model="query"
                    @input="debounceSearch()"
                    @keydown.down.prevent="navigateResults('down')"
                    @keydown.up.prevent="navigateResults('up')"
                    @keydown.enter.prevent="selectResult()"
                    type="text"
                    placeholder="Search for recipes, ingredients, articles..."
                    class="flex-1 bg-transparent border-none outline-none text-lg text-blue-black placeholder-stone-400 font-mono"
                >
                <button @click="closeSearch()" class="flex-shrink-0 text-stone-400 hover:text-blue-black transition">
                    <i class="fas fa-xmark text-2xl"></i>
                </button>
            </div>

            <!-- Search Results -->
            <div class="max-h-[60vh] overflow-y-auto">
                <!-- Loading State -->
                <div x-show="loading" class="p-8 text-center">
                    <i class="fas fa-spinner fa-spin text-3xl text-yellow-600 mb-3"></i>
                    <p class="text-sm text-stone-500 font-mono">Searching...</p>
                </div>

                <!-- Results -->
                <template x-if="!loading && results.length > 0">
                    <div class="p-4">
                        <template x-for="(result, index) in results" :key="index">
                            <a
                                :href="result.url"
                                @mouseenter="selectedIndex = index"
                                :class="{'bg-yellow-50': selectedIndex === index}"
                                class="block p-4 rounded-lg hover:bg-yellow-50 transition group"
                            >
                                <div class="flex items-start gap-4">
                                    <!-- Icon based on type -->
                                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                                        <i :class="result.icon || 'fa-utensils'" class="fas text-white text-lg"></i>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-mono font-bold text-blue-black group-hover:text-yellow-600 transition mb-1" x-text="result.title"></h3>
                                        <p class="text-sm text-stone-600 line-clamp-2 mb-2" x-text="result.description"></p>
                                        <div class="flex items-center gap-3 text-xs text-stone-500">
                                            <span class="uppercase tracking-wider" x-text="result.type"></span>
                                            <span x-show="result.readTime">•</span>
                                            <span x-show="result.readTime" x-text="result.readTime"></span>
                                        </div>
                                    </div>

                                    <i class="fas fa-arrow-right text-stone-400 group-hover:text-yellow-600 group-hover:translate-x-1 transition flex-shrink-0 mt-1"></i>
                                </div>
                            </a>
                        </template>

                        <!-- View All Results Link -->
                        <div class="mt-4 pt-4 border-t border-stone-200">
                            <a
                                :href="`/search?q=${encodeURIComponent(query)}`"
                                class="flex items-center justify-center gap-2 p-3 text-sm font-mono text-yellow-600 hover:text-yellow-700 hover:bg-yellow-50 rounded-lg transition"
                            >
                                <span>View all results</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </template>

                <!-- No Results -->
                <div x-show="!loading && query.length > 0 && results.length === 0" class="p-8 text-center">
                    <i class="fas fa-search text-4xl text-stone-300 mb-3"></i>
                    <p class="text-lg font-mono text-stone-600 mb-1">No results found</p>
                    <p class="text-sm text-stone-500">Try different keywords or check your spelling</p>
                </div>

                <!-- Popular Searches (shown when empty) -->
                <div x-show="!loading && query.length === 0" class="p-6">
                    <p class="text-xs uppercase tracking-wider text-stone-500 font-mono mb-4">Popular Searches</p>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="suggestion in popularSearches" :key="suggestion.term">
                            <button
                                @click="selectSuggestion(suggestion.term)"
                                class="flex items-center gap-3 p-3 bg-stone-50 hover:bg-yellow-50 rounded-lg transition text-left group"
                            >
                                <i :class="suggestion.icon" class="fas text-yellow-600 text-lg"></i>
                                <span class="font-mono text-sm text-blue-black group-hover:text-yellow-600" x-text="suggestion.term"></span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-6 pt-6 border-t border-stone-200">
                        <p class="text-xs uppercase tracking-wider text-stone-500 font-mono mb-3">Quick Links</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="#" class="px-4 py-2 bg-white border border-stone-200 hover:border-yellow-600 hover:bg-yellow-50 rounded-full text-sm font-mono transition">All Recipes</a>
                            <a href="#" class="px-4 py-2 bg-white border border-stone-200 hover:border-yellow-600 hover:bg-yellow-50 rounded-full text-sm font-mono transition">Collections</a>
                            <a href="#" class="px-4 py-2 bg-white border border-stone-200 hover:border-yellow-600 hover:bg-yellow-50 rounded-full text-sm font-mono transition">Blog</a>
                        </div>
                    </div>
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
        scrollY: 0,
        lastScrollY: 0,
        scrollThreshold: 10,
        popularSearches: [
            { term: 'Pasta Recipes', icon: 'fa-bowl-food' },
            { term: 'Quick Desserts', icon: 'fa-cake-candles' },
            { term: 'Healthy Meals', icon: 'fa-leaf' },
            { term: 'Vegetarian', icon: 'fa-carrot' },
            { term: 'Grilling', icon: 'fa-fire' },
            { term: 'Breakfast Ideas', icon: 'fa-mug-hot' }
        ],

        init() {
            window.addEventListener('scroll', () => this.handleScroll());
        },

        handleScroll() {
            this.scrollY = window.scrollY;

            // Always show nav at the top of the page
            if (this.scrollY < 50) {
                this.navVisible = true;
                this.lastScrollY = this.scrollY;
                return;
            }

            // Check if scroll difference exceeds threshold
            const scrollDiff = this.scrollY - this.lastScrollY;

            if (Math.abs(scrollDiff) > this.scrollThreshold) {
                if (scrollDiff > 0) {
                    // Scrolling down - hide nav
                    this.navVisible = false;
                } else {
                    // Scrolling up - show nav
                    this.navVisible = true;
                }

                this.lastScrollY = this.scrollY;
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
                this.results = data.results.map(result => ({
                    title: result.title,
                    description: result.subtitle,
                    type: result.type,
                    url: result.url,
                    image: result.image,
                    icon: this.getIconForType(result.type)
                }));
            } catch (error) {
                console.error('Search error:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        getIconForType(type) {
            const icons = {
                'post': 'fa-newspaper',
                'category': 'fa-folder',
                'tag': 'fa-tag',
                'author': 'fa-user'
            };
            return icons[type] || 'fa-file';
        },

        selectSuggestion(term) {
            this.query = term;
            this.performSearch();
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
            }
        }
    }
}
</script>
@endpush
