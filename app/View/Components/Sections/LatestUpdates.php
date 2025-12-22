<?php

namespace App\View\Components\Sections;

use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Models\Post;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class LatestUpdates extends Component
{
    public string $introImage;

    public string $introImageAlt;

    public string $titleSmall;

    public string $titleLarge;

    public string $description;

    /** @var Post|array<string, mixed>|null */
    public Post|array|null $featuredPost;

    /** @var Collection<int, Post|array<string, mixed>> */
    public Collection $posts;

    public string $buttonText;

    public string $loadAction;

    /** @var array<string, mixed> */
    public array $loadParams;

    /** @var array<int, int> */
    public array $excludeIds;

    public bool $showLoadMore;

    /** @var array<string, class-string> */
    protected array $actions = [
        'recent' => GetRecentPosts::class,
        'trending' => GetTrendingPosts::class,
        'byTag' => GetPostsByTag::class,
        'byCategory' => GetPostsByCategory::class,
    ];

    /**
     * Create a new component instance.
     *
     * @param  int|null  $featuredPostId  ID to fetch a featured post
     * @param  array<int, int>  $postIds  IDs to fetch posts
     * @param  array<string, mixed>|null  $staticFeatured  Static data for featured post
     * @param  array<int, array<string, mixed>>  $staticPosts  Static data for posts
     * @param  array<string, mixed>  $loadParams
     */
    public function __construct(
        string $introImage = '',
        string $introImageAlt = '',
        string $titleSmall = 'Latest',
        string $titleLarge = 'Updates',
        string $description = '',
        ?int $featuredPostId = null,
        array $postIds = [],
        ?array $staticFeatured = null,
        array $staticPosts = [],
        string $buttonText = 'More Updates',
        string $loadAction = 'recent',
        array $loadParams = [],
        bool $autoFetch = false,
        int $featuredCount = 1,
        int $postsCount = 4,
        bool $showLoadMore = true,
    ) {
        $this->introImage = $introImage;
        $this->introImageAlt = $introImageAlt;
        $this->titleSmall = $titleSmall;
        $this->titleLarge = $titleLarge;
        $this->description = $description;
        $this->buttonText = $buttonText;
        $this->loadAction = $loadAction;
        $this->loadParams = $loadParams;
        $this->showLoadMore = $showLoadMore;

        // Static mode: use provided static data arrays
        if ($staticFeatured !== null || count($staticPosts) > 0) {
            $this->featuredPost = $staticFeatured;
            $this->posts = collect($staticPosts);
            $this->excludeIds = [];

            return;
        }

        // Auto-fetch posts using action class
        if ($autoFetch || ($featuredPostId === null && count($postIds) === 0)) {
            $this->fetchPostsViaAction($loadAction, $loadParams, $featuredCount, $postsCount);
        } else {
            // Fetch specific posts by ID
            $this->featuredPost = $featuredPostId
                ? Post::with(['author', 'categories', 'tags'])->find($featuredPostId)
                : null;

            $this->posts = count($postIds) > 0
                ? Post::with(['author', 'categories', 'tags'])
                    ->whereIn('id', $postIds)
                    ->get()
                    ->sortBy(fn ($post) => array_search($post->id, $postIds))
                    ->values()
                : collect();
        }

        $this->excludeIds = $this->computeExcludeIds();
    }

    /**
     * Fetch posts using an action class.
     *
     * @param  array<string, mixed>  $params
     */
    protected function fetchPostsViaAction(string $action, array $params, int $featuredCount, int $postsCount): void
    {
        $actionClass = $this->actions[$action] ?? GetRecentPosts::class;
        $actionInstance = new $actionClass;

        // Fetch enough posts for featured + regular posts
        $totalNeeded = $featuredCount + $postsCount;
        $result = $actionInstance->execute([
            'page' => 1,
            'perPage' => $totalNeeded,
            ...$params,
        ]);

        $allPosts = collect($result->items());

        // First post(s) become featured
        $this->featuredPost = $allPosts->first();

        // Remaining posts go to the grid
        $this->posts = $allPosts->skip($featuredCount)->take($postsCount)->values();
    }

    /**
     * Compute the IDs of initially loaded posts for exclusion.
     *
     * @return array<int, int>
     */
    protected function computeExcludeIds(): array
    {
        $ids = [];

        if ($this->featuredPost) {
            $ids[] = $this->featuredPost->id;
        }

        foreach ($this->posts as $post) {
            $ids[] = $post->id;
        }

        return $ids;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.latest-updates');
    }
}
