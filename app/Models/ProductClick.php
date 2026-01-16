<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductClick extends Model
{
    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'product_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referrer',
        'session_id',
        'clicked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a product click.
     */
    public static function record(Product $product, ?int $userId = null): self
    {
        return static::create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'session_id' => session()->getId(),
            'clicked_at' => now(),
        ]);
    }
}
