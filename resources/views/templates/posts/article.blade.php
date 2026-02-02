{{-- Shared Article Template - Used by both public view and CMS preview --}}
@props([
    'post',                  // Post model (required)
    'template' => 'default',
    'templateConfig' => null,
    'isRtl' => false,
    'readingTime' => null,
    'showAuthor' => true,
    'isPreview' => false,
    'relatedPosts' => null,
])

@php
    $contentBlocks = is_array($post->content)
        ? ($post->content['blocks'] ?? $post->content)
        : [];
@endphp

{{-- Preview Badge (only for CMS preview) --}}
@if($isPreview)
    <div class="fixed top-28 {{ $isRtl ? 'left-4' : 'right-4' }} z-40">
        <div class="flex items-center gap-2 px-4 py-2 bg-tasty-blue-black/90 text-white text-xs rounded-full shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span>{{ $templateConfig['name'] ?? 'Default' }} Template Preview</span>
        </div>
    </div>
@endif

{{-- Article Header - Full width, outside constrained article --}}
@switch($template)
    @case('feature')
        {{-- Feature header includes sponsor/share inline --}}
        <x-post.headers.feature :post="$post" />
        @break

    @case('minimal')
        {{-- Minimal header includes author/date/share inline --}}
        <x-post.headers.minimal :post="$post" />
        @break

    @default
        <x-post.headers.default :post="$post" />
@endswitch

<article class="w-full max-w-[1880px] mx-auto">
    {{-- Article Meta (sponsor/share) --}}
    @switch($template)
        @case('minimal')
            <x-post.article-meta :post="$post" :showShare="false" />
            @break

        @case('feature')
            {{-- Feature template has meta in header --}}
            @break

        @default
            <x-post.article-meta :post="$post" />
    @endswitch

    {{-- Prepare ad code for after first paragraph (non-recipe articles only) --}}
    @php
        $categoryId = $post->categories->first()?->id;
        $adCodeAfterHeader = !$isPreview
            ? \App\Models\AdPlacement::getAdForArticleSlot(\App\Models\AdPlacement::SLOT_AFTER_HEADER, $categoryId)
            : null;
    @endphp

    {{-- Article Content --}}
    @if(($post->post_type ?? 'article') === 'recipe')
        {{-- Recipe Layout: Two-column (Ingredients | Preparation) --}}
        @include('templates.posts.partials.recipe-body', [
            'post' => $post,
            'contentBlocks' => $contentBlocks,
            'isRtl' => $isRtl,
        ])
    @elseif(!empty($contentBlocks))
        <div class="w-full bg-off-white pt-8 {{ $template === 'minimal' ? 'lg:pt-12' : '' }} {{ $isRtl ? 'text-right' : '' }}">
            @include('templates.posts.partials.content-blocks', [
                'blocks' => $contentBlocks,
                'isRtl' => $isRtl,
                'adCodeAfterFirstParagraph' => $adCodeAfterHeader,
            ])
        </div>
    @elseif(is_string($post->content))
        {{-- Fallback for HTML content --}}
        <div class="bg-off-white">
            <div class="max-w-[894px] mx-auto px-4 lg:px-0">
                <div class="text-body-lg text-tasty-blue-black/90 prose prose-lg max-w-none">
                    {!! $post->content !!}
                </div>
            </div>
        </div>
    @endif

    {{-- Tags Section --}}
    @if($post->tags->isNotEmpty())
        <div class="bg-off-white pt-8 pb-8">
            <div class="max-w-[894px] mx-auto px-4 lg:px-0 pt-10 border-t border-tasty-blue-black/10">
                <div class="flex flex-wrap items-center gap-2 {{ $isRtl ? 'justify-end' : '' }}">
                    @foreach($post->tags as $tag)
                        @if($isPreview)
                            <span class="inline-block bg-white text-gray-700 text-sm px-3 py-1 rounded-full">
                                {{ $tag->name }}
                            </span>
                        @else
                            <a href="{{ route('tag.show', $tag->slug) }}" class="inline-block bg-white hover:bg-tasty-yellow text-gray-700 text-sm px-3 py-1 rounded-full transition-colors">
                                {{ $tag->name }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Ad Slot: Before comments --}}
    @if(!$isPreview)
        @php
            $categoryId = $categoryId ?? $post->categories->first()?->id;
            $adCodeBeforeComments = \App\Models\AdPlacement::getAdForArticleSlot(\App\Models\AdPlacement::SLOT_BEFORE_COMMENTS, $categoryId);
        @endphp
        @if($adCodeBeforeComments)
            <div class="bg-off-white py-8">
                <div class="ad-slot flex items-center justify-center">
                    {!! $adCodeBeforeComments !!}
                </div>
            </div>
        @endif
    @endif

    {{-- Comments Section (only for public view, not preview) --}}
    @if(!$isPreview)
        <x-comments.section :post="$post" />
    @endif

    {{-- Ad Slot: After comments --}}
    @if(!$isPreview)
        @php
            $categoryId = $categoryId ?? $post->categories->first()?->id;
            $adCodeAfterComments = \App\Models\AdPlacement::getAdForArticleSlot(\App\Models\AdPlacement::SLOT_AFTER_COMMENTS, $categoryId);
        @endphp
        @if($adCodeAfterComments)
            <div class="bg-off-white py-8">
                <div class="ad-slot flex items-center justify-center">
                    {!! $adCodeAfterComments !!}
                </div>
            </div>
        @endif
    @endif
</article>

{{-- Related Posts (only for public view, not preview) --}}
@if(!$isPreview && $relatedPosts?->isNotEmpty())
    @php
        $primaryCategory = $post->categories->first();
        $relatedCount = $relatedPosts->count();
    @endphp
    <section class="w-full max-w-[1880px] mx-auto py-16 md:py-24">
        <div class="container-main px-5 md:px-10">
            <h2 class="text-h2 text-blue-black text-center mb-10 md:mb-16">More In {{ $primaryCategory?->name ?? 'This Category' }}</h2>

            @if($relatedCount === 1)
                {{-- Single post: center it --}}
                <div class="max-w-2xl mx-auto">
                    <x-cards.horizontal :post="$relatedPosts->first()" />
                </div>
            @else
                {{-- Multiple posts: use grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10">
                    @foreach($relatedPosts as $relatedPost)
                        <x-cards.horizontal :post="$relatedPost" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif

{{-- Newsletter Section (only for public view, not preview) --}}
@if(!$isPreview)
    <x-sections.newsletter />
@endif
