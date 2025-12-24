<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPostsByCategory extends BasePostsAction
{
    /**
     * Get posts by category slug(s).
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params = []): LengthAwarePaginator
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? $this->perPage;
        $excludeIds = $params['excludeIds'] ?? [];

        // Support both single category and multiple categories
        $categorySlugs = $params['categories'] ?? [];
        if (isset($params['category'])) {
            $categorySlugs = [$params['category']];
        }

        return Post::query()
            ->published()
            ->with(['author', 'categories', 'tags'])
            ->when(count($categorySlugs) > 0, fn ($q) => $q->whereHas(
                'categories',
                fn ($q) => $q->whereIn('slug', $categorySlugs)
            ))
            ->when(count($excludeIds) > 0, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('published_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
