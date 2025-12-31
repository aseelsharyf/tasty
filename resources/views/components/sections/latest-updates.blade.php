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
    {{-- Top Row Container - aligns with hero's 1880px container --}}
    <div class="w-full max-w-[1880px] mx-auto pt-16 max-xl:pt-8 max-xl:px-5">
        {{-- Row 1: Intro Section | Featured Card --}}
        {{-- Uses same 50/50 split as hero section --}}
        <div class="flex max-xl:flex-col max-xl:items-stretch max-xl:gap-6">
            {{-- Intro Section - takes left 50% like hero image --}}
            <div class="w-1/2 flex flex-col gap-5 items-center justify-center max-xl:w-full">
                {{-- Intro Image --}}
                <div class="w-full max-w-[450px] h-[429.5px] max-xl:h-[182px] max-xl:max-w-[300px] max-md:max-w-[200px] flex items-center justify-center">
                    <img
                        src="{{ $introImage }}"
                        alt="{{ $introImageAlt }}"
                        class="w-full h-full object-contain"
                        style="mix-blend-mode: darken;"
                    >
                </div>
                {{-- Title & Description --}}
                <div class="flex flex-col gap-4 items-center text-center text-blue-black w-full max-w-[450px]">
                    <div class="flex flex-col items-center">
                        <span class="font-display text-[48px] leading-[1.1] tracking-[-0.04em] max-xl:text-[24px]">{{ $titleSmall }}</span>
                        <h2 class="font-display text-[72px] leading-[1] tracking-[-0.04em] uppercase max-xl:text-[36px]">{{ $titleLarge }}</h2>
                    </div>
                    <p class="text-body-md">{{ $description }}</p>
                </div>
            </div>

            {{-- Featured Card - takes right 50% like hero yellow section --}}
            @if($featuredPost)
                <div class="w-1/2 pr-10 max-xl:w-full max-xl:pr-0 max-xl:flex max-xl:justify-center">
                    <x-cards.featured :post="$featuredPost" />
                </div>
            @endif
        </div>
    </div>

    {{-- Cards Grid Container - uses same 1880px container as top row --}}
    <div class="w-full max-w-[1880px] mx-auto pt-16 pb-32 max-lg:pt-8 max-lg:pb-16 px-10 max-lg:px-5">
        {{-- 2-column grid: Post1 | Post2, Post3 | Post4 --}}
        {{-- Desktop: horizontal cards, left aligned right, right aligned left --}}
        {{-- Tablet: vertical cards, 2 per row with gap --}}
        {{-- Mobile: single column --}}
        <div class="grid grid-cols-2 gap-y-10 max-xl:gap-6 max-lg:grid-cols-1">
            @foreach($posts as $index => $post)
                <div class="{{ $index % 2 === 0 ? 'justify-self-end pr-10' : 'justify-self-start' }} max-xl:justify-self-auto max-xl:pr-0 max-xl:max-w-none w-full max-w-[660px]">
                    <x-cards.horizontal :post="$post" class="h-full" />
                </div>
            @endforeach

            @if($showLoadMore)
            {{-- Dynamically loaded posts via Alpine.js --}}
            <template x-for="(post, index) in loadedPosts" :key="post.id">
                <div :class="[({{ count($posts) }} + index) % 2 === 0 ? 'justify-self-end pr-10' : 'justify-self-start', 'max-xl:justify-self-auto max-xl:pr-0 max-xl:max-w-none', 'w-full', 'max-w-[660px]']">
                <article class="bg-white rounded-xl overflow-hidden p-10 flex gap-10 items-center w-full h-full
                    max-xl:flex-col max-xl:p-0 max-xl:pt-4 max-xl:px-4 max-xl:pb-8 max-xl:gap-4 max-xl:items-start">
                    {{-- Image --}}
                    <div class="w-[200px] h-[200px] flex-shrink-0 relative
                        max-xl:w-full max-xl:h-auto max-xl:aspect-[4/3] max-xl:p-4">
                        <a :href="post.url" class="block w-full h-full max-xl:absolute max-xl:inset-0">
                            <img :src="post.image" :alt="post.imageAlt" class="w-full h-full object-cover object-center rounded">
                        </a>
                        {{-- Tag overlay - only visible on tablet/mobile --}}
                        <template x-if="post.category || post.tag">
                            <div class="hidden max-xl:block relative z-10">
                                <div class="tag self-start">
                                    <template x-if="post.category">
                                        <a :href="post.categoryUrl || '#'" class="hover:underline" x-text="post.category?.toUpperCase()"></a>
                                    </template>
                                    <template x-if="post.category && post.tag">
                                        <span class="mx-1">•</span>
                                    </template>
                                    <template x-if="post.tag">
                                        <a :href="post.tagUrl || '#'" class="hover:underline" x-text="post.tag?.toUpperCase()"></a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    {{-- Content --}}
                    <div class="flex flex-col flex-1 gap-6 justify-center min-w-0
                        max-xl:gap-5 max-xl:w-full">
                        {{-- Tag - only visible on desktop --}}
                        <template x-if="post.category || post.tag">
                            <div class="max-xl:hidden">
                                <div class="tag self-start">
                                    <template x-if="post.category">
                                        <a :href="post.categoryUrl || '#'" class="hover:underline" x-text="post.category?.toUpperCase()"></a>
                                    </template>
                                    <template x-if="post.category && post.tag">
                                        <span class="mx-1">•</span>
                                    </template>
                                    <template x-if="post.tag">
                                        <a :href="post.tagUrl || '#'" class="hover:underline" x-text="post.tag?.toUpperCase()"></a>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <a :href="post.url" class="hover:opacity-80 transition-opacity">
                            <h3 class="font-display text-[28px] leading-[1.1] tracking-[-0.04em] text-blue-black line-clamp-3
                                max-xl:text-[24px]" x-text="post.title"></h3>
                        </a>
                        {{-- Meta --}}
                        <div class="flex flex-wrap items-center gap-5 text-[14px] leading-[12px] uppercase text-blue-black
                            max-xl:flex-col max-xl:items-start max-xl:gap-4 max-xl:text-[12px]">
                            <a :href="post.authorUrl" class="underline underline-offset-4 hover:opacity-80 transition-opacity" x-text="'BY ' + post.author?.toUpperCase()"></a>
                            <span class="max-xl:hidden">•</span>
                            <span x-text="post.date?.toUpperCase()"></span>
                        </div>
                    </div>
                </article>
                </div>
            </template>
            @endif
        </div>

        @if($showLoadMore)
        {{-- Load More Button --}}
        <div class="flex justify-center mt-16 max-lg:mt-10" x-show="hasMore" x-cloak>
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
