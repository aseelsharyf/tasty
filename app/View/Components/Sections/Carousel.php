<?php

namespace App\View\Components\Sections;

use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Models\Post;
use App\View\Components\Sections\Concerns\HasSectionCategoryRestrictions;
use App\View\Components\Sections\Concerns\TracksUsedPosts;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Carousel extends Component
{
    use HasSectionCategoryRestrictions;
    use ResolvesColors;
    use TracksUsedPosts;

    protected function sectionType(): string
    {
        return 'carousel';
    }

    public string $bgColorClass;

    public string $bgColorStyle;

    public bool $showDividers;

    public string $dividerColor;

    public string $paddingTop;

    public string $paddingBottom;

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
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  bool  $showDividers  Show dividers between cards
     * @param  string  $dividerColor  Divider color (white, gray, or Tailwind class)
     * @param  string  $paddingTop  Top padding (none, small, medium, large)
     * @param  string  $paddingBottom  Bottom padding (none, small, medium, large)
     * @param  string  $action  Action to fetch posts
     * @param  array<string, mixed>  $params  Parameters for the action
     * @param  int  $totalSlots  Total number of slots from CMS
     * @param  array<int, int>  $manualPostIds  Index => postId for manual slots
     * @param  array<int, array<string, mixed>>  $staticContent  Index => content for static slots
     * @param  int  $dynamicCount  Number of dynamic slots to fill
     */
    public function __construct(
        string $bgColor = 'yellow',
        bool $showDividers = true,
        string $dividerColor = 'white',
        string $paddingTop = 'none',
        string $paddingBottom = 'medium',
        string $action = 'recent',
        array $params = [],
        int $totalSlots = 0,
        array $manualPostIds = [],
        array $staticContent = [],
        int $dynamicCount = 0,
    ) {
        $this->initPostTracker();

        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->showDividers = $showDividers;
        $this->dividerColor = str_starts_with($dividerColor, 'bg-') ? $dividerColor : ($dividerColor === 'white' ? 'bg-white' : 'bg-gray-300');
        $this->paddingTop = $paddingTop;
        $this->paddingBottom = $paddingBottom;

        if ($totalSlots > 0 || count($manualPostIds) > 0 || count($staticContent) > 0) {
            $this->posts = $this->resolveHybridSlots(
                totalSlots: $totalSlots,
                manualPostIds: $manualPostIds,
                staticContent: $staticContent,
                dynamicCount: $dynamicCount,
                action: $action,
                params: $params,
            );
            $this->markPostsUsed($this->posts);

            return;
        }

        // Fallback: fetch default posts
        $this->posts = $this->fetchPostsViaAction($action, $params, 5);
        $this->markPostsUsed($this->posts);
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
        $manualPosts = collect();
        $validManualIds = array_filter(array_values($manualPostIds));

        if (count($validManualIds) > 0) {
            $manualPosts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', $validManualIds)
                ->get();

            $manualPosts = $this->filterAllowedPosts($manualPosts)->keyBy('id');
        }

        $validManualCount = $manualPosts->count();
        $staticCount = count($staticContent);
        $neededDynamicCount = $totalSlots - $validManualCount - $staticCount;

        $dynamicPosts = collect();
        if ($neededDynamicCount > 0) {
            $actionClass = $this->actions[$action] ?? GetRecentPosts::class;
            $actionInstance = new $actionClass;

            $excludeIds = $this->getExcludeIds($validManualIds);

            $result = $actionInstance->execute([
                'page' => 1,
                'perPage' => $neededDynamicCount,
                'excludeIds' => $excludeIds,
                'sectionType' => $this->sectionType(),
                ...$params,
            ]);

            $dynamicPosts = collect($result->items());
        }

        $slots = [];
        $dynamicIndex = 0;

        for ($i = 0; $i < $totalSlots; $i++) {
            if (isset($manualPostIds[$i]) && $manualPosts->has($manualPostIds[$i])) {
                $slots[$i] = $manualPosts->get($manualPostIds[$i]);
            } elseif (isset($staticContent[$i])) {
                $slots[$i] = $staticContent[$i];
            } else {
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
            'sectionType' => $this->sectionType(),
            'excludeIds' => $this->getExcludeIds(),
            ...$params,
        ]);

        return collect($result->items());
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.carousel');
    }
}
