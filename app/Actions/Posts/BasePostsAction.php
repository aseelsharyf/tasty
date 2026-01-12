<?php

namespace App\Actions\Posts;

use App\Contracts\PostsActionContract;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BasePostsAction implements PostsActionContract
{
    protected int $perPage = 4;

    /**
     * Transform posts to array format for JSON response.
     *
     * @return array<int, array<string, mixed>>
     */
    public function transform(LengthAwarePaginator $posts): array
    {
        return $posts->map(function (Post $post) {
            $categoryModel = $post->categories->first();
            $tagModel = $post->tags->first();

            return [
                'id' => $post->id,
                'image' => $post->featured_image_url ?? 'https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?w=400&h=400&fit=crop',
                'imageAlt' => $post->title,
                'blurhash' => $post->featured_image_blurhash,
                'category' => $categoryModel?->name,
                'categoryUrl' => $categoryModel ? route('category.show', $categoryModel->slug) : null,
                'tag' => $tagModel?->name,
                'tagUrl' => $tagModel ? route('tag.show', $tagModel->slug) : null,
                'kicker' => $post->kicker ?? '',
                'title' => $post->title,
                'description' => $post->excerpt ?? '',
                'author' => $post->author?->name ?? 'Unknown',
                'authorUrl' => $post->author?->url ?? '#',
                'date' => $post->published_at?->format('F j, Y') ?? '',
                'url' => $post->url,
            ];
        })->toArray();
    }

    /**
     * Get the response data including pagination info.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getResponse(array $params = []): array
    {
        $posts = $this->execute($params);

        return [
            'posts' => $this->transform($posts),
            'hasMore' => $posts->hasMorePages(),
            'nextPage' => $posts->hasMorePages() ? $posts->currentPage() + 1 : null,
            'total' => $posts->total(),
        ];
    }
}
