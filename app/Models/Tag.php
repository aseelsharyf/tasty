<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    use HasFactory, HasTranslations, HasUuid;

    public array $translatable = ['name'];

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
    ];

    protected $appends = [
        'posts_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            // Generate UUID if not set
            if (empty($tag->uuid)) {
                $tag->uuid = (string) \Illuminate\Support\Str::uuid();
            }

            // Generate slug from name if not set
            if (empty($tag->slug)) {
                $name = is_array($tag->name) ? ($tag->name['en'] ?? reset($tag->name)) : $tag->name;
                $tag->slug = static::generateUniqueSlug($name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'tag';
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    // Relationships

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    // Accessors

    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }
}
