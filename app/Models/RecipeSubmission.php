<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RecipeSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeSubmissionFactory> */
    use HasFactory, HasUuid;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CONVERTED = 'converted';

    public const TYPE_SINGLE = 'single';

    public const TYPE_COMPOSITE = 'composite';

    protected $fillable = [
        'uuid',
        'submission_type',
        'submitter_name',
        'submitter_email',
        'submitter_phone',
        'submitter_avatar',
        'is_chef',
        'chef_name',
        'recipe_name',
        'headline',
        'slug',
        'description',
        'prep_time',
        'cook_time',
        'total_time',
        'servings',
        'categories',
        'meal_times',
        'ingredients',
        'instructions',
        'child_recipes',
        'parent_submission_id',
        'image_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'converted_post_id',
    ];

    protected function casts(): array
    {
        return [
            'is_chef' => 'boolean',
            'categories' => 'array',
            'meal_times' => 'array',
            'ingredients' => 'array',
            'instructions' => 'array',
            'child_recipes' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RecipeSubmission $submission) {
            if (empty($submission->slug)) {
                $submission->slug = Str::slug($submission->recipe_name);
            }

            if (empty($submission->total_time) && ($submission->prep_time || $submission->cook_time)) {
                $submission->total_time = ($submission->prep_time ?? 0) + ($submission->cook_time ?? 0);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relationships

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function convertedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'converted_post_id');
    }

    public function parentSubmission(): BelongsTo
    {
        return $this->belongsTo(RecipeSubmission::class, 'parent_submission_id');
    }

    public function childSubmissions(): HasMany
    {
        return $this->hasMany(RecipeSubmission::class, 'parent_submission_id');
    }

    // Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    public function scopeSingle(Builder $query): Builder
    {
        return $query->where('submission_type', self::TYPE_SINGLE);
    }

    public function scopeComposite(Builder $query): Builder
    {
        return $query->where('submission_type', self::TYPE_COMPOSITE);
    }

    // Helpers

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function isSingle(): bool
    {
        return $this->submission_type === self::TYPE_SINGLE;
    }

    public function isComposite(): bool
    {
        return $this->submission_type === self::TYPE_COMPOSITE;
    }

    public function getChefDisplayName(): string
    {
        return $this->is_chef ? $this->submitter_name : ($this->chef_name ?? $this->submitter_name);
    }

    public function approve(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function reject(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function markAsConverted(Post $post): void
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_post_id' => $post->id,
        ]);
    }
}
