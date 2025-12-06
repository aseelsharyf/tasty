<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTarget extends Model
{
    use HasFactory;

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_MONTHLY = 'monthly';

    public const PERIOD_YEARLY = 'yearly';

    protected $fillable = [
        'user_id',
        'category_id',
        'period_type',
        'period_start',
        'target_count',
        'assigned_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'target_count' => 'integer',
        ];
    }

    // === Relationships ===

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // === Scopes ===

    /**
     * Filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filter by period type.
     */
    public function scopeOfType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Filter by category (null for overall targets).
     */
    public function scopeForCategory($query, ?int $categoryId)
    {
        if ($categoryId === null) {
            return $query->whereNull('category_id');
        }

        return $query->where('category_id', $categoryId);
    }

    /**
     * Get only overall targets (no category).
     */
    public function scopeOverall($query)
    {
        return $query->whereNull('category_id');
    }

    /**
     * Get only category-specific targets.
     */
    public function scopeCategorySpecific($query)
    {
        return $query->whereNotNull('category_id');
    }

    /**
     * Get target for a specific period that contains the given date.
     */
    public function scopeForPeriod($query, string $periodType, ?Carbon $date = null)
    {
        $date = $date ?? now();
        $periodStart = self::getPeriodStart($periodType, $date);

        return $query->where('period_type', $periodType)
            ->where('period_start', $periodStart);
    }

    /**
     * Get current target for a period type.
     */
    public function scopeCurrent($query, string $periodType = self::PERIOD_MONTHLY)
    {
        return $query->forPeriod($periodType, now());
    }

    // === Helpers ===

    /**
     * Check if this is a category-specific target.
     */
    public function isCategorySpecific(): bool
    {
        return $this->category_id !== null;
    }

    /**
     * Check if target was assigned by admin.
     */
    public function isAssigned(): bool
    {
        return $this->assigned_by !== null;
    }

    /**
     * Check if user can edit this target.
     */
    public function canEdit(User $user): bool
    {
        // Admin/Editor can always edit
        if ($user->hasAnyRole(['Admin', 'Editor', 'Developer'])) {
            return true;
        }

        // User can edit their own self-set targets
        return $this->user_id === $user->id && ! $this->isAssigned();
    }

    /**
     * Get the progress towards this target.
     *
     * @return array{current: int, target: int, percentage: int, remaining: int}
     */
    public function getProgress(): array
    {
        $current = $this->countPublishedInPeriod();
        $percentage = $this->target_count > 0
            ? min(100, round(($current / $this->target_count) * 100))
            : 0;

        return [
            'current' => $current,
            'target' => $this->target_count,
            'percentage' => (int) $percentage,
            'remaining' => max(0, $this->target_count - $current),
        ];
    }

    /**
     * Count published posts in this target's period.
     */
    public function countPublishedInPeriod(): int
    {
        $periodEnd = $this->getPeriodEnd();

        $query = Post::where('author_id', $this->user_id)
            ->where('status', Post::STATUS_PUBLISHED)
            ->whereBetween('published_at', [$this->period_start, $periodEnd]);

        // If category-specific, filter by category via pivot table
        if ($this->category_id !== null) {
            $query->whereHas('categories', function ($q) {
                $q->where('categories.id', $this->category_id);
            });
        }

        return $query->count();
    }

    /**
     * Get the end date of this target's period.
     */
    public function getPeriodEnd(): Carbon
    {
        return match ($this->period_type) {
            self::PERIOD_WEEKLY => $this->period_start->copy()->endOfWeek(),
            self::PERIOD_MONTHLY => $this->period_start->copy()->endOfMonth(),
            self::PERIOD_YEARLY => $this->period_start->copy()->endOfYear(),
            default => $this->period_start->copy()->endOfMonth(),
        };
    }

    /**
     * Get remaining days in the period.
     */
    public function getDaysRemaining(): int
    {
        $end = $this->getPeriodEnd();

        return max(0, now()->diffInDays($end, false));
    }

    /**
     * Calculate the start of a period for a given date.
     */
    public static function getPeriodStart(string $periodType, ?Carbon $date = null): Carbon
    {
        $date = $date ?? now();

        return match ($periodType) {
            self::PERIOD_WEEKLY => $date->copy()->startOfWeek(),
            self::PERIOD_MONTHLY => $date->copy()->startOfMonth(),
            self::PERIOD_YEARLY => $date->copy()->startOfYear(),
            default => $date->copy()->startOfMonth(),
        };
    }

    /**
     * Get the label for a period type.
     */
    public static function getPeriodLabel(string $periodType): string
    {
        return match ($periodType) {
            self::PERIOD_WEEKLY => 'Weekly',
            self::PERIOD_MONTHLY => 'Monthly',
            self::PERIOD_YEARLY => 'Yearly',
            default => ucfirst($periodType),
        };
    }

    /**
     * Get display label for this target.
     */
    public function getDisplayLabel(): string
    {
        $label = self::getPeriodLabel($this->period_type);

        if ($this->category) {
            $label .= ' - '.$this->category->name;
        }

        return $label;
    }
}
