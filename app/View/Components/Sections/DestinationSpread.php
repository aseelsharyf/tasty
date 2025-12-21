<?php

namespace App\View\Components\Sections;

use App\Models\Post;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class DestinationSpread extends Component
{
    use ResolvesColors;

    public string $bgColorClass;

    public string $bgColorStyle;

    public bool $showDividers;

    public string $dividerColor;

    /** @var Collection<int, Post> */
    public Collection $posts;

    /**
     * Create a new component instance.
     *
     * @param  array<int, int>  $postIds  Specific post IDs to display
     * @param  string  $bgColor  Background color (named, Tailwind class, hex, or rgba)
     * @param  bool  $showDividers  Show dividers between cards
     * @param  string  $dividerColor  Divider color (white, gray, or Tailwind class)
     */
    public function __construct(
        array $postIds = [],
        string $bgColor = 'yellow',
        bool $showDividers = true,
        string $dividerColor = 'white',
    ) {
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->showDividers = $showDividers;
        $this->dividerColor = str_starts_with($dividerColor, 'bg-') ? $dividerColor : ($dividerColor === 'white' ? 'bg-white' : 'bg-gray-300');

        // Fetch posts by IDs
        if (count($postIds) > 0) {
            $this->posts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', $postIds)
                ->get()
                ->sortBy(fn ($post) => array_search($post->id, $postIds))
                ->values();
        } else {
            $this->posts = collect();
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.destination-spread');
    }
}
