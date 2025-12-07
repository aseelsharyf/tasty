<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Menu extends Model
{
    /** @use HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory, HasTranslations, HasUuid;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'uuid',
        'name',
        'location',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to order by translated name for a specific locale.
     */
    public function scopeOrderByTranslatedName(Builder $query, string $locale = 'en', string $direction = 'asc'): Builder
    {
        return $query->orderByRaw("name->>? {$direction}", [$locale]);
    }

    /**
     * Scope to search within translated name for a specific locale.
     */
    public function scopeWhereTranslatedNameLike(Builder $query, string $search, ?string $locale = null): Builder
    {
        if ($locale) {
            return $query->whereRaw('name->>? ILIKE ?', [$locale, "%{$search}%"]);
        }

        return $query->whereRaw('name::text ILIKE ?', ["%{$search}%"]);
    }

    /**
     * Scope to get only active menus.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get menu items for this menu (root level only).
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('order');
    }

    /**
     * Get all menu items including nested.
     */
    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    /**
     * Get the menu tree with nested items.
     *
     * @return \Illuminate\Support\Collection<int, MenuItem>
     */
    public function getTree(): \Illuminate\Support\Collection
    {
        return $this->items()
            ->with(['children' => function ($query) {
                $query->orderBy('order')->with('children');
            }])
            ->get();
    }

    /**
     * Get a menu by its location.
     */
    public static function findByLocation(string $location): ?self
    {
        return static::where('location', $location)->active()->first();
    }
}
