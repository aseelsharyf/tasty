<?php

namespace App\View\Components\Sections;

use App\Actions\Posts\GetPostsByCategory;
use App\Actions\Posts\GetPostsByTag;
use App\Actions\Posts\GetRecentPosts;
use App\Actions\Posts\GetTrendingPosts;
use App\Models\Post;
use App\View\Components\Sections\Concerns\HasSectionCategoryRestrictions;
use App\View\Components\Sections\Concerns\TracksUsedPosts;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class FeaturedLocation extends Component
{
    use HasSectionCategoryRestrictions;
    use ResolvesColors;
    use TracksUsedPosts;

    protected function sectionType(): string
    {
        return 'featured-location';
    }

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

    /** @var Collection<int, Post|array<string, mixed>> */
    public Collection $carouselPosts;

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
     * @param  array<string, mixed>|null  $staticContent  Static content from CMS
     * @param  int  $totalSlots  Total number of slots from CMS
     * @param  array<int, int>  $manualPostIds  Index => postId for manual slots
     * @param  array<int, array<string, mixed>>  $staticSlots  Index => content for static slots
     * @param  int  $dynamicCount  Number of dynamic slots to fill
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
        ?array $staticContent = null,
        int $totalSlots = 0,
        array $manualPostIds = [],
        array $staticSlots = [],
        int $dynamicCount = 0,
    ) {
        // Initialize post tracker to prevent duplicates across sections
        $this->initPostTracker();

        // Resolve background color
        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];
        $this->textColor = $textColor;
        $this->buttonVariant = $buttonVariant;

        // Initialize carousel posts
        $this->carouselPosts = collect();

        // New hybrid slot mode from CMS
        if ($totalSlots > 0 || count($manualPostIds) > 0 || count($staticSlots) > 0) {
            $this->resolveHybridSlots(
                totalSlots: $totalSlots,
                manualPostIds: $manualPostIds,
                staticSlots: $staticSlots,
                dynamicCount: $dynamicCount,
                action: $action,
                params: $params,
            );

            // Apply overrides for featured post
            $this->applyOverrides($image, $imageAlt, $kicker, $title, $tag1, $tag2, $description, $buttonText, $buttonUrl);

            return;
        }

        // Static content from CMS
        if ($staticContent !== null) {
            $this->post = $staticContent;
            $this->populateFromArray($staticContent);

            return;
        }

        // Determine post source
        if ($post instanceof Post) {
            $this->post = $post;
            $this->populateFromPost($post);
            $this->markPostUsed($post);
        } elseif (is_array($post)) {
            // Static array data
            $this->post = $post;
            $this->populateFromArray($post);
        } elseif ($postId !== null) {
            // Fetch by specific ID
            $this->post = Post::with(['author', 'categories', 'tags'])->find($postId);
            if ($this->post) {
                $this->populateFromPost($this->post);
                $this->markPostUsed($this->post);
            } else {
                $this->setDefaults();
            }
        } else {
            // Fetch via action
            $this->post = $this->fetchPostViaAction($action, $params);
            if ($this->post) {
                $this->populateFromPost($this->post);
                $this->markPostUsed($this->post);
            } else {
                $this->setDefaults();
            }
        }

        // Apply overrides
        $this->applyOverrides($image, $imageAlt, $kicker, $title, $tag1, $tag2, $description, $buttonText, $buttonUrl);
    }

    /**
     * Apply individual prop overrides.
     */
    protected function applyOverrides(
        ?string $image,
        ?string $imageAlt,
        ?string $kicker,
        ?string $title,
        ?string $tag1,
        ?string $tag2,
        ?string $description,
        ?string $buttonText,
        ?string $buttonUrl,
    ): void {
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
     * Resolve hybrid slots with mix of manual, static, and dynamic content.
     *
     * @param  array<int, int>  $manualPostIds
     * @param  array<int, array<string, mixed>>  $staticSlots
     * @param  array<string, mixed>  $params
     */
    protected function resolveHybridSlots(
        int $totalSlots,
        array $manualPostIds,
        array $staticSlots,
        int $dynamicCount,
        string $action,
        array $params,
    ): void {
        // Fetch manual posts and filter by allowed categories
        $manualPosts = collect();
        $validManualIds = array_filter(array_values($manualPostIds));

        if (count($validManualIds) > 0) {
            $manualPosts = Post::with(['author', 'categories', 'tags'])
                ->whereIn('id', $validManualIds)
                ->get();

            $manualPosts = $this->filterAllowedPosts($manualPosts)->keyBy('id');
        }

        // Calculate how many dynamic posts we need
        $validManualCount = $manualPosts->count();
        $staticCount = count($staticSlots);
        $neededDynamicCount = $totalSlots - $validManualCount - $staticCount;

        // Fetch dynamic posts if needed
        $dynamicPosts = collect();
        if ($neededDynamicCount > 0) {
            $actionClass = $this->actions[$action] ?? GetRecentPosts::class;
            $actionInstance = new $actionClass;

            $excludeIds = $this->getExcludeIds($validManualIds);

            $result = $actionInstance->execute([
                'page' => 1,
                'perPage' => $neededDynamicCount,
                'excludeIds' => $excludeIds,
                'sectionType' => $this->sectionType(),
                ...$params,
            ]);

            $dynamicPosts = collect($result->items());
        }

        // Build final slot array
        $slots = [];
        $dynamicIndex = 0;

        for ($i = 0; $i < $totalSlots; $i++) {
            if (isset($manualPostIds[$i]) && $manualPosts->has($manualPostIds[$i])) {
                $slots[$i] = $manualPosts->get($manualPostIds[$i]);
            } elseif (isset($staticSlots[$i])) {
                $slots[$i] = $staticSlots[$i];
            } else {
                $slots[$i] = $dynamicPosts->get($dynamicIndex);
                $dynamicIndex++;
            }
        }

        $allSlots = collect($slots)->filter()->values();

        // First slot is featured, rest are carousel
        $featuredSlot = $allSlots->shift();

        if ($featuredSlot instanceof Post) {
            $this->post = $featuredSlot;
            $this->populateFromPost($featuredSlot);
            $this->markPostUsed($featuredSlot);
        } elseif (is_array($featuredSlot)) {
            $this->post = $featuredSlot;
            $this->populateFromArray($featuredSlot);
        } else {
            $this->setDefaults();
        }

        // Remaining slots become carousel posts
        $this->carouselPosts = $allSlots;
        $this->markPostsUsed($this->carouselPosts);
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
        $anchor = $post->featured_image_anchor;
        $this->imagePosition = $anchor ? ($anchor['x'] ?? 50).'% '.($anchor['y'] ?? 50).'%' : 'center';
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
        $this->imagePosition = $data['imagePosition'] ?? 'center';
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
        $this->imagePosition = 'center';
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
            'sectionType' => $this->sectionType(),
            'excludeIds' => $this->getExcludeIds(),
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
