<?php

namespace App\View\Components\Sections;

use App\Models\Post;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FeaturedLocation extends Component
{
    use ResolvesColors;

    public string $image;

    public string $imageAlt;

    public string $name;

    public string $tagline;

    public string $tag1;

    public string $tag2;

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
     * @param  array<string, mixed>|null  $data
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  string  $textColor  Text color (Tailwind utility name like 'blue-black')
     */
    public function __construct(
        Post|array|null $post = null,
        ?string $image = null,
        ?string $imageAlt = null,
        ?string $name = null,
        ?string $tagline = null,
        ?string $tag1 = null,
        ?string $tag2 = null,
        ?string $description = null,
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
            $this->name = $post->title;
            $this->tagline = $post->subtitle ?? '';
            $this->tag1 = 'TASTY FEATURE';
            $this->tag2 = $post->categories->first()?->name ?? 'FOOD DESTINATIONS';
            $this->description = $post->excerpt ?? '';
            $this->buttonText = 'Read More';
            $this->buttonUrl = $post->url;
        } elseif (is_array($post)) {
            $this->image = $post['image'] ?? '';
            $this->imageAlt = $post['imageAlt'] ?? $post['name'] ?? '';
            $this->name = $post['name'] ?? '';
            $this->tagline = $post['tagline'] ?? '';
            $this->tag1 = $post['tag1'] ?? 'TASTY FEATURE';
            $this->tag2 = $post['tag2'] ?? 'FOOD DESTINATIONS';
            $this->description = $post['description'] ?? '';
            $this->buttonText = $post['buttonText'] ?? 'Read More';
            $this->buttonUrl = $post['buttonUrl'] ?? $post['url'] ?? '#';
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->name = '';
            $this->tagline = '';
            $this->tag1 = 'TASTY FEATURE';
            $this->tag2 = 'FOOD DESTINATIONS';
            $this->description = '';
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
        if ($name !== null) {
            $this->name = $name;
        }
        if ($tagline !== null) {
            $this->tagline = $tagline;
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
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.featured-location');
    }
}
