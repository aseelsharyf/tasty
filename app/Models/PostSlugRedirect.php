<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostSlugRedirect extends Model
{
    protected $fillable = [
        'post_id',
        'old_slug',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public static function record(Post $post, string $oldSlug): self
    {
        return static::query()->updateOrCreate(
            ['old_slug' => $oldSlug],
            ['post_id' => $post->id],
        );
    }
}
