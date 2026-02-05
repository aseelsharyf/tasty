<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPostsByAuthor extends BasePostsAction
{
    /**
     * Get posts by author username.
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params = []): LengthAwarePaginator
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? $this->perPage;
        $excludeIds = $params['excludeIds'] ?? [];
        $authorUsername = $params['author'] ?? null;

        $query = Post::query()
            ->published()
            ->with(['author', 'categories', 'tags'])
            ->when($authorUsername, function ($q) use ($authorUsername) {
                $author = User::where('username', $authorUsername)->first();
                if ($author) {
                    $q->where('author_id', $author->id);
                }
            })
            ->when(count($excludeIds) > 0, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('published_at');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
