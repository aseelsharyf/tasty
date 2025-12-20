@extends('layouts.app')

@section('content')
    {{-- Post Header --}}
    <article class="w-full">
        {{-- Featured Image --}}
        @if($post->featured_image_url)
            <div class="w-full h-[300px] md:h-[500px] lg:h-[600px] relative">
                <img
                    src="{{ $post->featured_image_url }}"
                    alt="{{ $post->title }}"
                    class="absolute inset-0 w-full h-full object-cover object-center"
                />
            </div>
        @endif

        {{-- Post Content --}}
        <div class="container">
            <div class="bg-white px-5 lg:px-10 py-12 lg:py-16">
                <div class="max-w-3xl mx-auto">
                    {{-- Metadata Badge --}}
                    <div class="flex justify-center mb-6">
                        <x-content.metadata-badge
                            :category="$post->categories->first()?->name ?? ''"
                            :categoryUrl="$post->categories->first() ? route('category.show', $post->categories->first()->slug) : '#'"
                            :tag="$post->tags->first()?->name"
                            :tagUrl="$post->tags->first() ? route('tag.show', $post->tags->first()->slug) : '#'"
                        />
                    </div>

                    {{-- Title --}}
                    <x-content.title
                        :title="$post->title"
                        tag="h1"
                        size="xl"
                        align="center"
                    />

                    {{-- Subtitle --}}
                    @if($post->subtitle)
                        <p class="mt-4 text-lg text-gray-600 text-center">{{ $post->subtitle }}</p>
                    @endif

                    {{-- Author and Date --}}
                    <div class="flex justify-center mt-6">
                        <x-content.author-date
                            :author="$post->author?->name ?? ''"
                            authorUrl="#"
                            :date="$post->published_at?->format('M d, Y') ?? ''"
                            layout="horizontal"
                            align="center"
                        />
                    </div>

                    {{-- Excerpt --}}
                    @if($post->excerpt)
                        <div class="mt-8 text-lg text-gray-700 leading-relaxed text-center italic border-l-4 border-tasty-yellow pl-6 py-2">
                            {{ $post->excerpt }}
                        </div>
                    @endif

                    {{-- Content --}}
                    @if($post->content)
                        <div class="mt-10 prose prose-lg max-w-none">
                            @if(is_array($post->content))
                                @foreach($post->content as $block)
                                    @if(isset($block['type']) && $block['type'] === 'paragraph')
                                        <p>{!! $block['data']['text'] ?? '' !!}</p>
                                    @elseif(isset($block['type']) && $block['type'] === 'header')
                                        <h{{ $block['data']['level'] ?? 2 }}>{!! $block['data']['text'] ?? '' !!}</h{{ $block['data']['level'] ?? 2 }}>
                                    @elseif(isset($block['type']) && $block['type'] === 'list')
                                        @if(($block['data']['style'] ?? 'unordered') === 'ordered')
                                            <ol>
                                                @foreach($block['data']['items'] ?? [] as $item)
                                                    <li>{!! $item !!}</li>
                                                @endforeach
                                            </ol>
                                        @else
                                            <ul>
                                                @foreach($block['data']['items'] ?? [] as $item)
                                                    <li>{!! $item !!}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @elseif(isset($block['type']) && $block['type'] === 'quote')
                                        <blockquote>
                                            <p>{!! $block['data']['text'] ?? '' !!}</p>
                                            @if(!empty($block['data']['caption']))
                                                <cite>— {!! $block['data']['caption'] !!}</cite>
                                            @endif
                                        </blockquote>
                                    @elseif(isset($block['type']) && $block['type'] === 'image')
                                        <figure>
                                            <img src="{{ $block['data']['file']['url'] ?? $block['data']['url'] ?? '' }}" alt="{{ $block['data']['caption'] ?? '' }}" class="rounded-lg">
                                            @if(!empty($block['data']['caption']))
                                                <figcaption>{{ $block['data']['caption'] }}</figcaption>
                                            @endif
                                        </figure>
                                    @endif
                                @endforeach
                            @else
                                {!! $post->content !!}
                            @endif
                        </div>
                    @endif

                    {{-- Tags --}}
                    @if($post->tags->isNotEmpty())
                        <div class="mt-10 pt-6 border-t border-gray-200">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm text-gray-500 uppercase tracking-wide">Tags:</span>
                                @foreach($post->tags as $tag)
                                    <a href="{{ route('tag.show', $tag->slug) }}" class="inline-block bg-gray-100 hover:bg-tasty-yellow text-gray-700 text-sm px-3 py-1 rounded-full transition-colors">
                                        {{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </article>

    {{-- Related Posts --}}
    @if($relatedPosts->isNotEmpty())
        <div class="container">
            <div class="bg-tasty-off-white px-5 lg:px-10 py-12 lg:py-16">
                <x-ui.heading
                    level="h2"
                    text="Related Articles"
                    align="center"
                    class="mb-10"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    @foreach($relatedPosts as $relatedPost)
                        <x-cards.news-small
                            :image="$relatedPost->featured_image_url ?? '/images/placeholder.jpg'"
                            :imageAlt="$relatedPost->title"
                            :category="$relatedPost->categories->first()?->name ?? ''"
                            :categoryUrl="$relatedPost->categories->first() ? route('category.show', $relatedPost->categories->first()->slug) : '#'"
                            :tag="$relatedPost->tags->first()?->name"
                            :tagUrl="$relatedPost->tags->first() ? route('tag.show', $relatedPost->tags->first()->slug) : '#'"
                            :author="$relatedPost->author?->name ?? ''"
                            authorUrl="#"
                            :date="$relatedPost->published_at?->format('M d, Y') ?? ''"
                            :title="$relatedPost->title"
                            :description="$relatedPost->excerpt"
                            :articleUrl="route('post.show', [$relatedPost->categories->first()?->slug ?? 'uncategorized', $relatedPost->slug])"
                        />
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
