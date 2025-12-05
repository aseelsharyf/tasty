<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'is_active',
        'is_default',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'order' => 'integer',
        ];
    }

    // Relationships

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'language_code', 'code');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // Helpers

    public function isRtl(): bool
    {
        return $this->direction === 'rtl';
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::where('code', 'en')->first()
            ?? static::first();
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get all active language codes
     *
     * @return Collection<int, string>
     */
    public static function getActiveCodes(): Collection
    {
        return static::active()->pluck('code');
    }

    /**
     * Check if a language code is valid and active
     */
    public static function isValidCode(string $code): bool
    {
        return static::active()->where('code', $code)->exists();
    }
}
