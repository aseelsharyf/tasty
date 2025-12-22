<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Sponsor extends Model
{
    use HasFactory, HasTranslations, HasUuid;

    public array $translatable = ['name', 'url'];

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'url',
        'featured_media_id',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = [
        'posts_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sponsor $sponsor) {
            if (empty($sponsor->uuid)) {
                $sponsor->uuid = (string) Str::uuid();
            }

            if (empty($sponsor->slug)) {
                $name = is_array($sponsor->name) ? ($sponsor->name['en'] ?? reset($sponsor->name)) : $sponsor->name;
                $sponsor->slug = static::generateUniqueSlug($name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'sponsor';
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
     * Scope to get only active sponsors.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    // Relationships

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'featured_media_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    // Accessors

    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featuredMedia?->url;
    }
}
