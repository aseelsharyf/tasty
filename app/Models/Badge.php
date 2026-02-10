<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Badge extends Model
{
    use HasFactory, HasTranslations, HasUuid;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'icon',
        'color',
        'description',
        'is_active',
        'order',
    ];

    protected static function booted(): void
    {
        static::creating(function (Badge $badge) {
            if (empty($badge->uuid)) {
                $badge->uuid = (string) Str::uuid();
            }

            if (empty($badge->slug)) {
                $name = is_array($badge->name) ? ($badge->name['en'] ?? reset($badge->name)) : $badge->name;
                $badge->slug = static::generateUniqueSlug($name);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'badge';
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope to only active badges.
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

    // Relationships

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
