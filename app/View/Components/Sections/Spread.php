<?php

namespace App\View\Components\Sections;

use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Models\Post;
use App\Models\Tag;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Spread extends Component
{
    use ResolvesColors;

    public bool $showIntro;

    public string $introImage;

    public string $introImageAlt;

    public string $titleSmall;

    public string $titleLarge;

    public string $description;

    public string $bgColorClass;

    public string $bgColorStyle;

    public string $mobileLayout;

    public bool $showDividers;

    public string $dividerColor;

    /** @var Collection<int, Post|array<string, mixed>> */
    public Collection $posts;

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
     * @param  bool  $showIntro  Whether to show the intro card
     * @param  string  $introImage  Intro section image
     * @param  string  $introImageAlt  Intro image alt text
     * @param  string  $titleSmall  Small title text
     * @param  string  $titleLarge  Large title text
     * @param  string  $description  Intro description
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  string  $mobileLayout  Mobile layout mode (scroll, grid)
     * @param  bool  $showDividers  Show dividers between cards
     * @param  string  $dividerColor  Divider color (white, gray, or Tailwind class)
     * @param  array<int, array<string, mixed>>  $staticPosts  Static post data for page builder
     * @param  array<int, int>  $postIds  Specific post IDs to display
     * @param  string  $action  Action to fetch posts
     * @param  array<string, mixed>  $params  Parameters for the action
     * @param  int  $count  Number of posts to fetch
     * @param  bool  $randomTag  Pick a random tag and load posts from it
     * @param  int  $totalSlots  Total number of slots from CMS
     * @param  array<int, int>  $manualPostIds  Index => postId for manual slots
     * @param  array<int, array<string, mixed>>  $staticContent  Index => content for static slots
     * @param  int  $dynamicCount  Number of dynamic slots to fill
     */
    public function __construct(
        bool $showIntro = true,
        string $introImage = '',
        string $introImageAlt = '',
        string $titleSmall = 'The',
        string $titleLarge = 'SPREAD',
        string $description = '',
        string $bgColor = 'yellow',
        string $mobileLayout = 'scroll',
        bool $showDividers = true,
        string $dividerColor = 'white',
        array $staticPosts = [],
        array $postIds = [],
        string $action = 'recent',
        array $params = [],
        int $count = 4,
        bool $randomTag = false,
        int $totalSlots = 0,
        array $manualPostIds = [],
        array $staticContent = [],
        int $dynamicCount = 0,
    ) {
        $this->showIntro = $showIntro;
        $this->introImage = $introImage;
        $this->introImageAlt = $introImageAlt;
        $this->titleSmall = $titleSmall;
        $this->titleLarge = $titleLarge;
        $this->description = $description;
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->mobileLayout = $mobileLayout;
        $this->showDividers = $showDividers;
        $this->dividerColor = str_starts_with($dividerColor, 'bg-') ? $dividerColor : ($dividerColor === 'white' ? 'bg-white' : 'bg-gray-300');

        // New hybrid slot mode from CMS
        if ($totalSlots > 0 || count($manualPostIds) > 0 || count($staticContent) > 0) {
            $this->posts = $this->resolveHybridSlots(
                totalSlots: $totalSlots,
                manualPostIds: $manualPostIds,
                staticContent: $staticContent,
                dynamicCount: $dynamicCount,
                action: $action,
                params: $params,
            );

            return;
        }

        // Legacy: Static posts can be mixed with dynamic posts
        $staticCollection = collect($staticPosts);

        // If only static posts provided (no action specified or count is 0 after static)
        if (count($staticPosts) > 0 && $count === 0) {
            $this->posts = $staticCollection;

            return;
        }

        // Handle random tag mode
        if ($randomTag) {
            $tag = Tag::query()->whereHas('posts')->inRandomOrder()->first();

            if ($tag) {
                $this->titleSmall = 'Tagged';
                $this->titleLarge = strtoupper($tag->name);
                $this->description = $description ?: "Explore our latest posts tagged with {$tag->name}.";
                $dynamicPosts = $this->fetchPostsViaAction('byTag', ['slug' => $tag->slug], $count);
                $this->posts = $staticCollection->merge($dynamicPosts);
            } else {
                // Fallback if no tags with posts exist
                $dynamicPosts = $this->fetchPostsViaAction('recent', [], $count);
                $this->posts = $staticCollection->merge($dynamicPosts);
            }
        } elseif (count($postIds) > 0) {
            $dynamicPosts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', $postIds)
                ->get()
                ->sortBy(fn ($post) => array_search($post->id, $postIds))
                ->values();
            $this->posts = $staticCollection->merge($dynamicPosts);
        } else {
            $dynamicPosts = $this->fetchPostsViaAction($action, $params, $count);
            $this->posts = $staticCollection->merge($dynamicPosts);
        }
    }

    /**
     * Resolve hybrid slots with mix of manual, static, and dynamic content.
     *
     * @param  array<int, int>  $manualPostIds
     * @param  array<int, array<string, mixed>>  $staticContent
     * @param  array<string, mixed>  $params
     * @return Collection<int, Post|array<string, mixed>>
     */
    protected function resolveHybridSlots(
        int $totalSlots,
        array $manualPostIds,
        array $staticContent,
        int $dynamicCount,
        string $action,
        array $params,
    ): Collection {
        // Fetch manual posts
        $manualPosts = collect();
        if (count($manualPostIds) > 0) {
            $manualPosts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', array_values($manualPostIds))
                ->get()
                ->keyBy('id');
        }

        // Fetch dynamic posts if needed
        $dynamicPosts = collect();
        if ($dynamicCount > 0) {
            $actionClass = $this->actions[$action] ?? GetRecentPosts::class;
            $actionInstance = new $actionClass;

            // Exclude manual posts from dynamic fetch
            $excludeIds = array_values($manualPostIds);

            $result = $actionInstance->execute([
                'page' => 1,
                'perPage' => $dynamicCount,
                'excludeIds' => $excludeIds,
                ...$params,
            ]);

            $dynamicPosts = collect($result->items());
        }

        // Build final slot array
        $slots = [];
        $dynamicIndex = 0;

        for ($i = 0; $i < $totalSlots; $i++) {
            if (isset($manualPostIds[$i])) {
                // Manual post for this slot
                $slots[$i] = $manualPosts->get($manualPostIds[$i]);
            } elseif (isset($staticContent[$i])) {
                // Static content for this slot
                $slots[$i] = $staticContent[$i];
            } else {
                // Dynamic post for this slot
                $slots[$i] = $dynamicPosts->get($dynamicIndex);
                $dynamicIndex++;
            }
        }

        return collect($slots)->filter()->values();
    }

    /**
     * Fetch posts using an action class.
     *
     * @param  array<string, mixed>  $params
     * @return Collection<int, Post>
     */
    protected function fetchPostsViaAction(string $action, array $params, int $count): Collection
    {
        $actionClass = $this->actions[$action] ?? GetRecentPosts::class;
        $actionInstance = new $actionClass;

        $result = $actionInstance->execute([
            'page' => 1,
            'perPage' => $count,
            ...$params,
        ]);

        return collect($result->items());
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.spread');
    }
}
