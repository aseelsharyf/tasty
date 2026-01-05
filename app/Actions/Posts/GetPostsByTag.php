<?php

namespace App\Actions\Posts;

use App\Actions\Posts\Concerns\FiltersBySectionCategories;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPostsByTag extends BasePostsAction
{
    use FiltersBySectionCategories;

    /**
     * Get posts by tag slug(s).
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params = []): LengthAwarePaginator
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? $this->perPage;
        $excludeIds = $params['excludeIds'] ?? [];
        $sectionType = $params['sectionType'] ?? null;

        // Support both single tag and multiple tags
        $tagSlugs = $params['tags'] ?? [];
        if (isset($params['tag'])) {
            $tagSlugs = [$params['tag']];
        }

        $query = Post::query()
            ->published()
            ->with(['author', 'categories', 'tags'])
            ->when(count($tagSlugs) > 0, fn ($q) => $q->whereHas(
                'tags',
                fn ($q) => $q->whereIn('slug', $tagSlugs)
            ))
            ->when(count($excludeIds) > 0, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('published_at');

        $query = $this->applySectionCategoryFilter($query, $sectionType);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
