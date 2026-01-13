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

class FeaturedPerson extends Component
{
    use HasSectionCategoryRestrictions;
    use ResolvesColors;
    use TracksUsedPosts;

    protected function sectionType(): string
    {
        return 'featured-person';
    }

    /** @var Post|array<string, mixed>|null */
    public Post|array|null $post;

    public string $bgColorClass;

    public string $bgColorStyle;

    public string $buttonText;

    public string $tag1;

    public ?string $tag1Slug;

    public string $tag2;

    public ?string $tag2Slug;

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
     * @param  array<string, mixed>|null  $staticData  Static data for the person
     * @param  string  $action  Action to fetch post (recent, trending, byTag, byCategory)
     * @param  array<string, mixed>  $params  Parameters for the action
     * @param  string  $bgColor  Background color for content area (named, Tailwind class, hex, or rgba)
     * @param  string  $buttonText  Button text
     * @param  string  $tag1  First tag text
     * @param  string  $tag2  Second tag text (or uses category)
     * @param  array<string, mixed>|null  $staticContent  Static content from CMS
     */
    public function __construct(
        ?int $postId = null,
        ?array $staticData = null,
        string $action = 'byTag',
        array $params = ['tag' => 'featured'],
        string $bgColor = 'yellow',
        string $buttonText = 'Read More',
        string $tag1 = 'TASTY FEATURE',
        string $tag2 = '',
        ?array $staticContent = null,
    ) {
        // Initialize post tracker to prevent duplicates across sections
        $this->initPostTracker();

        // Resolve background color
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];

        $this->buttonText = $buttonText;
        $this->tag1 = $tag1;
        $this->tag1Slug = 'featured';
        $this->tag2 = $tag2;
        $this->tag2Slug = null;

        // Static content from CMS (same as staticData)
        if ($staticContent !== null) {
            $this->post = $staticContent;

            if (empty($this->tag2) && isset($staticContent['category'])) {
                $this->tag2 = $staticContent['category'];
            }
            if (isset($staticContent['categorySlug'])) {
                $this->tag2Slug = $staticContent['categorySlug'];
            }

            return;
        }

        // Static mode: use provided static data
        if ($staticData !== null) {
            $this->post = $staticData;

            // Use tag2 from static data if not provided
            if (empty($this->tag2) && isset($staticData['category'])) {
                $this->tag2 = $staticData['category'];
            }
            if (isset($staticData['categorySlug'])) {
                $this->tag2Slug = $staticData['categorySlug'];
            }

            return;
        }

        // Fetch post by ID or via action
        if ($postId !== null) {
            $this->post = Post::with(['author', 'categories', 'tags'])->find($postId);
        } else {
            $this->post = $this->fetchPostViaAction($action, $params);
        }

        // Use category name and slug as tag2 if not provided
        if ($this->post instanceof Post) {
            $category = $this->post->categories->first();
            if (empty($this->tag2)) {
                $this->tag2 = $category?->name ?? 'PEOPLE';
            }
            $this->tag2Slug = $category?->slug;

            // Mark post as used so other sections don't show it
            $this->markPostUsed($this->post);
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
        return view('components.sections.featured-person');
    }
}
