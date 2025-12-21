<?php

namespace App\View\Components\Cards;

use App\Models\Post;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Restaurant extends Component
{
    public string $image;

    public string $imageAlt;

    public string $name;

    public string $tagline;

    public string $description;

    public int $rating;

    public string $url;

    public string $variant;

    /**
     * Create a new component instance.
     *
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        Post|array|null $post = null,
        ?string $image = null,
        ?string $imageAlt = null,
        ?string $name = null,
        ?string $tagline = null,
        ?string $description = null,
        ?int $rating = null,
        ?string $url = null,
        string $variant = 'default',
    ) {
        $this->variant = $variant;
        if ($post instanceof Post) {
            $this->image = $post->featured_image_url ?? '';
            $this->imageAlt = $post->title;
            $this->name = $post->title;
            $this->tagline = $post->subtitle ?? '';
            $this->description = $post->excerpt ?? '';
            $this->rating = (int) ($post->getCustomField('rating') ?? 4);
            $this->url = $post->url;
        } elseif (is_array($post)) {
            $this->image = $post['image'] ?? '';
            $this->imageAlt = $post['imageAlt'] ?? $post['name'] ?? '';
            $this->name = $post['name'] ?? '';
            $this->tagline = $post['tagline'] ?? '';
            $this->description = $post['description'] ?? '';
            $this->rating = (int) ($post['rating'] ?? 4);
            $this->url = $post['url'] ?? '#';
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->name = '';
            $this->tagline = '';
            $this->description = '';
            $this->rating = 4;
            $this->url = '#';
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
        if ($description !== null) {
            $this->description = $description;
        }
        if ($rating !== null) {
            $this->rating = $rating;
        }
        if ($url !== null) {
            $this->url = $url;
        }
    }

    /**
     * Generate star rating display.
     */
    public function ratingStars(): string
    {
        $filled = str_repeat('★', $this->rating);
        $empty = str_repeat('☆', 5 - $this->rating);

        return $filled.$empty;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.restaurant');
    }
}
