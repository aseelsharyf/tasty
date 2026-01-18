<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductStore extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'name',
        'business_type',
        'address',
        'location_label',
        'logo_media_id',
        'hotline',
        'contact_email',
        'website_url',
        'is_active',
        'order',
    ];

    /** @var array<int, string> */
    protected $appends = ['products_count', 'logo_url'];

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

    /**
     * Scope to filter only active stores.
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'logo_media_id');
    }

    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo?->url;
    }
}
