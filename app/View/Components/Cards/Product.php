<?php

namespace App\View\Components\Cards;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Product extends Component
{
    public string $image;

    public string $imageAlt;

    /** @var array<int, string> */
    public array $tags;

    public string $title;

    public string $description;

    public string $url;

    /**
     * Create a new component instance.
     *
     * @param  array<string, mixed>|null  $data
     * @param  array<int, string>|null  $tags
     */
    public function __construct(
        ?array $data = null,
        ?string $image = null,
        ?string $imageAlt = null,
        ?array $tags = null,
        ?string $title = null,
        ?string $description = null,
        ?string $url = null,
    ) {
        if (is_array($data)) {
            $this->image = $data['image'] ?? '';
            $this->imageAlt = $data['imageAlt'] ?? $data['title'] ?? '';
            $this->tags = $data['tags'] ?? [];
            $this->title = $data['title'] ?? '';
            $this->description = $data['description'] ?? '';
            $this->url = $data['url'] ?? '#';
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->tags = [];
            $this->title = '';
            $this->description = '';
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
        if ($url !== null) {
            $this->url = $url;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.product');
    }
}
