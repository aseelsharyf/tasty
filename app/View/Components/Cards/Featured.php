<?php

namespace App\View\Components\Cards;

use App\Models\Post;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Featured extends Component
{
    public string $image;

    public string $imageAlt;

    public ?string $blurhash;

    public string $imagePosition;

    public ?string $category;

    public ?string $categoryUrl;

    public ?string $tag;

    public ?string $tagUrl;

    public string $title;

    public string $description;

    public string $author;

    public string $authorUrl;

    public string $date;

    public string $url;

    /**
     * Create a new component instance.
     *
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        Post|array|null $post = null,
        ?string $image = null,
        ?string $imageAlt = null,
        ?string $blurhash = null,
        ?string $imagePosition = null,
        ?string $category = null,
        ?string $categoryUrl = null,
        ?string $tag = null,
        ?string $tagUrl = null,
        ?string $title = null,
        ?string $description = null,
        ?string $author = null,
        ?string $authorUrl = null,
        ?string $date = null,
        ?string $url = null,
    ) {
        if ($post instanceof Post) {
            $categoryModel = $post->categories->first();
            $tagModel = $post->tags->first();
            $anchor = $post->featured_image_anchor ?? ['x' => 50, 'y' => 0];

            $this->image = $post->featured_image_url ?? '';
            $this->imageAlt = $post->title;
            $this->blurhash = $post->featured_image_blurhash;
            $this->imagePosition = ($anchor['x'] ?? 50).'% '.($anchor['y'] ?? 50).'%';
            $this->category = $categoryModel?->name;
            $this->categoryUrl = $categoryModel ? route('category.show', $categoryModel->slug) : null;
            $this->tag = $tagModel?->name;
            $this->tagUrl = $tagModel ? route('tag.show', $tagModel->slug) : null;
            $this->title = $post->title;
            $this->description = $post->excerpt ?? '';
            $this->author = $post->author?->name ?? 'Unknown';
            $this->authorUrl = $post->author?->url ?? '#';
            $this->date = $post->published_at?->format('F j, Y') ?? '';
            $this->url = $post->url;
        } elseif (is_array($post)) {
            $this->image = $post['image'] ?? '';
            $this->imageAlt = $post['imageAlt'] ?? $post['title'] ?? '';
            $this->blurhash = $post['blurhash'] ?? null;
            $this->imagePosition = $post['imagePosition'] ?? 'center';
            $this->category = $post['category'] ?? null;
            $this->categoryUrl = $post['categoryUrl'] ?? null;
            $this->tag = $post['tag'] ?? null;
            $this->tagUrl = $post['tagUrl'] ?? null;
            $this->title = $post['title'] ?? '';
            $this->description = $post['description'] ?? '';
            $this->author = $post['author'] ?? 'Unknown';
            $this->authorUrl = $post['authorUrl'] ?? '#';
            $this->date = $post['date'] ?? '';
            $this->url = $post['url'] ?? '#';
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->blurhash = null;
            $this->imagePosition = 'center';
            $this->category = null;
            $this->categoryUrl = null;
            $this->tag = null;
            $this->tagUrl = null;
            $this->title = '';
            $this->description = '';
            $this->author = 'Unknown';
            $this->authorUrl = '#';
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
        if ($blurhash !== null) {
            $this->blurhash = $blurhash;
        }
        if ($imagePosition !== null) {
            $this->imagePosition = $imagePosition;
        }
        if ($category !== null) {
            $this->category = $category;
        }
        if ($categoryUrl !== null) {
            $this->categoryUrl = $categoryUrl;
        }
        if ($tag !== null) {
            $this->tag = $tag;
        }
        if ($tagUrl !== null) {
            $this->tagUrl = $tagUrl;
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
        if ($authorUrl !== null) {
            $this->authorUrl = $authorUrl;
        }
        if ($date !== null) {
            $this->date = $date;
        }
        if ($url !== null) {
            $this->url = $url;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.featured');
    }
}
