<?php

namespace App\View\Components\Sections;

use App\Models\Post;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class EverydayCooking extends Component
{
    public string $introImage;

    public string $introImageAlt;

    public string $titleSmall;

    public string $titleLarge;

    public string $description;

    public ?Post $featuredPost;

    /** @var Collection<int, Post> */
    public Collection $posts;

    /**
     * Create a new component instance.
     *
     * @param  int|null  $featuredPostId  ID for the featured recipe post
     * @param  array<int, int>  $postIds  IDs for the recipe grid posts
     */
    public function __construct(
        string $introImage = '',
        string $introImageAlt = '',
        string $titleSmall = 'Everyday',
        string $titleLarge = 'Cooking',
        string $description = '',
        ?int $featuredPostId = null,
        array $postIds = [],
    ) {
        $this->introImage = $introImage;
        $this->introImageAlt = $introImageAlt;
        $this->titleSmall = $titleSmall;
        $this->titleLarge = $titleLarge;
        $this->description = $description;

        // Fetch featured post
        $this->featuredPost = $featuredPostId
            ? Post::with(['author', 'categories', 'tags'])->find($featuredPostId)
            : null;

        // Fetch grid posts
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
        return view('components.sections.everyday-cooking');
    }
}
