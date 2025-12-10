{{-- Default Template: Clean, readable layout with centered content --}}
<article class="max-w-2xl mx-auto px-6 py-12">
    {{-- Category --}}
    @if($post['category'] ?? null)
        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
            <span class="inline-block px-3 py-1 text-xs font-medium uppercase tracking-wider text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded-full">
                {{ $post['category'] }}
            </span>
        </div>
    @endif

    {{-- Featured Image --}}
    @if($post['featured_image_url'] ?? null)
        <div class="mb-8 -mx-6 sm:mx-0 sm:rounded-xl overflow-hidden">
            <img
                src="{{ $post['featured_image_url'] }}"
                alt="{{ $post['title'] }}"
                class="w-full h-64 sm:h-80 object-cover"
            />
        </div>
    @endif

    {{-- Title --}}
    <h1 class="text-4xl sm:text-5xl font-bold mb-4 leading-tight {{ $isRtl ? 'text-right' : '' }}">
        {{ $post['title'] ?? 'Untitled' }}
    </h1>

    {{-- Subtitle --}}
    @if($post['subtitle'] ?? null)
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-6 {{ $isRtl ? 'text-right' : '' }}">
            {{ $post['subtitle'] }}
        </p>
    @endif

    {{-- Meta --}}
    <div class="flex flex-wrap items-center gap-4 mb-8 pb-8 border-b border-gray-200 dark:border-gray-700">
        @if(($showAuthor ?? true) && ($post['author']['name'] ?? null))
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <span class="text-sm font-medium">{{ $post['author']['name'] }}</span>
            </div>
        @endif
        @if($readingTime ?? null)
            <span class="text-sm text-gray-500">{{ $readingTime }}</span>
        @endif
        {{-- Tags --}}
        @if(!empty($post['tags']))
            <div class="flex flex-wrap items-center gap-2">
                @foreach($post['tags'] as $tag)
                    <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Custom Fields Aside --}}
    @include('templates.posts.partials.custom-fields', [
        'postTypeConfig' => $postTypeConfig ?? null,
        'post' => $post,
        'isRtl' => $isRtl,
    ])

    {{-- Content --}}
    <div class="prose prose-lg dark:prose-invert max-w-none {{ $isRtl ? 'text-right' : '' }}">
        @include('templates.posts.partials.content-blocks', ['blocks' => $content['blocks'] ?? [], 'isRtl' => $isRtl])
    </div>
</article>
