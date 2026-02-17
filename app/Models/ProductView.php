<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductView extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'product_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referrer',
        'session_id',
        'viewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
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
     * Record a product view with 30-minute session deduplication.
     *
     * @param  array{product_id: int, user_id: int|null, ip_address: string|null, user_agent: string|null, referrer: string|null, session_id: string|null}  $data
     */
    public static function record(array $data): ?self
    {
        $sessionId = $data['session_id'] ?? null;
        $productId = $data['product_id'];

        // Deduplicate: same session viewing the same product within 30 minutes
        if ($sessionId) {
            $recentView = static::where('product_id', $productId)
                ->where('session_id', $sessionId)
                ->where('viewed_at', '>=', now()->subMinutes(30))
                ->exists();

            if ($recentView) {
                return null;
            }
        }

        return static::create([
            'product_id' => $productId,
            'user_id' => $data['user_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'session_id' => $sessionId,
            'viewed_at' => now(),
        ]);
    }
}
