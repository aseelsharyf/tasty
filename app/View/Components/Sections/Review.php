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

class Review extends Component
{
    public bool $showIntro;

    public string $introImage;

    public string $introImageAlt;

    public string $titleSmall;

    public string $titleLarge;

    public string $description;

    public string $mobileLayout;

    public bool $showDividers;

    public string $dividerColor;

    public string $buttonText;

    public string $loadAction;

    /** @var array<string, mixed> */
    public array $loadParams;

    /** @var array<int, int> */
    public array $excludeIds;

    public bool $showLoadMore;

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
     * @param  string  $mobileLayout  Mobile layout mode (scroll, grid)
     * @param  bool  $showDividers  Show dividers between cards
     * @param  string  $dividerColor  Divider color (white, gray, or Tailwind class)
     * @param  string  $buttonText  Load more button text
     * @param  bool  $showLoadMore  Whether to show load more button
     * @param  array<int, array<string, mixed>>  $staticPosts  Static post data for page builder
     * @param  array<int, int>  $postIds  Specific post IDs to display
     * @param  string  $action  Action to fetch posts
     * @param  array<string, mixed>  $params  Parameters for the action
     * @param  int  $count  Number of posts to fetch initially
     */
    public function __construct(
        bool $showIntro = true,
        string $introImage = '',
        string $introImageAlt = '',
        string $titleSmall = 'On the',
        string $titleLarge = 'MENU',
        string $description = '',
        string $mobileLayout = 'scroll',
        bool $showDividers = true,
        string $dividerColor = 'white',
        string $buttonText = 'More Reviews',
        bool $showLoadMore = true,
        array $staticPosts = [],
        array $postIds = [],
        string $action = 'recent',
        array $params = [],
        int $count = 5,
    ) {
        $this->showIntro = $showIntro;
        $this->introImage = $introImage;
        $this->introImageAlt = $introImageAlt;
        $this->titleSmall = $titleSmall;
        $this->titleLarge = $titleLarge;
        $this->description = $description;
        $this->mobileLayout = $mobileLayout;
        $this->showDividers = $showDividers;
        $this->dividerColor = str_starts_with($dividerColor, 'bg-') ? $dividerColor : ($dividerColor === 'white' ? 'bg-white' : 'bg-gray-300');
        $this->buttonText = $buttonText;
        $this->showLoadMore = $showLoadMore;
        $this->loadAction = $action;
        $this->loadParams = $params;

        // Static mode: use provided static data
        if (count($staticPosts) > 0) {
            $this->posts = collect($staticPosts);
            $this->excludeIds = [];
            $this->showLoadMore = false; // No load more for static data

            return;
        }

        // Fetch by post IDs
        if (count($postIds) > 0) {
            $this->posts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', $postIds)
                ->get()
                ->sortBy(fn ($post) => array_search($post->id, $postIds))
                ->values();
            $this->excludeIds = $postIds;

            return;
        }

        // Fetch via action
        $this->posts = $this->fetchPostsViaAction($action, $params, $count);
        $this->excludeIds = $this->computeExcludeIds();
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
     * Compute the IDs of initially loaded posts for exclusion.
     *
     * @return array<int, int>
     */
    protected function computeExcludeIds(): array
    {
        $ids = [];

        foreach ($this->posts as $post) {
            if ($post instanceof Post) {
                $ids[] = $post->id;
            }
        }

        return $ids;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.review');
    }
}
