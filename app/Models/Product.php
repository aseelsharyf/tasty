<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory, HasTranslations, HasUuid, SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = ['title', 'description', 'short_description'];

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'description',
        'short_description',
        'brand',
        'product_category_id',
        'product_store_id',
        'featured_tag_id',
        'featured_media_id',
        'price',
        'currency',
        'availability',
        'affiliate_url',
        'is_active',
        'is_featured',
        'order',
        'sku',
        'stock_quantity',
        'track_inventory',
        'compare_at_price',
        'metadata',
    ];

    /** @var array<int, string> */
    protected $appends = ['featured_image_url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'track_inventory' => 'boolean',
            'order' => 'integer',
            'stock_quantity' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $title = is_array($product->title)
                    ? ($product->title['en'] ?? reset($product->title))
                    : $product->title;
                $product->slug = static::generateUniqueSlug($title);
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug ?: 'product';
        $counter = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope to filter only active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by the order column.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory(Builder $query, ProductCategory|int $category): Builder
    {
        $categoryId = $category instanceof ProductCategory ? $category->id : $category;

        return $query->where('product_category_id', $categoryId);
    }

    /**
     * Scope to order by translated title for a specific locale.
     */
    public function scopeOrderByTranslatedTitle(Builder $query, string $locale = 'en', string $direction = 'asc'): Builder
    {
        return $query->orderByRaw("title->>? {$direction}", [$locale]);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(ProductStore::class, 'product_store_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'featured_media_id');
    }

    public function featuredTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'featured_tag_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(MediaItem::class, 'product_images')
            ->withPivot('order')
            ->orderByPivot('order')
            ->withTimestamps();
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ProductClick::class);
    }

    /**
     * Scope to filter only featured products.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by availability.
     */
    public function scopeAvailable(Builder $query, string $availability = 'in_stock'): Builder
    {
        return $query->where('availability', $availability);
    }

    /**
     * Get all image URLs for product grids.
     */
    public function getImageUrlsAttribute(): array
    {
        return $this->images->pluck('url')->toArray();
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featuredMedia?->url;
    }

    public function getClickCountAttribute(): int
    {
        return $this->clicks()->count();
    }

    public function getFormattedPriceAttribute(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        return number_format((float) $this->price, 2).' '.$this->currency;
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price !== null
            && $this->price !== null
            && $this->compare_at_price > $this->price;
    }

    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Get the redirect URL for tracking.
     */
    public function getRedirectUrlAttribute(): string
    {
        return route('products.redirect', ['product' => $this->slug]);
    }
}
