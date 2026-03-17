<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid', 'order_number', 'status', 'payment_status', 'payment_method',
        'discount_code_id', 'discount_code', 'discount_amount',
        'subtotal', 'total', 'currency', 'contact_person', 'contact_number',
        'email', 'delivery_location_id', 'address', 'additional_info',
        'has_affiliate_products', 'accepted_at', 'paid_at', 'shipped_at',
        'completed_at', 'cancelled_at', 'cancellation_reason', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'has_affiliate_products' => 'boolean',
            'accepted_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    // Generate order number: TST-YYYYMMDD-XXXX
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "TST-{$date}-";
        $lastOrder = static::where('order_number', 'like', "{$prefix}%")
            ->orderBy('order_number', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $nextNumber = $lastNumber + 1;
        }

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function isAutoAcceptable(): bool
    {
        return ! $this->has_affiliate_products;
    }

    public function markAsAccepted(): void
    {
        $this->update(['status' => OrderStatus::Accepted, 'accepted_at' => now()]);
    }

    public function markAsPaid(): void
    {
        $this->update(['payment_status' => PaymentStatus::Paid, 'paid_at' => now()]);
    }
}
