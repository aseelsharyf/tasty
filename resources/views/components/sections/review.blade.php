{{-- Review Section --}}
@php
    // Cards container class - scroll or grid for the review cards
    $cardsContainerClass = match($mobileLayout) {
        'grid' => 'grid grid-cols-2 gap-6 lg:flex lg:gap-10 lg:flex-wrap',
        default => 'flex gap-6 overflow-x-auto scrollbar-hide -mx-5 px-5 lg:mx-0 lg:px-0 lg:gap-10 lg:flex-wrap lg:overflow-visible',
    };

    // Card class for scroll vs grid mode
    $cardClass = $mobileLayout === 'scroll'
        ? 'flex flex-col gap-6 w-[280px] shrink-0 lg:w-auto lg:flex-1 lg:min-w-[280px] lg:gap-8 group'
        : 'flex flex-col gap-6 lg:gap-8 lg:flex-1 lg:min-w-[280px] group';
@endphp
<section
    class="w-full max-w-[1880px] mx-auto"
    @if($showLoadMore)
    x-data="reviewSection({
        action: '{{ $loadAction }}',
        params: {{ Js::from($loadParams) }},
        excludeIds: {{ Js::from($excludeIds) }},
        perPage: 3,
        showIntro: {{ $showIntro ? 'true' : 'false' }}
    })"
    @endif
