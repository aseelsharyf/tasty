<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class GetRecentPosts extends BasePostsAction
{
    /**
     * Get recent published posts.
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params = []): LengthAwarePaginator
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? $this->perPage;
        $excludeIds = $params['excludeIds'] ?? [];

        return Post::query()
            ->published()
            ->with(['author', 'categories', 'tags'])
            ->when(count($excludeIds) > 0, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('published_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
