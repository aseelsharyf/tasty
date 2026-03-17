<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCode extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'max_uses',
        'times_used',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this discount code can be used right now.
     *
     * @return array{valid: bool, message: string}
     */
    public function validate(float $orderSubtotal): array
    {
        if (! $this->is_active) {
            return ['valid' => false, 'message' => 'This discount code is not active.'];
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return ['valid' => false, 'message' => 'This discount code is not yet active.'];
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return ['valid' => false, 'message' => 'This discount code has expired.'];
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return ['valid' => false, 'message' => 'This discount code has reached its usage limit.'];
        }

        if ($this->min_order_amount !== null && $orderSubtotal < (float) $this->min_order_amount) {
            return ['valid' => false, 'message' => 'Minimum order amount of '.number_format((float) $this->min_order_amount, 2).' MVR required.'];
        }

        return ['valid' => true, 'message' => 'Discount code applied.'];
    }

    /**
     * Calculate the discount amount for a given subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        $discount = match ($this->type) {
            DiscountType::Percentage => $subtotal * ((float) $this->value / 100),
            DiscountType::Fixed => (float) $this->value,
        };

        // Cap the discount if max_discount_amount is set
        if ($this->max_discount_amount !== null && $discount > (float) $this->max_discount_amount) {
            $discount = (float) $this->max_discount_amount;
        }

        // Discount cannot exceed subtotal
        return min($discount, $subtotal);
    }

    /**
     * Get a human-readable label for the discount.
     */
    public function getDiscountLabelAttribute(): string
    {
        return match ($this->type) {
            DiscountType::Percentage => $this->value.'% off',
            DiscountType::Fixed => number_format((float) $this->value, 2).' MVR off',
        };
    }

    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }
}