>
    <div class="w-full max-w-[1440px] mx-auto px-10 pt-16 pb-32 max-lg:px-5 max-lg:pt-10 max-lg:pb-16 flex flex-col gap-10 lg:gap-16">

        {{-- DESKTOP LAYOUT --}}
        <div class="hidden lg:contents">
            {{-- First Row: Intro + First 2 Cards --}}
            <div class="flex gap-10">
                {{-- Intro Card --}}
                @if($showIntro)
                    <article class="flex flex-col gap-5 flex-1 min-w-[280px]">
                        {{-- Intro Image --}}
                        @if($introImage)
                            <div class="w-full aspect-[400/430] overflow-hidden">
                                <img
                                    src="{{ $introImage }}"
                                    alt="{{ $introImageAlt }}"
                                    class="w-full h-full object-cover mix-blend-darken"
                                >
                            </div>
                        @endif

                        {{-- Intro Text --}}
                        <div class="flex flex-col gap-5 text-center text-blue-black">
                            <div class="flex flex-col items-center">
                                <span class="text-h2">{{ $titleSmall }}</span>
                                <span class="text-h1 uppercase">{{ $titleLarge }}</span>
                            </div>
                            @if($description)
                                <p class="text-[24px] leading-[26px] font-sans">{{ $description }}</p>
                            @endif
                        </div>
                    </article>

                    {{-- Divider after intro --}}
                    @if($showDividers && $posts->count() > 0)
                        <div class="w-px self-stretch {{ $dividerColor }}"></div>
                    @endif
                @endif

                {{-- First Row Cards (2 cards if intro shown, 3 if not) --}}
                @foreach($posts->take($showIntro ? 2 : 3) as $index => $post)
                    @php
                        $postImage = is_array($post) ? ($post['image'] ?? '') : ($post->featured_image_url ?? '');
                        $postTitle = is_array($post) ? ($post['title'] ?? '') : ($post->title ?? '');
                        $postSubtitle = is_array($post) ? ($post['subtitle'] ?? '') : ($post->subtitle ?? '');
                        $postDescription = is_array($post) ? ($post['description'] ?? '') : ($post->excerpt ?? '');
                        $postUrl = is_array($post) ? ($post['url'] ?? '#') : ($post->url ?? '#');
                        $postCategory = is_array($post) ? ($post['category'] ?? null) : ($post->categories->first()?->name ?? null);
                        $postRating = is_array($post) ? ($post['rating'] ?? null) : ($post->rating ?? null);
                    @endphp

                    <a href="{{ $postUrl }}" class="flex flex-col gap-8 flex-1 min-w-[280px] group">
                        {{-- Card Image with Tag --}}
                        <div class="relative w-full flex-1 min-h-[400px] rounded-xl overflow-hidden">
                            <img src="{{ $postImage }}" alt="{{ $postTitle }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute bottom-6 left-0 right-0 flex justify-center">
                                <div class="inline-flex items-center gap-2.5 bg-white rounded-[48px] px-3 py-2 text-[12px] leading-[12px] uppercase whitespace-nowrap">
                                    <span>{{ strtoupper($postCategory ?? 'REVIEW') }}</span>
                                    @if($postRating)
                                        <span>•</span>
                                        <span class="inline-flex items-center gap-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-3 h-3" viewBox="0 0 12 12" fill="{{ $i <= $postRating ? '#FFE762' : '#D1D5DB' }}"><path d="M6 0L7.34708 4.1459H11.7063L8.17963 6.7082L9.52671 10.8541L6 8.2918L2.47329 10.8541L3.82037 6.7082L0.293661 4.1459H4.65292L6 0Z"/></svg>
                                            @endfor
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 text-center text-blue-black">
                            <div class="flex flex-col">
                                <h3 class="text-h3 uppercase">{{ $postTitle }}</h3>
                                @if($postSubtitle)<p class="text-h4">{{ $postSubtitle }}</p>@endif
                            </div>
                            @if($postDescription)<p class="text-body-md">{{ $postDescription }}</p>@endif
                        </div>
                    </a>

                    @if($showDividers && !$loop->last && $loop->iteration < ($showIntro ? 2 : 3))
                        <div class="w-px self-stretch {{ $dividerColor }}"></div>
                    @endif
                @endforeach
            </div>

            {{-- Second Row: Remaining Cards --}}
            @if($posts->count() > ($showIntro ? 2 : 3))
                <div class="flex gap-10">
                    @foreach($posts->skip($showIntro ? 2 : 3) as $index => $post)
                        @php
                            $postImage = is_array($post) ? ($post['image'] ?? '') : ($post->featured_image_url ?? '');
                            $postTitle = is_array($post) ? ($post['title'] ?? '') : ($post->title ?? '');
                            $postSubtitle = is_array($post) ? ($post['subtitle'] ?? '') : ($post->subtitle ?? '');
                            $postDescription = is_array($post) ? ($post['description'] ?? '') : ($post->excerpt ?? '');
                            $postUrl = is_array($post) ? ($post['url'] ?? '#') : ($post->url ?? '#');
                            $postCategory = is_array($post) ? ($post['category'] ?? null) : ($post->categories->first()?->name ?? null);
                            $postRating = is_array($post) ? ($post['rating'] ?? null) : ($post->rating ?? null);
                        @endphp

                        <a href="{{ $postUrl }}" class="flex flex-col gap-8 flex-1 min-w-[280px] group">
                            <div class="relative w-full h-[460px] rounded-xl overflow-hidden">
                                <img src="{{ $postImage }}" alt="{{ $postTitle }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                <div class="absolute bottom-6 left-0 right-0 flex justify-center">
                                    <div class="inline-flex items-center gap-2.5 bg-white rounded-[48px] px-3 py-2 text-[12px] leading-[12px] uppercase whitespace-nowrap">
                                        <span>{{ strtoupper($postCategory ?? 'REVIEW') }}</span>
                                        @if($postRating)
                                            <span>•</span>
                                            <span class="inline-flex items-center gap-0.5">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg class="w-3 h-3" viewBox="0 0 12 12" fill="{{ $i <= $postRating ? '#FFE762' : '#D1D5DB' }}"><path d="M6 0L7.34708 4.1459H11.7063L8.17963 6.7082L9.52671 10.8541L6 8.2918L2.47329 10.8541L3.82037 6.7082L0.293661 4.1459H4.65292L6 0Z"/></svg>
                                                @endfor
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-5 text-center text-blue-black">
                                <div class="flex flex-col">
                                    <h3 class="text-h3 uppercase">{{ $postTitle }}</h3>
                                    @if($postSubtitle)<p class="text-h4">{{ $postSubtitle }}</p>@endif
                                </div>
                                @if($postDescription)<p class="text-body-md">{{ $postDescription }}</p>@endif
                            </div>
                        </a>

                        @if($showDividers && !$loop->last)
                            <div class="w-px self-stretch {{ $dividerColor }}"></div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- MOBILE LAYOUT --}}
        <div class="lg:hidden flex flex-col gap-10">
            {{-- Intro Card (full width, own row) --}}
            @if($showIntro)
                <article class="flex flex-col gap-5">
                    @if($introImage)
                        <div class="w-full aspect-[400/430] overflow-hidden">
                            <img src="{{ $introImage }}" alt="{{ $introImageAlt }}" class="w-full h-full object-cover mix-blend-darken">
                        </div>
                    @endif
                    <div class="flex flex-col gap-5 text-center text-blue-black">
                        <div class="flex flex-col items-center">
                            <span class="text-h2">{{ $titleSmall }}</span>
                            <span class="text-h1 uppercase">{{ $titleLarge }}</span>
                        </div>
                        @if($description)
                            <p class="text-[18px] leading-[24px] font-sans">{{ $description }}</p>
                        @endif
                    </div>
                </article>
            @endif

            {{-- Review Cards (scroll or grid) --}}
            <div class="{{ $cardsContainerClass }}">
                @foreach($posts as $index => $post)
                    @php
                        $postImage = is_array($post) ? ($post['image'] ?? '') : ($post->featured_image_url ?? '');
                        $postTitle = is_array($post) ? ($post['title'] ?? '') : ($post->title ?? '');
                        $postSubtitle = is_array($post) ? ($post['subtitle'] ?? '') : ($post->subtitle ?? '');
                        $postDescription = is_array($post) ? ($post['description'] ?? '') : ($post->excerpt ?? '');
                        $postUrl = is_array($post) ? ($post['url'] ?? '#') : ($post->url ?? '#');
                        $postCategory = is_array($post) ? ($post['category'] ?? null) : ($post->categories->first()?->name ?? null);
                        $postRating = is_array($post) ? ($post['rating'] ?? null) : ($post->rating ?? null);
                    @endphp

                    <a href="{{ $postUrl }}" class="{{ $cardClass }}">
                        <div class="relative w-full h-[320px] rounded-xl overflow-hidden">
                            <img src="{{ $postImage }}" alt="{{ $postTitle }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute bottom-6 left-0 right-0 flex justify-center">
                                <div class="inline-flex items-center gap-2.5 bg-white rounded-[48px] px-3 py-2 text-[12px] leading-[12px] uppercase whitespace-nowrap">
                                    <span>{{ strtoupper($postCategory ?? 'REVIEW') }}</span>
                                    @if($postRating)
                                        <span>•</span>
                                        <span class="inline-flex items-center gap-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-3 h-3" viewBox="0 0 12 12" fill="{{ $i <= $postRating ? '#FFE762' : '#D1D5DB' }}"><path d="M6 0L7.34708 4.1459H11.7063L8.17963 6.7082L9.52671 10.8541L6 8.2918L2.47329 10.8541L3.82037 6.7082L0.293661 4.1459H4.65292L6 0Z"/></svg>
                                            @endfor
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-4 text-center text-blue-black">
                            <div class="flex flex-col">
                                <h3 class="text-[28px] leading-[32px] tracking-[-1.12px] font-display uppercase">{{ $postTitle }}</h3>
                                @if($postSubtitle)<p class="text-[24px] leading-[28px] tracking-[-0.96px] font-display">{{ $postSubtitle }}</p>@endif
                            </div>
                            @if($postDescription)<p class="text-[18px] leading-[26px]">{{ $postDescription }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        @if($showLoadMore)
        {{-- Dynamically loaded cards via Alpine.js (Desktop) - Always grid --}}
        <div class="hidden lg:grid grid-cols-3 gap-10">
            <template x-for="post in loadedPosts" :key="post.id">
                <a :href="post.url" class="flex flex-col gap-8 group">
                    <div class="relative w-full h-[460px] rounded-xl overflow-hidden">
                        <img :src="post.image" :alt="post.title" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        <div class="absolute bottom-6 left-0 right-0 flex justify-center">
                            <div class="inline-flex items-center gap-2.5 bg-white rounded-[48px] px-3 py-2 text-[12px] leading-[12px] uppercase whitespace-nowrap">
                                <span x-text="post.category ? post.category.toUpperCase() : 'REVIEW'"></span>
                                <template x-if="post.rating">
                                    <span class="contents">
                                        <span>•</span>
                                        <span class="inline-flex items-center gap-0.5">
                                            <template x-for="star in 5" :key="star">
                                                <svg class="w-3 h-3" viewBox="0 0 12 12" :fill="star <= post.rating ? '#FFE762' : '#D1D5DB'"><path d="M6 0L7.34708 4.1459H11.7063L8.17963 6.7082L9.52671 10.8541L6 8.2918L2.47329 10.8541L3.82037 6.7082L0.293661 4.1459H4.65292L6 0Z"/></svg>
                                            </template>
                                        </span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-5 text-center text-blue-black">
                        <div class="flex flex-col">
                            <h3 class="text-h3 uppercase" x-text="post.title"></h3>
                            <p class="text-h4" x-show="post.subtitle" x-text="post.subtitle"></p>
                        </div>
                        <p class="text-body-md" x-show="post.description" x-text="post.description"></p>
                    </div>
                </a>
            </template>
        </div>

        {{-- Dynamically loaded cards via Alpine.js (Mobile) - Always grid --}}
        <div class="lg:hidden grid grid-cols-2 gap-6">
            <template x-for="post in loadedPosts" :key="post.id">
                <a :href="post.url" class="flex flex-col gap-6 group">
                    <div class="relative w-full h-[320px] rounded-xl overflow-hidden">
                        <img :src="post.image" :alt="post.title" class="absolute inset-0 w-full h-full object-cover">
                        <div class="absolute bottom-6 left-0 right-0 flex justify-center">
                            <div class="inline-flex items-center gap-2.5 bg-white rounded-[48px] px-3 py-2 text-[12px] leading-[12px] uppercase whitespace-nowrap">
                                <span x-text="post.category ? post.category.toUpperCase() : 'REVIEW'"></span>
                                <template x-if="post.rating">
                                    <span class="contents">
                                        <span>•</span>
                                        <span class="inline-flex items-center gap-0.5">
                                            <template x-for="star in 5" :key="star">
                                                <svg class="w-3 h-3" viewBox="0 0 12 12" :fill="star <= post.rating ? '#FFE762' : '#D1D5DB'"><path d="M6 0L7.34708 4.1459H11.7063L8.17963 6.7082L9.52671 10.8541L6 8.2918L2.47329 10.8541L3.82037 6.7082L0.293661 4.1459H4.65292L6 0Z"/></svg>
                                            </template>
                                        </span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-4 text-center text-blue-black">
                        <div class="flex flex-col">
                            <h3 class="text-[28px] leading-[32px] tracking-[-1.12px] font-display uppercase" x-text="post.title"></h3>
                            <p class="text-[24px] leading-[28px] tracking-[-0.96px] font-display" x-show="post.subtitle" x-text="post.subtitle"></p>
                        </div>
                        <p class="text-[18px] leading-[26px]" x-show="post.description" x-text="post.description"></p>
                    </div>
                </a>
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
    Alpine.data('reviewSection', (config) => ({
        loadedPosts: [],
        loading: false,
        hasMore: true,
        page: 1,
        action: config.action || 'recent',
        params: config.params || {},
        excludeIds: config.excludeIds || [],
        perPage: config.perPage || 3,
        showIntro: config.showIntro || false,

        get chunkedPosts() {
            const chunks = [];
            for (let i = 0; i < this.loadedPosts.length; i += 3) {
                chunks.push(this.loadedPosts.slice(i, i + 3));
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
