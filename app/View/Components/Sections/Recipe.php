<?php

namespace App\View\Components\Sections;

use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Models\Post;
use App\View\Components\Sections\Concerns\HasSectionCategoryRestrictions;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Recipe extends Component
{
    use HasSectionCategoryRestrictions;
    use ResolvesColors;

    protected function sectionType(): string
    {
        return 'recipe';
    }

    public bool $showIntro;

    public string $introImage;

    public string $introImageAlt;

    public string $titleSmall;

    public string $titleLarge;

    public string $description;

    public string $bgColorClass;

    public string $bgColorStyle;

    public string $gradientDirection;

    public string $mobileLayout;

    public bool $showDividers;

    public string $dividerColor;

    /** @var Collection<int, Post|array<string, mixed>> */
    public Collection $posts;

    /** @var Post|array<string, mixed>|null */
    public mixed $featuredPost;

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
     * @param  string  $gradient  Gradient direction: 'top', 'bottom', or 'none'
     * @param  string  $mobileLayout  Mobile layout mode (scroll, grid)
     * @param  bool  $showDividers  Show dividers between cards
     * @param  string  $dividerColor  Divider color (white, gray, or Tailwind class)
     * @param  array<int, array<string, mixed>>  $staticPosts  Static post data for page builder
     * @param  array<string, mixed>|null  $staticFeatured  Static featured post data
     * @param  array<int, int>  $postIds  Specific post IDs to display
     * @param  int|null  $featuredPostId  Specific featured post ID
     * @param  string  $action  Action to fetch posts
     * @param  array<string, mixed>  $params  Parameters for the action
     * @param  int  $count  Number of posts to fetch (excluding featured)
     * @param  int  $totalSlots  Total number of slots from CMS
     * @param  array<int, int>  $manualPostIds  Index => postId for manual slots
     * @param  array<int, array<string, mixed>>  $staticContent  Index => content for static slots
     * @param  int  $dynamicCount  Number of dynamic slots to fill
     */
    public function __construct(
        bool $showIntro = true,
        string $introImage = '',
        string $introImageAlt = '',
        string $titleSmall = 'Everyday',
        string $titleLarge = 'COOKING',
        string $description = '',
        string $bgColor = 'yellow',
        string $gradient = 'top',
        string $mobileLayout = 'grid',
        bool $showDividers = false,
        string $dividerColor = 'white',
        array $staticPosts = [],
        ?array $staticFeatured = null,
        array $postIds = [],
        ?int $featuredPostId = null,
        string $action = 'recent',
        array $params = [],
        int $count = 4,
        int $totalSlots = 0,
        array $manualPostIds = [],
        array $staticContent = [],
        int $dynamicCount = 0,
    ) {
        $this->showIntro = $showIntro;
        $this->introImage = $introImage ?: \Illuminate\Support\Facades\Vite::asset('resources/images/image-30.png');
        $this->introImageAlt = $introImageAlt;
        $this->titleSmall = $titleSmall;
        $this->titleLarge = $titleLarge;
        $this->description = $description;
        $this->gradientDirection = $gradient;

        // Build background style with gradient
        if ($gradient !== 'none') {
            $gradientColor = $this->resolveHexColor($bgColor);
            $direction = $gradient === 'bottom' ? '0deg' : '180deg';
            $this->bgColorClass = '';
            $this->bgColorStyle = "background: linear-gradient({$direction}, {$gradientColor} 0%, {$this->hexToRgba($gradientColor, 0.5)} 20%, {$this->hexToRgba($gradientColor, 0)} 40%), #FFF;";
        } else {
            $bgResolved = $this->resolveBgColor($bgColor);
            $this->bgColorClass = $bgResolved['class'];
            $this->bgColorStyle = $bgResolved['style'];
        }

        $this->mobileLayout = $mobileLayout;
        $this->showDividers = $showDividers;
        $this->dividerColor = str_starts_with($dividerColor, 'bg-') ? $dividerColor : ($dividerColor === 'white' ? 'bg-white' : 'bg-gray-300');

        // New hybrid slot mode from CMS
        if ($totalSlots > 0 || count($manualPostIds) > 0 || count($staticContent) > 0) {
            $slots = $this->resolveHybridSlots(
                totalSlots: $totalSlots,
                manualPostIds: $manualPostIds,
                staticContent: $staticContent,
                dynamicCount: $dynamicCount,
                action: $action,
                params: $params,
            );

            // First slot is featured, rest are posts
            $this->featuredPost = $slots->shift();
            $this->posts = $slots;

            return;
        }

        // Legacy: Handle featured post
        if ($staticFeatured !== null) {
            $this->featuredPost = $staticFeatured;
        } elseif ($featuredPostId !== null) {
            $this->featuredPost = Post::with(['author', 'categories', 'tags'])->find($featuredPostId);
        } else {
            $this->featuredPost = null;
        }

        // Legacy: Handle regular posts
        $staticCollection = collect($staticPosts);

        if (count($staticPosts) > 0 && $count === 0) {
            $this->posts = $staticCollection;

            return;
        }

        if (count($postIds) > 0) {
            $dynamicPosts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', $postIds)
                ->get()
                ->sortBy(fn ($post) => array_search($post->id, $postIds))
                ->values();
            $this->posts = $staticCollection->merge($dynamicPosts);
        } else {
            $dynamicPosts = $this->fetchPostsViaAction($action, $params, $count);

            // If no explicit featured post, use first dynamic post as featured
            if ($this->featuredPost === null && $dynamicPosts->isNotEmpty()) {
                $this->featuredPost = $dynamicPosts->shift();
            }

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

            // Exclude manual posts from dynamic fetch
            $excludeIds = array_values($manualPostIds);

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

        // Fetch one extra for featured if needed
        $fetchCount = $this->featuredPost === null ? $count + 1 : $count;

        $result = $actionInstance->execute([
            'page' => 1,
            'perPage' => $fetchCount,
            'sectionType' => $this->sectionType(),
            ...$params,
        ]);

        return collect($result->items());
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.recipe');
    }
}
