<?php

namespace App\View\Components\Cards;

use App\Models\Post;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Recipe extends Component
{
    public string $image;

    public string $imageAlt;

    /** @var array<int, string> */
    public array $tags;

    public string $title;

    public string $description;

    public string $author;

    public string $date;

    public string $url;

    public string $variant;

    /**
     * Create a new component instance.
     *
     * @param  array<string, mixed>|null  $data
     * @param  array<int, string>|null  $tags
     */
    public function __construct(
        Post|array|null $post = null,
        ?string $image = null,
        ?string $imageAlt = null,
        ?array $tags = null,
        ?string $title = null,
        ?string $description = null,
        ?string $author = null,
        ?string $date = null,
        ?string $url = null,
        string $variant = 'default',
    ) {
        $this->variant = $variant;

        if ($post instanceof Post) {
            $this->image = $post->featured_image_url ?? '';
            $this->imageAlt = $post->title;
            $this->tags = $this->extractTags($post);
            $this->title = $post->title;
            $this->description = $post->excerpt ?? '';
            $this->author = $post->author?->name ?? 'Unknown';
            $this->date = $post->published_at?->format('F j, Y') ?? '';
            $this->url = $post->url;
        } elseif (is_array($post)) {
            $this->image = $post['image'] ?? '';
            $this->imageAlt = $post['imageAlt'] ?? $post['title'] ?? '';
            $this->tags = $post['tags'] ?? [];
            $this->title = $post['title'] ?? '';
            $this->description = $post['description'] ?? '';
            $this->author = $post['author'] ?? 'Unknown';
            $this->date = $post['date'] ?? '';
            $this->url = $post['url'] ?? '#';
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->tags = [];
            $this->title = '';
            $this->description = '';
            $this->author = 'Unknown';
            $this->date = '';
            $this->url = '#';
        }

        // Allow individual prop overrides
        if ($image !== null) {
            $this->image = $image;
        }
        if ($imageAlt !== null) {
            $this->imageAlt = $imageAlt;
        }
        if ($tags !== null) {
            $this->tags = $tags;
        }
        if ($title !== null) {
            $this->title = $title;
        }
        if ($description !== null) {
            $this->description = $description;
        }
        if ($author !== null) {
            $this->author = $author;
        }
        if ($date !== null) {
            $this->date = $date;
        }
        if ($url !== null) {
            $this->url = $url;
        }
    }

    /**
     * Extract tags from a Post model.
     *
     * @return array<int, string>
     */
    private function extractTags(Post $post): array
    {
        $tags = ['RECIPE'];
        if ($tag = $post->tags->first()) {
            $tags[] = strtoupper($tag->name);
        }

        return $tags;
    }

    /**
     * Check if this is the featured variant.
     */
    public function isFeatured(): bool
    {
        return $this->variant === 'featured';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.recipe');
    }
}
