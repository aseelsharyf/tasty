<?php

namespace App\View\Components\Sections;

use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Models\Post;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Hero extends Component
{
    use ResolvesColors;

    public ?Post $post;

    public string $bgColorClass;

    public string $bgColorStyle;

    public string $buttonText;

    public string $alignment;

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
     * @param  int|null  $postId  Specific post ID to display
     * @param  string  $action  Action to fetch post (recent, trending, byTag, byCategory)
     * @param  array<string, mixed>  $params  Parameters for the action (e.g., ['tag' => 'featured'])
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  string  $buttonText  Button text
     */
    public function __construct(
        ?int $postId = null,
        string $action = 'recent',
        array $params = [],
        string $bgColor = 'yellow',
        string $buttonText = 'Read More',
        string $alignment = 'center',
    ) {
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->buttonText = $buttonText;
        $this->alignment = $alignment;

        // Fetch post by ID or via action
        if ($postId !== null) {
            $this->post = Post::with(['author', 'categories', 'tags'])->find($postId);
        } else {
            $this->post = $this->fetchPostViaAction($action, $params);
        }
    }

    /**
     * Fetch a single post using an action class.
     *
     * @param  array<string, mixed>  $params
     */
    protected function fetchPostViaAction(string $action, array $params): ?Post
    {
        $actionClass = $this->actions[$action] ?? GetRecentPosts::class;
        $actionInstance = new $actionClass;

        $result = $actionInstance->execute([
            'page' => 1,
            'perPage' => 1,
            ...$params,
        ]);

        return $result->first();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.hero');
    }
}
