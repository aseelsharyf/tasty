<?php

namespace App\Http\Controllers\Api;

use App\Actions\Posts\BasePostsAction;
use App\Actions\Posts\GetPostsByAuthor;
use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostsController extends Controller
{
    /**
     * Map of action names to action classes.
     *
     * @var array<string, class-string<BasePostsAction>>
     */
    protected array $actions = [
        'recent' => GetRecentPosts::class,
        'trending' => GetTrendingPosts::class,
        'byTag' => GetPostsByTag::class,
        'byCategory' => GetPostsByCategory::class,
        'byAuthor' => GetPostsByAuthor::class,
    ];

    /**
     * Load more posts based on action type.
     */
    public function loadMore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:recent,trending,byTag,byCategory,byAuthor',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:20',
            'excludeIds' => 'array',
            'excludeIds.*' => 'integer',
            'tag' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'category' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
            'sectionType' => 'nullable|string|max:50',
            'author' => 'nullable|string|max:255',
        ]);

        $actionClass = $this->actions[$validated['action']];
        /** @var BasePostsAction $action */
        $action = new $actionClass;

        // Support both singular and plural formats for categories/tags
        $categories = $validated['categories'] ?? ($validated['category'] ? [$validated['category']] : null);
        $tags = $validated['tags'] ?? ($validated['tag'] ? [$validated['tag']] : null);

        $params = [
            'page' => $validated['page'] ?? 1,
            'perPage' => $validated['perPage'] ?? 4,
            'excludeIds' => $validated['excludeIds'] ?? [],
            'tags' => $tags,
            'categories' => $categories,
            'sectionType' => $validated['sectionType'] ?? null,
            'author' => $validated['author'] ?? null,
        ];

        return response()->json($action->getResponse($params));
    }
}
