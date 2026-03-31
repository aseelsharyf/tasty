<?php

namespace App\View\Components\Cards;

use App\Models\Product as ProductModel;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Product extends Component
{
    public string $image;

    public string $imageAlt;

    public ?string $blurhash;

    /** @var array<int, array{name: string, url: string|null}> */
    public array $tags;

    public string $title;

    public string $description;

    public string $url;

    public ?string $price;

    public ?string $compareAtPrice;

    public bool $showPrice;

    public ?string $storeLogo;

    public ?string $storeName;

    public ?string $storeUrl;

    public ?string $categoryName;

    public ?string $categoryUrl;

    public ?int $productId;

    public bool $isPurchasable;

    public bool $hasVariants;

    public ?string $productSlug;

    /** @var array<int, array{id: int, name: string, price: string, in_stock: bool}> */
    public array $variants;

    /**
     * Create a new component instance.
     *
     * @param  ProductModel|array<string, mixed>|null  $product
     * @param  array<int, array{name: string, url: string|null}>|array<int, string>|string|null  $tags
     */
    public function __construct(
        ProductModel|array|null $product = null,
        ?string $image = null,
        ?string $imageAlt = null,
        array|string|null $tags = null,
        ?string $title = null,
        ?string $description = null,
        ?string $url = null,
        ?string $price = null,
        ?string $compareAtPrice = null,
        bool $showPrice = true,
        ?string $storeLogo = null,
        ?string $storeName = null,
        ?string $storeUrl = null,
        ?string $categoryName = null,
        ?string $categoryUrl = null,
    ) {
        $this->showPrice = $showPrice;
        $this->productId = null;
        $this->isPurchasable = false;
        $this->hasVariants = false;
        $this->productSlug = null;
        $this->variants = [];
        if ($product instanceof ProductModel) {
            $this->productId = $product->id;
            $this->isPurchasable = $product->isPurchasable();
            $this->hasVariants = $product->variants()->exists();
            $this->productSlug = $product->slug;
            if ($this->hasVariants) {
                $this->variants = $product->variants
                    ->map(fn ($v) => [
                        'id' => $v->id,
                        'name' => $v->name,
                        'price' => number_format((float) $v->price, 2).' '.$product->currency,
                        'in_stock' => $v->isInStock(),
                    ])
                    ->all();
            }
            $this->image = $product->featured_image_url ?? '';
            $this->imageAlt = $product->featuredMedia?->alt_text ?? $product->title;
            $this->blurhash = $product->featuredMedia?->blurhash;
            // Build tags from category only
            $badgeTags = [];
            if ($product->category) {
                $badgeTags[] = [
                    'name' => strtoupper($product->category->name),
                    'url' => $this->safeRoute('products.category', $product->category->slug),
                ];
            }
            $this->tags = $badgeTags;
            $this->title = $product->title;
            $this->description = $product->description ?? '';
            $this->url = $product->url;
            $this->price = $product->formatted_price;
            $this->compareAtPrice = $product->compare_at_price
                ? number_format((float) $product->compare_at_price, 2).' '.$product->currency
                : null;
            // Store info
            $this->storeLogo = $product->store?->logo_url;
            $this->storeName = $product->store?->name;
            $this->storeUrl = $product->store?->slug ? $this->safeRoute('products.store', $product->store->slug) : null;
            // Category info
            $this->categoryName = $product->category?->name;
            $this->categoryUrl = $product->category?->slug ? $this->safeRoute('products.category', $product->category->slug) : null;
        } elseif (is_array($product)) {
            $this->image = $product['image'] ?? '';
            $this->imageAlt = $product['imageAlt'] ?? $product['title'] ?? '';
            $this->blurhash = $product['blurhash'] ?? null;
            $dataTags = $product['tags'] ?? [];
            // Convert simple string tags to array format
            if (is_string($dataTags)) {
                $this->tags = array_map(fn ($t) => ['name' => trim($t), 'url' => null], array_filter(explode(',', $dataTags)));
            } elseif (is_array($dataTags) && count($dataTags) > 0 && is_string($dataTags[0] ?? null)) {
                // Simple array of strings
                $this->tags = array_map(fn ($t) => ['name' => $t, 'url' => null], $dataTags);
            } else {
                $this->tags = $dataTags;
            }
            $this->title = $product['title'] ?? '';
            $this->description = $product['description'] ?? '';
            $this->url = $product['url'] ?? '#';
            $this->price = $product['price'] ?? null;
            $this->compareAtPrice = $product['compareAtPrice'] ?? null;
            $this->storeLogo = $product['storeLogo'] ?? null;
            $this->storeName = $product['storeName'] ?? null;
            $this->storeUrl = $product['storeUrl'] ?? null;
            $this->categoryName = $product['categoryName'] ?? null;
            $this->categoryUrl = $product['categoryUrl'] ?? null;
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->blurhash = null;
            $this->tags = [];
            $this->title = '';
            $this->description = '';
            $this->url = '#';
            $this->price = null;
            $this->compareAtPrice = null;
            $this->storeLogo = null;
            $this->storeName = null;
            $this->storeUrl = null;
            $this->categoryName = null;
            $this->categoryUrl = null;
        }

        // Allow individual prop overrides
        if ($image !== null) {
            $this->image = $image;
        }
        if ($imageAlt !== null) {
            $this->imageAlt = $imageAlt;
        }
        if ($tags !== null) {
            $this->tags = is_string($tags) ? array_filter(array_map('trim', explode(',', $tags))) : $tags;
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
        if ($price !== null) {
            $this->price = $price;
        }
        if ($compareAtPrice !== null) {
            $this->compareAtPrice = $compareAtPrice;
        }
        if ($storeLogo !== null) {
            $this->storeLogo = $storeLogo;
        }
        if ($storeName !== null) {
            $this->storeName = $storeName;
        }
        if ($storeUrl !== null) {
            $this->storeUrl = $storeUrl;
        }
        if ($categoryName !== null) {
            $this->categoryName = $categoryName;
        }
        if ($categoryUrl !== null) {
            $this->categoryUrl = $categoryUrl;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.product');
    }

    /**
     * Safely generate a route URL, returning '#' if route doesn't exist (CMS_ONLY mode).
     */
    private function safeRoute(string $name, mixed $parameters = []): string
    {
        try {
            return route($name, $parameters);
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException) {
            return '#';
        }
    }
}
