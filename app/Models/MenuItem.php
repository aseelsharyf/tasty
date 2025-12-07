<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    /** @use HasFactory<\Database\Factories\MenuItemFactory> */
    use HasFactory, HasTranslations, HasUuid;

    public array $translatable = ['label'];

    public const TYPE_CUSTOM = 'custom';

    public const TYPE_PAGE = 'page';

    public const TYPE_POST = 'post';

    public const TYPE_CATEGORY = 'category';

    public const TYPE_EXTERNAL = 'external';

    protected $fillable = [
        'uuid',
        'menu_id',
        'parent_id',
        'label',
        'type',
        'url',
        'linkable_type',
        'linkable_id',
        'target',
        'icon',
        'css_classes',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'css_classes' => 'array',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Scope to order by translated label for a specific locale.
     */
    public function scopeOrderByTranslatedLabel(Builder $query, string $locale = 'en', string $direction = 'asc'): Builder
    {
        return $query->orderByRaw("label->>? {$direction}", [$locale]);
    }

    /**
     * Scope to get only active items.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root items only.
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the menu this item belongs to.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the parent item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get child items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get the linked model (polymorphic).
     */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the resolved URL for this menu item.
     */
    public function getResolvedUrlAttribute(): ?string
    {
        if ($this->type === self::TYPE_EXTERNAL || $this->type === self::TYPE_CUSTOM) {
            return $this->url;
        }

        if ($this->linkable) {
            return match ($this->type) {
                self::TYPE_CATEGORY => route('category.show', $this->linkable->slug),
                self::TYPE_POST => route('post.show', $this->linkable->slug),
                default => $this->url,
            };
        }

        return $this->url;
    }

    /**
     * Check if this item has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this is a root item.
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get available item types.
     *
     * @return array<string, string>
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_CUSTOM => 'Custom Link',
            self::TYPE_EXTERNAL => 'External Link',
            self::TYPE_CATEGORY => 'Category',
            self::TYPE_POST => 'Post',
        ];
    }
}
