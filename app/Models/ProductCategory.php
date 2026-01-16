<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class ProductCategory extends Model
{
    use HasFactory, HasTranslations, HasUuid;

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'featured_media_id',
        'is_active',
        'order',
    ];

    /** @var array<int, string> */
    protected $appends = ['products_count'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProductCategory $category) {
            if (empty($category->slug)) {
                $name = is_array($category->name)
                    ? ($category->name['en'] ?? reset($category->name))
                    : $category->name;
                $category->slug = static::generateUniqueSlug($name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'product-category';
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope to filter only active categories.
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
     * Scope to order by translated name for a specific locale.
     */
    public function scopeOrderByTranslatedName(Builder $query, string $locale = 'en', string $direction = 'asc'): Builder
    {
        return $query->orderByRaw("name->>? {$direction}", [$locale]);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'featured_media_id');
    }

    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featuredMedia?->url;
    }
}
