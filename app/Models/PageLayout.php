<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PageLayout extends Model
{
    protected $fillable = [
        'layoutable_type',
        'layoutable_id',
        'configuration',
        'version',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'version' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, PageLayout>
     */
    public function layoutable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, PageLayout>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
