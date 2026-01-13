<?php

namespace App\View\Components\Sections;

use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Models\Post;
use App\View\Components\Sections\Concerns\HasSectionCategoryRestrictions;
use App\View\Components\Sections\Concerns\TracksUsedPosts;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class LatestUpdates extends Component
{
    use HasSectionCategoryRestrictions;
    use TracksUsedPosts;

    protected function sectionType(): string
    {
        return 'latest-updates';
    }

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
     * @param  array<int, int>  $manualPostIds  Index => postId mapping for manually assigned slots
     * @param  array<int, array<string, mixed>>  $staticContent  Index => content mapping for static slots
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
        int $totalSlots = 0,
        array $manualPostIds = [],
        array $staticContent = [],
        int $dynamicCount = 0,
        string $action = 'recent',
        array $params = [],
    ) {
        // Initialize post tracker to prevent duplicates across sections
        $this->initPostTracker();

        $this->introImage = $introImage ?: \Illuminate\Support\Facades\Vite::asset('resources/images/latest-updates.png');
        $this->introImageAlt = $introImageAlt;
        $this->titleSmall = $titleSmall;
        $this->titleLarge = $titleLarge;
        $this->description = $description;
        $this->buttonText = $buttonText;
        $this->loadAction = $action ?: $loadAction;
        $this->loadParams = ! empty($params) ? $params : $loadParams;
        $this->showLoadMore = $showLoadMore;

        // New hybrid slot mode: mix of manual, static, and dynamic slots
        if ($totalSlots > 0 || count($manualPostIds) > 0 || count($staticContent) > 0) {
            $this->resolveHybridSlots(
                totalSlots: $totalSlots,
                manualPostIds: $manualPostIds,
                staticContent: $staticContent,
                dynamicCount: $dynamicCount,
                action: $action ?: $loadAction,
                params: ! empty($params) ? $params : $loadParams,
            );
            $this->excludeIds = $this->computeExcludeIds();
            $this->markSectionPostsUsed();

            return;
        }

        // Legacy: Static mode with static data arrays
        if ($staticFeatured !== null || count($staticPosts) > 0) {
            $this->featuredPost = $staticFeatured;
            $this->posts = collect($staticPosts);
            $this->excludeIds = [];

            return;
        }

        // Legacy: Auto-fetch posts using action class
        if ($autoFetch || ($featuredPostId === null && count($postIds) === 0)) {
            $this->fetchPostsViaAction($loadAction, $loadParams, $featuredCount, $postsCount);
        } else {
            // Legacy: Fetch specific posts by ID
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
        $this->markSectionPostsUsed();
    }

    /**
     * Mark all posts in this section as used.
     */
    protected function markSectionPostsUsed(): void
    {
        $this->markPostUsed($this->featuredPost);
        $this->markPostsUsed($this->posts);
    }

    /**
     * Resolve hybrid slots with mix of manual, static, and dynamic content.
     *
     * @param  array<int, int>  $manualPostIds
     * @param  array<int, array<string, mixed>>  $staticContent
     * @param  array<string, mixed>  $params
     */
    protected function resolveHybridSlots(
        int $totalSlots,
        array $manualPostIds,
        array $staticContent,
        int $dynamicCount,
        string $action,
        array $params,
    ): void {
        // Fetch manual posts and filter by allowed categories
        $manualPosts = collect();
        if (count($manualPostIds) > 0) {
            $manualPosts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', array_values($manualPostIds))
                ->get();

            $manualPosts = $this->filterAllowedPosts($manualPosts)->keyBy('id');
        }

        // Fetch dynamic posts if needed
        $dynamicPosts = collect();
        if ($dynamicCount > 0) {
            $actionClass = $this->actions[$action] ?? GetRecentPosts::class;
            $actionInstance = new $actionClass;

            // Exclude manual posts AND posts used by other sections
            $excludeIds = $this->getExcludeIds(array_values($manualPostIds));

            $result = $actionInstance->execute([
                'page' => 1,
                'perPage' => $dynamicCount,
                'excludeIds' => $excludeIds,
                'sectionType' => $this->sectionType(),
                ...$params,
            ]);

            $dynamicPosts = collect($result->items());
        }

        // Build final slot array
        $slots = [];
        $dynamicIndex = 0;

        for ($i = 0; $i < $totalSlots; $i++) {
            if (isset($manualPostIds[$i])) {
                // Manual post for this slot (only if allowed)
                $post = $manualPosts->get($manualPostIds[$i]);
                if ($post) {
                    $slots[$i] = $post;
                }
            } elseif (isset($staticContent[$i])) {
                // Static content for this slot
                $slots[$i] = $staticContent[$i];
            } else {
                // Dynamic post for this slot
                $slots[$i] = $dynamicPosts->get($dynamicIndex);
                $dynamicIndex++;
            }
        }

        // First slot is featured, rest are posts
        $this->featuredPost = $slots[0] ?? null;
        $this->posts = collect(array_slice($slots, 1, null, false))->filter()->values();
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
            'sectionType' => $this->sectionType(),
            'excludeIds' => $this->getExcludeIds(),
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
