@extends('layouts.app')

@section('content')
    {{-- Category Header --}}
    <div class="w-full bg-tasty-off-white">
        <div class="container px-5 lg:px-10 py-16 lg:py-24">
            <div class="text-center">
                <x-ui.heading
                    level="h1"
                    :text="$category->name"
                    align="center"
                />
                @if($category->description)
                    <p class="mt-4 text-gray-600 max-w-2xl mx-auto">{{ $category->description }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Posts Grid --}}
    <div class="container">
        <div class="bg-tasty-off-white px-5 lg:px-10 pb-16 lg:pb-24">
            @if($posts->isEmpty())
                <div class="text-center py-16">
                    <p class="text-gray-500 text-lg">No posts found in this category.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    @foreach($posts as $post)
                        <x-cards.news-small
                            :image="$post->featured_image_url ?? '/images/placeholder.jpg'"
                            :imageAlt="$post->title"
                            :category="$post->categories->first()?->name ?? ''"
                            :categoryUrl="$post->categories->first() ? route('category.show', $post->categories->first()->slug) : '#'"
                            :tag="$post->tags->first()?->name"
                            :tagUrl="$post->tags->first() ? route('tag.show', $post->tags->first()->slug) : '#'"
                            :author="$post->author?->name ?? ''"
                            authorUrl="#"
                            :date="$post->published_at?->format('M d, Y') ?? ''"
                            :title="$post->title"
                            :description="$post->excerpt"
                            :articleUrl="route('post.show', [$post->categories->first()?->slug ?? 'uncategorized', $post->slug])"
                        />
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($posts->hasPages())
                    <div class="mt-12 lg:mt-16">
                        <x-ui.pagination :paginator="$posts" />
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
