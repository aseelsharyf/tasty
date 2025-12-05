<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory, HasTranslations, HasUuid;

    public array $translatable = ['name', 'description'];

    /**
     * Scope to order by translated name for a specific locale.
     * Uses PostgreSQL JSON extraction: name->>'locale'
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

        // Search across all translations
        return $query->whereRaw('name::text ILIKE ?', ["%{$search}%"]);
    }

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'parent_id',
        'order',
    ];

    protected $appends = [
        'posts_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            // Generate UUID if not set
            if (empty($category->uuid)) {
                $category->uuid = (string) \Illuminate\Support\Str::uuid();
            }

            // Generate slug from name if not set
            if (empty($category->slug)) {
                $name = is_array($category->name) ? ($category->name['en'] ?? reset($category->name)) : $category->name;
                $category->slug = static::generateUniqueSlug($name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'category';
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    // Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    // Accessors

    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }

    // Helpers

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get all ancestors of this category.
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public function ancestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $category = $this->parent;

        while ($category) {
            $ancestors->prepend($category);
            $category = $category->parent;
        }

        return $ancestors;
    }

    /**
     * Get the full path of this category (e.g., "Parent > Child > Grandchild").
     */
    public function getFullPathAttribute(): string
    {
        $path = $this->ancestors()->pluck('name')->push($this->name);

        return $path->implode(' > ');
    }
}
