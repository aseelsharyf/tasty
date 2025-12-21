<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPostsByTag extends BasePostsAction
{
    /**
     * Get posts by tag slug.
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params = []): LengthAwarePaginator
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? $this->perPage;
        $excludeIds = $params['excludeIds'] ?? [];
        $tagSlug = $params['tag'] ?? null;

        return Post::query()
            ->published()
            ->with(['author', 'categories', 'tags'])
            ->when($tagSlug, fn ($q) => $q->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug)))
            ->when(count($excludeIds) > 0, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('published_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
