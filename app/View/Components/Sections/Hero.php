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
use Illuminate\View\Component;

class Hero extends Component
{
    use HasSectionCategoryRestrictions;
    use ResolvesColors;
    use TracksUsedPosts;

    protected function sectionType(): string
    {
        return 'hero';
    }

    public ?Post $post = null;

    public string $bgColorClass;

    public string $bgColorStyle;

    public string $buttonText;

    public string $buttonColor;

    public string $alignment;

    // Manual mode properties
    public bool $manual;

    public ?string $kicker;

    public ?string $title;

    public ?string $image;

    public ?string $imageAlt;

    public ?string $category;

    public ?string $categoryUrl;

    public ?string $author;

    public ?string $authorUrl;

    public ?string $date;

    public ?string $buttonUrl;

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
     * @param  string  $buttonColor  Button color variant (yellow, white)
     * @param  bool  $manual  Enable manual mode (pass content directly instead of fetching post)
     * @param  string|null  $kicker  Manual mode: large headline text
     * @param  string|null  $title  Manual mode: smaller title text
     * @param  string|null  $image  Manual mode: image URL
     * @param  string|null  $imageAlt  Manual mode: image alt text
     * @param  string|null  $category  Manual mode: category name
     * @param  string|null  $categoryUrl  Manual mode: category link URL
     * @param  string|null  $author  Manual mode: author name
     * @param  string|null  $authorUrl  Manual mode: author link URL
     * @param  string|null  $date  Manual mode: date string
     * @param  string|null  $buttonUrl  Manual mode: button link URL
     * @param  array<string, mixed>|null  $staticContent  Static content from CMS
     */
    public function __construct(
        ?int $postId = null,
        string $action = 'recent',
        array $params = [],
        string $bgColor = 'yellow',
        string $buttonText = 'Read More',
        string $buttonColor = 'white',
        string $alignment = 'center',
        bool $manual = false,
        ?string $kicker = null,
        ?string $title = null,
        ?string $image = null,
        ?string $imageAlt = null,
        ?string $category = null,
        ?string $categoryUrl = null,
        ?string $author = null,
        ?string $authorUrl = null,
        ?string $date = null,
        ?string $buttonUrl = null,
        ?array $staticContent = null,
    ) {
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->buttonText = $buttonText;
        $this->buttonColor = $buttonColor;
        $this->alignment = $alignment;

        // Static content from CMS (treated as manual mode)
        if ($staticContent !== null) {
            $this->manual = true;
            $this->kicker = $staticContent['kicker'] ?? null;
            $this->title = $staticContent['title'] ?? null;
            $this->image = $staticContent['image'] ?? null;
            $this->imageAlt = $staticContent['imageAlt'] ?? $staticContent['title'] ?? null;
            $this->category = $staticContent['category'] ?? null;
            $this->categoryUrl = $staticContent['categoryUrl'] ?? null;
            $this->author = $staticContent['author'] ?? null;
            $this->authorUrl = $staticContent['authorUrl'] ?? null;
            $this->date = $staticContent['date'] ?? null;
            $this->buttonUrl = $staticContent['buttonUrl'] ?? '#';

            return;
        }

        // Manual mode properties
        $this->manual = $manual;
        $this->kicker = $kicker;
        $this->title = $title;
        $this->image = $image;
        $this->imageAlt = $imageAlt ?? $title;
        $this->category = $category;
        $this->categoryUrl = $categoryUrl;
        $this->author = $author;
        $this->authorUrl = $authorUrl;
        $this->date = $date;
        $this->buttonUrl = $buttonUrl ?? '#';

        // Initialize post tracker to prevent duplicates across sections
        $this->initPostTracker();

        // Fetch post only if not in manual mode
        if (! $manual) {
            if ($postId !== null) {
                $this->post = Post::with(['author', 'categories', 'tags'])->find($postId);
            } else {
                $this->post = $this->fetchPostViaAction($action, $params);
            }
        }

        // Mark post as used so other sections don't show it
        $this->markPostUsed($this->post);
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
            'sectionType' => $this->sectionType(),
            'excludeIds' => $this->getExcludeIds(),
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
