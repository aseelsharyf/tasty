<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'post_id',
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

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a post view with 30-minute session deduplication.
     *
     * @param  array{post_id: int, user_id: int|null, ip_address: string|null, user_agent: string|null, referrer: string|null, session_id: string|null}  $data
     */
    public static function record(array $data): ?self
    {
        $sessionId = $data['session_id'] ?? null;
        $postId = $data['post_id'];

        // Deduplicate: same session viewing the same post within 30 minutes
        if ($sessionId) {
            $recentView = static::where('post_id', $postId)
                ->where('session_id', $sessionId)
                ->where('viewed_at', '>=', now()->subMinutes(30))
                ->exists();

            if ($recentView) {
                return null;
            }
        }

        return static::create([
            'post_id' => $postId,
            'user_id' => $data['user_id'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'session_id' => $sessionId,
            'viewed_at' => now(),
        ]);
    }
}
