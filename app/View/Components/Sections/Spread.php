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

    /** @var Collection<int, Post> */
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
     * @param  string  $introImage  Intro section image
     * @param  string  $introImageAlt  Intro image alt text
     * @param  string  $titleSmall  Small title text
     * @param  string  $titleLarge  Large title text
     * @param  string  $description  Intro description
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  string  $mobileLayout  Mobile layout mode (scroll, grid)
     * @param  bool  $showDividers  Show dividers between cards
     * @param  string  $dividerColor  Divider color (white, gray, or Tailwind class)
     * @param  array<int, int>  $postIds  Specific post IDs to display
     * @param  string  $action  Action to fetch posts
     * @param  array<string, mixed>  $params  Parameters for the action
     * @param  int  $count  Number of posts to fetch
     * @param  bool  $randomTag  Pick a random tag and load posts from it
     */
    public function __construct(
        string $introImage = '',
        string $introImageAlt = '',
        string $titleSmall = 'The',
        string $titleLarge = 'SPREAD',
        string $description = '',
        string $bgColor = 'yellow',
        string $mobileLayout = 'scroll',
        bool $showDividers = true,
        string $dividerColor = 'white',
        array $postIds = [],
        string $action = 'recent',
        array $params = [],
        int $count = 4,
        bool $randomTag = false,
    ) {
        $this->introImage = $introImage;
        $this->introImageAlt = $introImageAlt;
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->mobileLayout = $mobileLayout;
        $this->showDividers = $showDividers;
        $this->dividerColor = str_starts_with($dividerColor, 'bg-') ? $dividerColor : ($dividerColor === 'white' ? 'bg-white' : 'bg-gray-300');

        // Handle random tag mode
        if ($randomTag) {
            $tag = Tag::query()->whereHas('posts')->inRandomOrder()->first();

            if ($tag) {
                $this->titleSmall = 'Tagged';
                $this->titleLarge = strtoupper($tag->name);
                $this->description = $description ?: "Explore our latest posts tagged with {$tag->name}.";
                $this->posts = $this->fetchPostsViaAction('byTag', ['slug' => $tag->slug], $count);
            } else {
                // Fallback if no tags with posts exist
                $this->titleSmall = $titleSmall;
                $this->titleLarge = $titleLarge;
                $this->description = $description;
                $this->posts = $this->fetchPostsViaAction('recent', [], $count);
            }
        } elseif (count($postIds) > 0) {
            $this->titleSmall = $titleSmall;
            $this->titleLarge = $titleLarge;
            $this->description = $description;
            $this->posts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', $postIds)
                ->get()
                ->sortBy(fn ($post) => array_search($post->id, $postIds))
                ->values();
        } else {
            $this->titleSmall = $titleSmall;
            $this->titleLarge = $titleLarge;
            $this->description = $description;
            $this->posts = $this->fetchPostsViaAction($action, $params, $count);
        }
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
