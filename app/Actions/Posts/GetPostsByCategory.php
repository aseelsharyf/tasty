<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPostsByCategory extends BasePostsAction
{
    /**
     * Get posts by category slug.
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params = []): LengthAwarePaginator
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? $this->perPage;
        $excludeIds = $params['excludeIds'] ?? [];
        $categorySlug = $params['category'] ?? null;

        return Post::query()
            ->published()
            ->with(['author', 'categories', 'tags'])
            ->when($categorySlug, fn ($q) => $q->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug)))
            ->when(count($excludeIds) > 0, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('published_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
