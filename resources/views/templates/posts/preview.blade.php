@extends('layouts.app')

@section('content')
    {{-- Preview badge --}}
    <div class="fixed top-28 {{ $isRtl ? 'left-4' : 'right-4' }} z-40">
        <div class="flex items-center gap-2 px-4 py-2 bg-tasty-blue-black/90 text-white text-xs rounded-full shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span>{{ $templateConfig['name'] ?? 'Default' }} Template Preview</span>
        </div>
    </div>

    {{-- Article Header - Based on template type --}}
    @include('components.article.headers.' . ($template ?? 'default'), [
        'post' => $post,
        'isRtl' => $isRtl,
        'readingTime' => $readingTime ?? null,
    ])

    {{-- Credits Section (separate from header) --}}
    <x-article.credits :post="$post" :isRtl="$isRtl" />

    {{-- Include the appropriate content template --}}
    @include('templates.posts.layouts.' . ($template ?? 'default'), [
        'post' => $post,
        'content' => $content,
        'isRtl' => $isRtl,
        'showAuthor' => $showAuthor ?? true,
        'readingTime' => $readingTime ?? null,
        'postTypeConfig' => $postTypeConfig ?? null,
        'templateConfig' => $templateConfig ?? null,
    ])
@endsection
