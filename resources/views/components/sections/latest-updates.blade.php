{{-- Latest Updates Section --}}
<section
    class="w-full bg-tasty-off-white max-w-[1880px] mx-auto py-16 px-10 max-md:px-5 max-md:py-8"
    x-data="latestUpdates({
        action: '{{ $loadAction }}',
        params: {{ Js::from($loadParams) }},
        excludeIds: {{ Js::from($excludeIds) }},
        perPage: 2
    })"
>
    <div class="container-main flex flex-col gap-10">
        {{-- Top Row: Title Column + Large Article --}}
        <div class="grid grid-cols-2 gap-6 items-center max-lg:grid-cols-1">
            {{-- Title Column --}}
            <div class="flex flex-col gap-5 items-start max-lg:items-center max-lg:w-full">
                <div class="w-full max-w-[380px] max-lg:max-w-[320px] mx-auto lg:mx-0">
                    <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-auto object-contain" style="mix-blend-mode: darken;">
                </div>
                <div class="flex flex-col items-start max-lg:items-center max-lg:text-center text-blue-black gap-4">
                    <div class="flex flex-col items-start max-lg:items-center">
                        <span class="text-h3">{{ $titleSmall }}</span>
                        <h2 class="text-h2 uppercase">{{ $titleLarge }}</h2>
                    </div>
                    <p class="text-body-md max-w-md">{{ $description }}</p>
                </div>
            </div>
            {{-- Large Featured Article --}}
            @if($featuredPost)
                <x-cards.featured :post="$featuredPost" />
            @endif
        </div>

        {{-- Bottom Row: Article Cards Grid --}}
        <div class="grid grid-cols-2 gap-6 max-md:grid-cols-1">
            {{-- Initial posts from server --}}
            @foreach($posts as $post)
                <x-cards.horizontal :post="$post" />
            @endforeach

            {{-- Dynamically loaded posts via Alpine.js --}}
            <template x-for="post in loadedPosts" :key="post.id">
                <article class="card-horizontal bg-white rounded-xl overflow-hidden">
                    <a :href="post.url" class="card-horizontal-image group">
                        <img :src="post.image" :alt="post.imageAlt" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    </a>
                    <div class="flex flex-col gap-4 flex-1 min-w-0">
                        <div class="tag self-start" x-show="post.category || post.tag">
                            <template x-if="post.category">
                                <a :href="post.categoryUrl" class="hover:underline" x-text="post.category?.toUpperCase()"></a>
                            </template>
                            <template x-if="post.category && post.tag">
                                <span class="mx-1">•</span>
                            </template>
                            <template x-if="post.tag">
                                <a :href="post.tagUrl" class="hover:underline" x-text="post.tag?.toUpperCase()"></a>
                            </template>
                        </div>
                        <a :href="post.url" class="hover:text-tasty-yellow transition-colors">
                            <h3 class="text-h4 text-blue-black line-clamp-2" x-text="post.title"></h3>
                        </a>
                        <div class="meta-row text-caption uppercase text-blue-black">
                            <a :href="post.authorUrl" class="underline underline-offset-4 hover:text-tasty-yellow transition-colors" x-text="'BY ' + post.author"></a>
                            <span class="meta-separator">•</span>
                            <span x-text="post.date"></span>
                        </div>
                    </div>
                </article>
            </template>
        </div>

        {{-- Load More Button --}}
        <div class="flex justify-center" x-show="hasMore" x-cloak>
            <button
                @click="loadMore()"
                class="btn btn-yellow"
                :disabled="loading"
                :class="{ 'opacity-50 cursor-not-allowed': loading }"
            >
                <svg x-show="!loading" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg x-show="loading" class="animate-spin" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Loading...' : '{{ $buttonText }}'"></span>
            </button>
        </div>
    </div>
</section>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('latestUpdates', (config) => ({
        loadedPosts: [],
        loading: false,
        hasMore: true,
        page: 1,
        action: config.action || 'recent',
        params: config.params || {},
        excludeIds: config.excludeIds || [],
        perPage: config.perPage || 4,

        async loadMore() {
            if (this.loading || !this.hasMore) return;

            this.loading = true;

            try {
                const response = await fetch('{{ route('api.posts.load-more') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: this.action,
                        page: this.page,
                        perPage: this.perPage,
                        excludeIds: this.excludeIds,
                        ...this.params
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to load posts');
                }

                const data = await response.json();

                if (data.posts && data.posts.length > 0) {
                    this.loadedPosts.push(...data.posts);
                    // Add new post IDs to exclude list to prevent duplicates
                    data.posts.forEach(post => {
                        if (!this.excludeIds.includes(post.id)) {
                            this.excludeIds.push(post.id);
                        }
                    });
                    this.page++;
                }

                this.hasMore = data.hasMore || false;
            } catch (error) {
                console.error('Error loading posts:', error);
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
