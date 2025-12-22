{{-- Latest Updates Section --}}
<section
    class="w-full bg-tasty-off-white relative z-10"
    @if($showLoadMore)
    x-data="latestUpdates({
        action: '{{ $loadAction }}',
        params: {{ Js::from($loadParams) }},
        excludeIds: {{ Js::from($excludeIds) }},
        perPage: 2
    })"
    @endif
>
    {{-- Container with proper padding --}}
    <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 max-lg:px-5 max-lg:pt-10 max-lg:pb-16 flex flex-col gap-16 max-lg:gap-10">

        {{-- Row 1: Intro Section | Featured Card --}}
        {{-- items-end aligns intro section with bottom of featured card --}}
        <div class="flex gap-10 items-end max-lg:flex-col max-lg:items-stretch max-lg:gap-6">
            {{-- Intro Section --}}
            <div class="flex-1 flex flex-col gap-5 items-center justify-end max-lg:justify-center max-lg:w-full">
                {{-- Intro Image --}}
                <div class="w-full max-w-[450px] aspect-square max-lg:max-w-[300px] max-md:max-w-[200px] flex items-center justify-center">
                    <img
                        src="{{ $introImage }}"
                        alt="{{ $introImageAlt }}"
                        class="w-full h-full object-contain"
                        style="mix-blend-mode: darken;"
                    >
                </div>
                {{-- Title & Description --}}
                <div class="flex flex-col gap-5 items-center text-center text-blue-black w-full">
                    <div class="flex flex-col items-center">
                        <span class="text-h2">{{ $titleSmall }}</span>
                        <h2 class="text-h1 uppercase">{{ $titleLarge }}</h2>
                    </div>
                    <p class="text-body-lg max-w-md leading-[26px]">{{ $description }}</p>
                </div>
            </div>

            {{-- Featured Card --}}
            @if($featuredPost)
                <x-cards.featured :post="$featuredPost" />
            @endif
        </div>

        {{-- Horizontal Cards Grid (2 columns, 2 per row) --}}
        <div class="flex flex-col gap-10 max-lg:gap-6">
            {{-- Row of 2 cards --}}
            <div class="grid grid-cols-2 gap-10 max-lg:grid-cols-1 max-lg:gap-6">
                @foreach($posts->take(2) as $post)
                    <x-cards.horizontal :post="$post" />
                @endforeach
            </div>

            {{-- Row of 2 cards --}}
            @if($posts->count() > 2)
                <div class="grid grid-cols-2 gap-10 max-lg:grid-cols-1 max-lg:gap-6">
                    @foreach($posts->skip(2)->take(2) as $post)
                        <x-cards.horizontal :post="$post" />
                    @endforeach
                </div>
            @endif

            @if($showLoadMore)
            {{-- Dynamically loaded posts via Alpine.js --}}
            <template x-for="(pair, index) in chunkedPosts" :key="index">
                <div class="grid grid-cols-2 gap-10 max-lg:grid-cols-1 max-lg:gap-6">
                    <template x-for="post in pair" :key="post.id">
                        <article class="bg-white rounded-xl overflow-hidden p-10 flex gap-10 items-end w-full
                            max-lg:flex-col max-lg:px-4 max-lg:pt-4 max-lg:pb-8 max-lg:gap-4 max-lg:items-start">
                            {{-- Image --}}
                            <div class="w-[200px] self-stretch rounded overflow-hidden flex-shrink-0 relative flex items-end justify-center p-6
                                max-lg:w-full max-lg:h-[206px] max-lg:self-auto max-lg:p-4">
                                <a :href="post.url" class="absolute inset-0">
                                    <img :src="post.image" :alt="post.imageAlt" class="w-full h-full object-cover rounded">
                                </a>
                                {{-- Tag overlay - only visible on tablet/mobile --}}
                                <div class="hidden max-lg:block relative z-10 tag" x-show="post.category || post.tag">
                                    <template x-if="post.category">
                                        <span x-text="post.category?.toUpperCase()"></span>
                                    </template>
                                </div>
                            </div>
                            {{-- Content --}}
                            <div class="flex flex-col flex-1 gap-6 justify-center min-w-0
                                max-lg:gap-5 max-lg:w-full">
                                {{-- Tag - only visible on desktop --}}
                                <div class="tag self-start max-lg:hidden" x-show="post.category || post.tag">
                                    <template x-if="post.category">
                                        <span x-text="post.category?.toUpperCase()"></span>
                                    </template>
                                    <template x-if="post.category && post.tag">
                                        <span class="mx-2.5">•</span>
                                    </template>
                                    <template x-if="post.tag">
                                        <span x-text="post.tag?.toUpperCase()"></span>
                                    </template>
                                </div>
                                <a :href="post.url" class="hover:opacity-80 transition-opacity">
                                    <h3 class="font-display text-[32px] leading-[38px] tracking-[-1.28px] text-blue-black
                                        max-lg:text-[24px] max-lg:leading-[24px] max-lg:tracking-[-0.96px]" x-text="post.title"></h3>
                                </a>
                                {{-- Meta --}}
                                <div class="flex flex-wrap items-center gap-5 text-[14px] leading-[12px] uppercase text-blue-black
                                    max-lg:flex-col max-lg:items-start max-lg:gap-4 max-lg:text-[12px]">
                                    <a :href="post.authorUrl" class="underline underline-offset-4 hover:opacity-80 transition-opacity" x-text="'BY ' + post.author?.toUpperCase()"></a>
                                    <span class="max-lg:hidden">•</span>
                                    <span x-text="post.date?.toUpperCase()"></span>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </template>
            @endif
        </div>

        @if($showLoadMore)
        {{-- Load More Button --}}
        <div class="flex justify-center" x-show="hasMore" x-cloak>
            <button
                @click="loadMore()"
                class="btn btn-yellow"
                :disabled="loading"
                :class="{ 'opacity-50 cursor-not-allowed': loading }"
            >
                <svg x-show="!loading" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg x-show="loading" class="animate-spin" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Loading...' : '{{ $buttonText }}'"></span>
            </button>
        </div>
        @endif
    </div>
</section>

@if($showLoadMore)
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
        perPage: config.perPage || 2,

        get chunkedPosts() {
            const chunks = [];
            for (let i = 0; i < this.loadedPosts.length; i += 2) {
                chunks.push(this.loadedPosts.slice(i, i + 2));
            }
            return chunks;
        },

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
@endif
