{{-- Feature Template: Full-width hero image with immersive reading experience --}}
<article>
    {{-- Hero Section --}}
    @if($post['featured_image_url'] ?? null)
        <div class="relative h-[50vh] min-h-[400px] mb-12">
            <img
                src="{{ $post['featured_image_url'] }}"
                alt="{{ $post['title'] }}"
                class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-8 max-w-4xl mx-auto">
                @if($post['category'] ?? null)
                    <span class="inline-block px-3 py-1 text-xs font-medium uppercase tracking-wider text-white bg-white/20 backdrop-blur-sm rounded-full mb-4">
                        {{ $post['category'] }}
                    </span>
                @endif
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-4 leading-tight {{ $isRtl ? 'text-right' : '' }}">
                    {{ $post['title'] ?? 'Untitled' }}
                </h1>
                @if($post['subtitle'] ?? null)
                    <p class="text-xl text-white/80 {{ $isRtl ? 'text-right' : '' }}">
                        {{ $post['subtitle'] }}
                    </p>
                @endif
            </div>
        </div>
    @else
        {{-- No featured image - show title normally --}}
        <div class="max-w-4xl mx-auto px-6 pt-12 pb-8">
            @if($post['category'] ?? null)
                <span class="inline-block px-3 py-1 text-xs font-medium uppercase tracking-wider text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded-full mb-4">
                    {{ $post['category'] }}
                </span>
            @endif
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-4 leading-tight {{ $isRtl ? 'text-right' : '' }}">
                {{ $post['title'] ?? 'Untitled' }}
            </h1>
            @if($post['subtitle'] ?? null)
                <p class="text-xl text-gray-600 dark:text-gray-400 {{ $isRtl ? 'text-right' : '' }}">
                    {{ $post['subtitle'] }}
                </p>
            @endif
        </div>
    @endif

    {{-- Meta --}}
    <div class="max-w-4xl mx-auto px-6">
        <div class="flex flex-wrap items-center gap-4 mb-8 pb-8 border-b border-gray-200 dark:border-gray-700">
            @if(($showAuthor ?? true) && ($post['author']['name'] ?? null))
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-medium block">{{ $post['author']['name'] }}</span>
                        @if($readingTime ?? null)
                            <span class="text-xs text-gray-500">{{ $readingTime }}</span>
                        @endif
                    </div>
                </div>
            @elseif($readingTime ?? null)
                <span class="text-sm text-gray-500">{{ $readingTime }}</span>
            @endif
            {{-- Tags --}}
            @if(!empty($post['tags']))
                <div class="flex flex-wrap items-center gap-2 {{ (($showAuthor ?? true) && ($post['author']['name'] ?? null)) || ($readingTime ?? null) ? 'ml-auto' : '' }}">
                    @foreach($post['tags'] as $tag)
                        <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="max-w-4xl mx-auto px-6 pb-12">
        {{-- Custom Fields Aside --}}
        @include('templates.posts.partials.custom-fields', [
            'postTypeConfig' => $postTypeConfig ?? null,
            'post' => $post,
            'isRtl' => $isRtl,
        ])

        <div class="prose prose-lg lg:prose-xl dark:prose-invert max-w-none {{ $isRtl ? 'text-right' : '' }}">
            @include('templates.posts.partials.content-blocks', ['blocks' => $content['blocks'] ?? [], 'isRtl' => $isRtl])
        </div>
    </div>
</article>
