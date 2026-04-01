<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Collection extends Model
{
    use HasFactory, HasTranslations, HasUuid;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'slug',
        'is_active',
        'order',
        'sort_order',
    ];

    protected $appends = [
        'posts_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Collection $collection) {
            if (empty($collection->uuid)) {
                $collection->uuid = (string) Str::uuid();
            }

            if (empty($collection->slug)) {
                $name = is_array($collection->name) ? ($collection->name['en'] ?? reset($collection->name)) : $collection->name;
                $collection->slug = static::generateUniqueSlug($name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'collection';
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope to order by translated name for a specific locale.
     */
    public function scopeOrderByTranslatedName(Builder $query, string $locale = 'en', string $direction = 'asc'): Builder
    {
        return $query->orderByRaw("name->>? {$direction}", [$locale]);
    }

    /**
     * Scope to search within translated name.
     */
    public function scopeWhereTranslatedNameLike(Builder $query, string $search, ?string $locale = null): Builder
    {
        if ($locale) {
            return $query->whereRaw('name->>? ILIKE ?', [$locale, "%{$search}%"]);
        }

        return $query->whereRaw('name::text ILIKE ?', ["%{$search}%"]);
    }

    /**
     * Scope for active collections.
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

    // Relationships

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    // Accessors

    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }
}
