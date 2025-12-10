{{-- Minimal Template: Typography-focused layout without distractions --}}
<article class="max-w-xl mx-auto px-6 py-16">
    {{-- Category --}}
    @if($post['category'] ?? null)
        <div class="mb-4 {{ $isRtl ? 'text-right' : '' }}">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ $post['category'] }}
            </span>
        </div>
    @endif

    {{-- Title --}}
    <h1 class="text-3xl font-bold mb-4 leading-snug {{ $isRtl ? 'text-right' : '' }}">
        {{ $post['title'] ?? 'Untitled' }}
    </h1>

    {{-- Subtitle --}}
    @if($post['subtitle'] ?? null)
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8 {{ $isRtl ? 'text-right' : '' }}">
            {{ $post['subtitle'] }}
        </p>
    @endif

    {{-- Simple meta line --}}
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 mb-12 {{ $isRtl ? 'justify-end' : '' }}">
        @if(($showAuthor ?? true) && ($post['author']['name'] ?? null))
            <span>{{ $post['author']['name'] }}</span>
            @if(($readingTime ?? null) || !empty($post['tags']))
                <span>&middot;</span>
            @endif
        @endif
        @if($readingTime ?? null)
            <span>{{ $readingTime }}</span>
            @if(!empty($post['tags']))
                <span>&middot;</span>
            @endif
        @endif
        @if(!empty($post['tags']))
            @foreach($post['tags'] as $tag)
                <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">{{ $tag }}</span>
            @endforeach
        @endif
    </div>

    {{-- Featured Image (smaller, optional) --}}
    @if($post['featured_image_url'] ?? null)
        <div class="mb-12 rounded-lg overflow-hidden">
            <img
                src="{{ $post['featured_image_url'] }}"
                alt="{{ $post['title'] }}"
                class="w-full"
            />
        </div>
    @endif

    {{-- Custom Fields Aside --}}
    @include('templates.posts.partials.custom-fields', [
        'postTypeConfig' => $postTypeConfig ?? null,
        'post' => $post,
        'isRtl' => $isRtl,
    ])

    {{-- Content --}}
    <div class="prose dark:prose-invert max-w-none {{ $isRtl ? 'text-right' : '' }}">
        @include('templates.posts.partials.content-blocks', ['blocks' => $content['blocks'] ?? [], 'isRtl' => $isRtl])
    </div>
</article>
