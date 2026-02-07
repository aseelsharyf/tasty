<?php

namespace App\View\Components\Sections;

use App\Models\Post;
use App\View\Components\Sections\Concerns\TracksUsedPosts;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Feature2 extends Component
{
    use ResolvesColors;
    use TracksUsedPosts;

    /** @var Post|array<string, mixed>|null */
    public Post|array|null $post;

    public string $image;

    public string $imageAlt;

    public string $imagePosition;

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

    /**
     * Create a new component instance.
     *
     * @param  int|null  $postId  Specific post ID to display (manual mode)
     * @param  array<string, mixed>|null  $staticContent  Static content from CMS
     * @param  string|null  $tag1  Override first tag
     * @param  string|null  $tag2  Override second tag
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  string  $textColor  Text color (Tailwind utility name like 'white')
     * @param  string  $buttonVariant  Button variant (white, yellow, blue-black)
     * @param  string  $buttonText  Button text
     */
    public function __construct(
        ?int $postId = null,
        ?array $staticContent = null,
        ?string $tag1 = null,
        ?string $tag2 = null,
        string $bgColor = 'blue-black',
        string $textColor = 'white',
        string $buttonVariant = 'yellow',
        string $buttonText = 'Read More',
    ) {
        // Initialize post tracker to prevent duplicates across sections
        $this->initPostTracker();

        // Resolve background color
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->textColor = $textColor;
        $this->buttonVariant = $buttonVariant;
        $this->buttonText = $buttonText;

        // Static content from CMS
        if ($staticContent !== null) {
            $this->post = $staticContent;
            $this->populateFromArray($staticContent);
        } elseif ($postId !== null) {
            // Fetch by specific ID (manual mode)
            $this->post = Post::with(['author', 'categories', 'tags'])->find($postId);
            if ($this->post) {
                $this->populateFromPost($this->post);
                $this->markPostUsed($this->post);
            } else {
                $this->setDefaults();
            }
        } else {
            $this->setDefaults();
        }

        // Allow tag overrides from config
        if ($tag1 !== null) {
            $this->tag1 = $tag1;
        }
        if ($tag2 !== null) {
            $this->tag2 = $tag2;
        }
    }

    /**
     * Populate properties from a Post model.
     */
    protected function populateFromPost(Post $post): void
    {
        $this->image = $post->featured_image_url ?? '';
        $this->imageAlt = $post->title;
        $anchor = $post->featured_image_anchor;
        $this->imagePosition = $anchor ? ($anchor['x'] ?? 50).'% '.($anchor['y'] ?? 50).'%' : 'center';
        $this->kicker = $post->kicker ?? '';
        $this->title = $post->title;
        $this->tag1 = 'TASTY FEATURE';
        $this->tag1Slug = 'featured';
        $category = $post->categories->first();
        $this->tag2 = $category?->name ?? '';
        $this->tag2Slug = $category?->slug;
        $this->description = $post->excerpt ?? '';
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
        $this->imagePosition = $data['imagePosition'] ?? 'center';
        $this->kicker = $data['kicker'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->tag1 = $data['tag1'] ?? 'TASTY FEATURE';
        $this->tag1Slug = $data['tag1Slug'] ?? 'featured';
        $this->tag2 = $data['tag2'] ?? $data['category'] ?? '';
        $this->tag2Slug = $data['tag2Slug'] ?? $data['categorySlug'] ?? null;
        $this->description = $data['description'] ?? '';
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
        $this->imagePosition = 'center';
        $this->kicker = '';
        $this->title = '';
        $this->tag1 = 'TASTY FEATURE';
        $this->tag1Slug = 'featured';
        $this->tag2 = '';
        $this->tag2Slug = null;
        $this->description = '';
        $this->buttonUrl = '#';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.feature-2');
    }
}
