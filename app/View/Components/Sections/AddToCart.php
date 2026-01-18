<?php

namespace App\View\Components\Sections;

use App\Models\Product;
use App\View\Concerns\ResolvesColors;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class AddToCart extends Component
{
    use ResolvesColors;

    public string $title;

    public string $description;

    public string $bgColorClass;

    public string $bgColorStyle;

    /** @var Collection<int, Product|array<string, mixed>> */
    public Collection $products;

    /**
     * Create a new component instance.
     *
     * @param  string  $title  Section title
     * @param  string  $description  Section description
     * @param  string  $bgColor  Background color
     * @param  array<int, int>  $productIds  Specific product IDs to display
     * @param  int  $count  Number of products to fetch if no productIds specified
     * @param  int|null  $categoryId  Filter by product category ID
     */
    public function __construct(
        string $title = 'ADD TO CART',
        string $description = 'Ingredients, tools, and staples we actually use.',
        string $bgColor = 'white',
        array $productIds = [],
        int $count = 6,
        ?int $categoryId = null,
    ) {
        $this->title = $title;
        $this->description = $description;

        $bgResolved = $this->resolveBgColor($bgColor);
        $this->bgColorClass = $bgResolved['class'];
        $this->bgColorStyle = $bgResolved['style'];

        // Load products
        if (count($productIds) > 0) {
            // Load specific products by ID
            $this->products = Product::with(['featuredMedia', 'category', 'featuredTag', 'store.logo'])
                ->active()
                ->whereIn('id', $productIds)
                ->get()
                ->sortBy(fn ($product) => array_search($product->id, $productIds))
                ->values();
        } else {
            // Load recent active products
            $query = Product::with(['featuredMedia', 'category', 'featuredTag', 'store.logo'])
                ->active()
                ->ordered();

            if ($categoryId) {
                $query->where('product_category_id', $categoryId);
            }

            $this->products = $query->limit($count)->get();
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.sections.add-to-cart');
    }
}
