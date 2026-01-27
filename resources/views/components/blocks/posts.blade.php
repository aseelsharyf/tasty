{{-- resources/views/components/blocks/posts.blade.php --}}
{{-- EditorJS Posts Block - Renders linked post cards using x-cards.horizontal --}}

@props([
    'posts' => [],
    'layout' => 'grid', // grid, scroll
    'isRtl' => false,
])

@php
    $posts = is_array($posts) ? $posts : [];

    // Fetch Post models for all posts in the block
    $postModels = collect();
    foreach ($posts as $post) {
        $postId = is_numeric($post) ? $post : ($post['id'] ?? null);
        if ($postId) {
            $postModel = \App\Models\Post::with(['author', 'featuredTag', 'featuredMedia', 'categories'])->find($postId);
            if ($postModel) {
                $postModels->push($postModel);
            }
        }
    }
@endphp

@if($postModels->count() > 0)
    <div class="w-full bg-tasty-light-gray py-12">
        @if($layout === 'scroll')
            {{-- Horizontal Scroll Layout --}}
            <div class="overflow-x-auto scrollbar-hide">
                <div class="flex gap-6 px-4 lg:px-10" style="width: max-content;">
                    @foreach($postModels as $postModel)
                        <div class="w-[400px] lg:w-[500px] flex-shrink-0 [&>article]:h-full">
                            <x-cards.horizontal :post="$postModel" />
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Grid Layout (default) --}}
            <div class="max-w-[1440px] mx-auto px-4 lg:px-10 grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($postModels as $postModel)
                    <x-cards.horizontal :post="$postModel" />
                @endforeach
            </div>
        @endif
    </div>
@endif
