<?php

namespace App\View\Components\Cards;

use App\Models\Post;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Review extends Component
{
    use ResolvesColors;

    public string $image;

    public string $imageAlt;

    /** @var array<int, string> */
    public array $tags;

    public string $title;

    public string $subtitle;

    public string $description;

    public string $author;

    public string $date;

    public string $buttonText;

    public string $buttonUrl;

    public string $bgColorClass;

    public string $bgColorStyle;

    public string $textColor;

    public string $buttonVariant;

    /**
     * Create a new component instance.
     *
     * @param  array<string, mixed>|null  $data
     * @param  array<int, string>|null  $tags
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  string  $textColor  Text color (Tailwind utility name like 'blue-black')
     */
    public function __construct(
        Post|array|null $post = null,
        ?string $image = null,
        ?string $imageAlt = null,
        ?array $tags = null,
        ?string $title = null,
        ?string $subtitle = null,
        ?string $description = null,
        ?string $author = null,
        ?string $date = null,
        ?string $buttonText = null,
        ?string $buttonUrl = null,
        string $bgColor = 'yellow',
        string $textColor = 'blue-black',
        string $buttonVariant = 'white',
    ) {
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->textColor = $textColor;
        $this->buttonVariant = $buttonVariant;
        if ($post instanceof Post) {
            $this->image = $post->featured_image_url ?? '';
            $this->imageAlt = $post->title;
            $this->tags = $this->extractTags($post);
            $this->title = $post->title;
            $this->subtitle = $post->subtitle ?? '';
            $this->description = $post->excerpt ?? '';
            $this->author = $post->author?->name ?? 'Unknown';
            $this->date = $post->published_at?->format('F j, Y') ?? '';
            $this->buttonText = 'Read More';
            $this->buttonUrl = $post->url;
        } elseif (is_array($post)) {
            $this->image = $post['image'] ?? '';
            $this->imageAlt = $post['imageAlt'] ?? $post['title'] ?? '';
            $this->tags = $post['tags'] ?? [];
            $this->title = $post['title'] ?? '';
            $this->subtitle = $post['subtitle'] ?? '';
            $this->description = $post['description'] ?? '';
            $this->author = $post['author'] ?? 'Unknown';
            $this->date = $post['date'] ?? '';
            $this->buttonText = $post['buttonText'] ?? 'Read More';
            $this->buttonUrl = $post['buttonUrl'] ?? $post['url'] ?? '#';
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->tags = [];
            $this->title = '';
            $this->subtitle = '';
            $this->description = '';
            $this->author = 'Unknown';
            $this->date = '';
            $this->buttonText = 'Read More';
            $this->buttonUrl = '#';
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
        if ($subtitle !== null) {
            $this->subtitle = $subtitle;
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
        if ($buttonText !== null) {
            $this->buttonText = $buttonText;
        }
        if ($buttonUrl !== null) {
            $this->buttonUrl = $buttonUrl;
        }
    }

    /**
     * Extract tags from a Post model.
     *
     * @return array<int, string>
     */
    private function extractTags(Post $post): array
    {
        $tags = [];
        if ($category = $post->categories->first()) {
            $tags[] = strtoupper($category->name);
        }
        $tags[] = 'REVIEW';

        return $tags;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.review');
    }
}
