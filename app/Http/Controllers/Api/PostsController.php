<?php

namespace App\Http\Controllers\Api;

use App\Actions\Posts\BasePostsAction;
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
    ];

    /**
     * Load more posts based on action type.
     */
    public function loadMore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:recent,trending,byTag,byCategory',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:20',
            'excludeIds' => 'array',
            'excludeIds.*' => 'integer',
            'tag' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $actionClass = $this->actions[$validated['action']];
        /** @var BasePostsAction $action */
        $action = new $actionClass;

        $params = [
            'page' => $validated['page'] ?? 1,
            'perPage' => $validated['perPage'] ?? 4,
            'excludeIds' => $validated['excludeIds'] ?? [],
            'tag' => $validated['tag'] ?? null,
            'category' => $validated['category'] ?? null,
        ];

        return response()->json($action->getResponse($params));
    }
}
