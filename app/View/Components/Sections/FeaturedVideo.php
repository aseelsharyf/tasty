<?php

namespace App\View\Components\Sections;

use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Models\Post;
use App\View\Components\Sections\Concerns\HasSectionCategoryRestrictions;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FeaturedVideo extends Component
{
    use HasSectionCategoryRestrictions;

    protected function sectionType(): string
    {
        return 'featured-video';
    }

    public string $image;

    public string $imageAlt;

    public string $title;

    public string $subtitle;

    public string $description;

    public string $url;

    public string $videoUrl;

    public ?string $category;

    public ?string $categoryUrl;

    public ?string $tag;

    public ?string $tagUrl;

    public string $author;

    public string $authorUrl;

    public string $date;

    public string $buttonText;

    public string $overlayColor;

    public bool $showSectionGradient;

    public string $sectionGradientDirection;

    public ?string $sectionBgColor;

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
     * @param  array<string, mixed>|null  $staticData  Static data for the video card
     * @param  string  $action  Action to fetch post (recent, trending, byTag, byCategory)
     * @param  array<string, mixed>  $params  Parameters for the action
     * @param  string  $buttonText  Button text
     * @param  string  $overlayColor  Overlay gradient color (hex, rgb, or rgba)
     * @param  bool  $showSectionGradient  Whether to show the section background gradient
     * @param  string  $sectionGradientDirection  Section gradient direction (top or bottom)
     * @param  string|null  $sectionBgColor  Fixed background color (overrides gradient if set)
     * @param  array<string, mixed>|null  $staticContent  Static content from CMS
     */
    public function __construct(
        ?int $postId = null,
        ?array $staticData = null,
        string $action = 'recent',
        array $params = [],
        string $buttonText = 'Watch',
        string $overlayColor = '#FFE762',
        bool $showSectionGradient = true,
        string $sectionGradientDirection = 'top',
        ?string $sectionBgColor = null,
        ?array $staticContent = null,
    ) {
        $this->buttonText = $buttonText;
        $this->overlayColor = $overlayColor;
        $this->showSectionGradient = $showSectionGradient;
        $this->sectionGradientDirection = $sectionGradientDirection;
        $this->sectionBgColor = $sectionBgColor;

        // Static content from CMS (same as staticData)
        if ($staticContent !== null) {
            $this->image = $staticContent['image'] ?? '';
            $this->imageAlt = $staticContent['imageAlt'] ?? $staticContent['title'] ?? '';
            $this->title = $staticContent['title'] ?? '';
            $this->subtitle = $staticContent['subtitle'] ?? '';
            $this->description = $staticContent['description'] ?? '';
            $this->url = $staticContent['url'] ?? '#';
            $this->videoUrl = $staticContent['videoUrl'] ?? $staticContent['url'] ?? '#';
            $this->category = $staticContent['category'] ?? null;
            $this->categoryUrl = $staticContent['categoryUrl'] ?? null;
            $this->tag = $staticContent['tag'] ?? null;
            $this->tagUrl = $staticContent['tagUrl'] ?? null;
            $this->author = $staticContent['author'] ?? 'Unknown';
            $this->authorUrl = $staticContent['authorUrl'] ?? '#';
            $this->date = $staticContent['date'] ?? '';

            return;
        }

        // Static mode: use provided static data
        if ($staticData !== null) {
            $this->image = $staticData['image'] ?? '';
            $this->imageAlt = $staticData['imageAlt'] ?? $staticData['title'] ?? '';
            $this->title = $staticData['title'] ?? '';
            $this->subtitle = $staticData['subtitle'] ?? '';
            $this->description = $staticData['description'] ?? '';
            $this->url = $staticData['url'] ?? '#';
            $this->videoUrl = $staticData['videoUrl'] ?? $staticData['url'] ?? '#';
            $this->category = $staticData['category'] ?? null;
            $this->categoryUrl = $staticData['categoryUrl'] ?? null;
            $this->tag = $staticData['tag'] ?? null;
            $this->tagUrl = $staticData['tagUrl'] ?? null;
            $this->author = $staticData['author'] ?? 'Unknown';
            $this->authorUrl = $staticData['authorUrl'] ?? '#';
            $this->date = $staticData['date'] ?? '';

            return;
        }

        // Fetch post by ID or via action
        $post = null;
        if ($postId !== null) {
            $post = Post::with(['author', 'categories', 'tags'])->find($postId);
        } else {
            $post = $this->fetchPostViaAction($action, $params);
        }

        if ($post) {
            $categoryModel = $post->categories->first();
            $tagModel = $post->tags->first();

            $this->image = $post->featured_image_url ?? '';
            $this->imageAlt = $post->title;
            $this->title = $post->title;
            $this->subtitle = $post->subtitle ?? '';
            $this->description = $post->excerpt ?? '';
            $this->url = $post->url;
            $this->videoUrl = $post->video_url ?? $post->url;
            $this->category = $categoryModel?->name;
            $this->categoryUrl = $categoryModel ? route('category.show', $categoryModel->slug) : null;
            $this->tag = $tagModel?->name;
            $this->tagUrl = $tagModel ? route('tag.show', $tagModel->slug) : null;
            $this->author = $post->author?->name ?? 'Unknown';
            $this->authorUrl = $post->author?->url ?? '#';
            $this->date = $post->published_at?->format('F j, Y') ?? '';
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->title = '';
            $this->subtitle = '';
            $this->description = '';
            $this->url = '#';
            $this->videoUrl = '#';
            $this->category = null;
            $this->categoryUrl = null;
            $this->tag = null;
            $this->tagUrl = null;
            $this->author = 'Unknown';
            $this->authorUrl = '#';
            $this->date = '';
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
            ...$params,
        ]);

        return $result->first();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.featured-video');
    }
}
