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

class FeaturedLocation extends Component
{
    use ResolvesColors;

    /** @var Post|array<string, mixed>|null */
    public Post|array|null $post;

    public string $image;

    public string $imageAlt;

    public string $kicker;

    public string $title;

    public string $tag1;

    public ?string $tag1Slug;

    public string $tag2;

    public ?string $tag2Slug;

    public string $description;

    public string $buttonText;

    public string $buttonUrl;

    public string $bgColorClass;

    public string $bgColorStyle;

    public string $textColor;

    public string $buttonVariant;

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
     * @param  Post|array<string, mixed>|null  $post  Post model or static data array
     * @param  int|null  $postId  Specific post ID to display
     * @param  string  $action  Action to fetch post (recent, trending, byTag, byCategory)
     * @param  array<string, mixed>  $params  Parameters for the action
     * @param  string|null  $image  Override image URL
     * @param  string|null  $imageAlt  Override image alt text
     * @param  string|null  $kicker  Large title (e.g., "CEYLON") - uses post kicker field
     * @param  string|null  $title  Subtitle below kicker - uses post title field
     * @param  string|null  $tag1  Override first tag
     * @param  string|null  $tag2  Override second tag
     * @param  string|null  $description  Override description
     * @param  string|null  $buttonText  Override button text
     * @param  string|null  $buttonUrl  Override button URL
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  string  $textColor  Text color (Tailwind utility name like 'blue-black')
     * @param  string  $buttonVariant  Button variant (white, yellow)
     */
    public function __construct(
        Post|array|null $post = null,
        ?int $postId = null,
        string $action = 'byTag',
        array $params = ['tag' => 'destinations'],
        ?string $image = null,
        ?string $imageAlt = null,
        ?string $kicker = null,
        ?string $title = null,
        ?string $tag1 = null,
        ?string $tag2 = null,
        ?string $description = null,
        ?string $buttonText = null,
        ?string $buttonUrl = null,
        string $bgColor = 'yellow',
        string $textColor = 'blue-black',
        string $buttonVariant = 'white',
    ) {
        // Resolve background color
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->textColor = $textColor;
        $this->buttonVariant = $buttonVariant;

        // Determine post source
        if ($post instanceof Post) {
            $this->post = $post;
            $this->populateFromPost($post);
        } elseif (is_array($post)) {
            // Static array data
            $this->post = $post;
            $this->populateFromArray($post);
        } elseif ($postId !== null) {
            // Fetch by specific ID
            $this->post = Post::with(['author', 'categories', 'tags'])->find($postId);
            if ($this->post) {
                $this->populateFromPost($this->post);
            } else {
                $this->setDefaults();
            }
        } else {
            // Fetch via action
            $this->post = $this->fetchPostViaAction($action, $params);
            if ($this->post) {
                $this->populateFromPost($this->post);
            } else {
                $this->setDefaults();
            }
        }

        // Allow individual prop overrides
        if ($image !== null) {
            $this->image = $image;
        }
        if ($imageAlt !== null) {
            $this->imageAlt = $imageAlt;
        }
        if ($kicker !== null) {
            $this->kicker = $kicker;
        }
        if ($title !== null) {
            $this->title = $title;
        }
        if ($tag1 !== null) {
            $this->tag1 = $tag1;
        }
        if ($tag2 !== null) {
            $this->tag2 = $tag2;
        }
        if ($description !== null) {
            $this->description = $description;
        }
        if ($buttonText !== null) {
            $this->buttonText = $buttonText;
        }
        if ($buttonUrl !== null) {
            $this->buttonUrl = $buttonUrl;
        }
    }

    /**
     * Populate properties from a Post model.
     * Kicker = large title (from post kicker field)
     * Title = subtitle below kicker (from post title field)
     */
    protected function populateFromPost(Post $post): void
    {
        $this->image = $post->featured_image_url ?? '';
        $this->imageAlt = $post->title;
        $this->kicker = $post->kicker ?? '';
        $this->title = $post->title;
        $this->tag1 = 'TASTY FEATURE';
        $this->tag1Slug = 'featured';
        $category = $post->categories->first();
        $this->tag2 = $category?->name ?? 'FOOD DESTINATIONS';
        $this->tag2Slug = $category?->slug;
        $this->description = $post->excerpt ?? '';
        $this->buttonText = 'Read More';
        $this->buttonUrl = $post->url;
    }

    /**
     * Populate properties from a static array.
     *
     * @param  array<string, mixed>  $data
     */
    protected function populateFromArray(array $data): void
    {
        $this->image = $data['image'] ?? '';
        $this->imageAlt = $data['imageAlt'] ?? $data['kicker'] ?? '';
        $this->kicker = $data['kicker'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->tag1 = $data['tag1'] ?? 'TASTY FEATURE';
        $this->tag1Slug = $data['tag1Slug'] ?? 'featured';
        $this->tag2 = $data['tag2'] ?? 'FOOD DESTINATIONS';
        $this->tag2Slug = $data['tag2Slug'] ?? null;
        $this->description = $data['description'] ?? '';
        $this->buttonText = $data['buttonText'] ?? 'Read More';
        $this->buttonUrl = $data['buttonUrl'] ?? $data['url'] ?? '#';
    }

    /**
     * Set default values when no post is found.
     */
    protected function setDefaults(): void
    {
        $this->post = null;
        $this->image = '';
        $this->imageAlt = '';
        $this->kicker = '';
        $this->title = '';
        $this->tag1 = 'TASTY FEATURE';
        $this->tag1Slug = 'featured';
        $this->tag2 = 'FOOD DESTINATIONS';
        $this->tag2Slug = null;
        $this->description = '';
        $this->buttonText = 'Read More';
        $this->buttonUrl = '#';
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
        return view('components.sections.featured-location');
    }
}
