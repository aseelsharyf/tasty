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

    /** @var array<int, string> */
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

    /**
     * Create a new component instance.
     *
     * @param  ProductModel|array<string, mixed>|null  $product
     * @param  array<int, string>|string|null  $tags
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
    ) {
        $this->showPrice = $showPrice;
        if ($product instanceof ProductModel) {
            $this->image = $product->featured_image_url ?? '';
            $this->imageAlt = $product->featuredMedia?->alt_text ?? $product->title;
            // Build tags from category and featured tag
            $badgeTags = [];
            if ($product->category) {
                $badgeTags[] = strtoupper($product->category->name);
            }
            if ($product->featuredTag) {
                $badgeTags[] = strtoupper($product->featuredTag->name);
            }
            $this->tags = $badgeTags;
            $this->title = $product->title;
            $this->description = $product->description ?? '';
            $this->url = route('products.redirect', ['product' => $product->slug]);
            $this->price = $product->formatted_price;
            $this->compareAtPrice = $product->compare_at_price
                ? number_format((float) $product->compare_at_price, 2).' '.$product->currency
                : null;
            // Store info
            $this->storeLogo = $product->store?->logo_url;
            $this->storeName = $product->store?->name;
            $this->storeUrl = $product->store?->slug ? route('products.store', $product->store->slug) : null;
        } elseif (is_array($product)) {
            $this->image = $product['image'] ?? '';
            $this->imageAlt = $product['imageAlt'] ?? $product['title'] ?? '';
            $dataTags = $product['tags'] ?? [];
            $this->tags = is_string($dataTags) ? array_filter(array_map('trim', explode(',', $dataTags))) : $dataTags;
            $this->title = $product['title'] ?? '';
            $this->description = $product['description'] ?? '';
            $this->url = $product['url'] ?? '#';
            $this->price = $product['price'] ?? null;
            $this->compareAtPrice = $product['compareAtPrice'] ?? null;
            $this->storeLogo = $product['storeLogo'] ?? null;
            $this->storeName = $product['storeName'] ?? null;
            $this->storeUrl = $product['storeUrl'] ?? null;
        } else {
            $this->image = '';
            $this->imageAlt = '';
            $this->tags = [];
            $this->title = '';
            $this->description = '';
            $this->url = '#';
            $this->price = null;
            $this->compareAtPrice = null;
            $this->storeLogo = null;
            $this->storeName = null;
            $this->storeUrl = null;
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
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.product');
    }
}
