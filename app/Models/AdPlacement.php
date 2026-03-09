<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdPlacement extends Model
{
    use HasFactory, HasUuid;

    public const PAGE_TYPE_ARTICLE_DETAIL = 'article_detail';

    public const SLOT_AFTER_HEADER = 'after_header';

    public const SLOT_BEFORE_COMMENTS = 'before_comments';

    public const SLOT_AFTER_COMMENTS = 'after_comments';

    public const SLOT_AFTER_INGREDIENTS = 'after_ingredients';

    public const SLOTS = [
        self::SLOT_AFTER_HEADER => 'After Header/Meta',
        self::SLOT_BEFORE_COMMENTS => 'Before Comments',
        self::SLOT_AFTER_COMMENTS => 'After Comments',
        self::SLOT_AFTER_INGREDIENTS => 'After Ingredients',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'page_type',
        'slot',
        'category_id',
        'ad_code',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (AdPlacement $adPlacement) {
            if (empty($adPlacement->uuid)) {
                $adPlacement->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPageType(Builder $query, string $pageType): Builder
    {
        return $query->where('page_type', $pageType);
    }

    public function scopeForSlot(Builder $query, string $slot): Builder
    {
        return $query->where('slot', $slot);
    }

    public function scopeForCategory(Builder $query, ?int $categoryId): Builder
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)
                ->orWhereNull('category_id');
        });
    }

    // Helper Methods

    /**
     * Get the ad code for a specific article slot.
     * Walks up the category hierarchy: subcategory → parent → ... → global.
     */
    public static function getAdForArticleSlot(string $slot, ?int $categoryId = null): ?string
    {
        $categoryIds = [];

        if ($categoryId) {
            $category = Category::find($categoryId);

            if ($category) {
                // Build chain: subcategory → parent → grandparent → ...
                $categoryIds[] = $category->id;

                foreach ($category->ancestors() as $ancestor) {
                    $categoryIds[] = $ancestor->id;
                }
            }
        }

        // Add null for global fallback
        $categoryIds[] = null;

        $baseQuery = static::query()
            ->active()
            ->forPageType(self::PAGE_TYPE_ARTICLE_DETAIL)
            ->forSlot($slot);

        // Try each category in hierarchy order, then global
        foreach ($categoryIds as $id) {
            $ad = (clone $baseQuery)
                ->where(fn ($q) => $id ? $q->where('category_id', $id) : $q->whereNull('category_id'))
                ->orderByDesc('priority')
                ->first();

            if ($ad) {
                return $ad->ad_code;
            }
        }

        return null;
    }

    /**
     * Get the slot label for display.
     */
    public function getSlotLabelAttribute(): string
    {
        return self::SLOTS[$this->slot] ?? $this->slot;
    }

    /**
     * Get available page types.
     *
     * @return array<string, string>
     */
    public static function getPageTypes(): array
    {
        return [
            self::PAGE_TYPE_ARTICLE_DETAIL => 'Article Detail',
        ];
    }
}
