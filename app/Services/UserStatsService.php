<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\UserTarget;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserStatsService
{
    /**
     * Get stats for a writer.
     *
     * @return array<string, mixed>
     */
    public function getWriterStats(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        $postsQuery = Post::where('author_id', $user->id);

        // Basic counts
        $totalPosts = $postsQuery->clone()->withoutTrashed()->count();
        $publishedThisPeriod = $postsQuery->clone()
            ->where('status', Post::STATUS_PUBLISHED)
            ->whereBetween('published_at', [$from, $to])
            ->count();

        $inReview = $postsQuery->clone()
            ->whereIn('workflow_status', ['review', 'copydesk'])
            ->count();

        $drafts = $postsQuery->clone()
            ->where('status', Post::STATUS_DRAFT)
            ->count();

        $rejected = $postsQuery->clone()
            ->where('workflow_status', 'rejected')
            ->count();

        // Posts by type
        $byPostType = $postsQuery->clone()
            ->withoutTrashed()
            ->select('post_type', DB::raw('count(*) as count'))
            ->groupBy('post_type')
            ->get()
            ->map(fn ($item) => [
                'type' => $item->post_type,
                'count' => $item->count,
            ])
            ->toArray();

        // Average time to publish (draft to published)
        $avgDaysToPublish = $this->calculateAverageTimeToPublish($user);

        // Current overall target (no category)
        $currentTarget = $user->getCurrentTarget(UserTarget::PERIOD_MONTHLY);
        $targetProgress = $currentTarget ? $currentTarget->getProgress() : null;

        // Activity streak
        $streak = $this->calculateStreak($user);

        return [
            'total_posts' => $totalPosts,
            'published_this_period' => $publishedThisPeriod,
            'in_review' => $inReview,
            'drafts' => $drafts,
            'rejected' => $rejected,
            'avg_days_to_publish' => $avgDaysToPublish,
            'by_post_type' => $byPostType,
            'current_target' => $currentTarget ? [
                'id' => $currentTarget->id,
                'period' => $currentTarget->period_type,
                'target_count' => $currentTarget->target_count,
                'is_assigned' => $currentTarget->isAssigned(),
                'days_remaining' => $currentTarget->getDaysRemaining(),
                'progress' => $targetProgress,
                'category_id' => $currentTarget->category_id,
                'category_name' => $currentTarget->category?->name,
            ] : null,
            'streak' => $streak,
        ];
    }

    /**
     * Get all current targets for a user (overall + category-specific).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUserTargets(User $user, string $periodType = UserTarget::PERIOD_MONTHLY): array
    {
        $targets = UserTarget::with(['category', 'assigner'])
            ->forUser($user->id)
            ->current($periodType)
            ->orderByRaw('category_id IS NULL DESC') // Overall first
            ->orderBy('category_id')
            ->get();

        return $targets->map(fn (UserTarget $target) => [
            'id' => $target->id,
            'period_type' => $target->period_type,
            'target_count' => $target->target_count,
            'category_id' => $target->category_id,
            'category_name' => $target->category?->name,
            'is_assigned' => $target->isAssigned(),
            'assigned_by_name' => $target->assigner?->name,
            'notes' => $target->notes,
            'can_edit' => $target->canEdit($user),
            'days_remaining' => $target->getDaysRemaining(),
            'progress' => $target->getProgress(),
            'display_label' => $target->getDisplayLabel(),
        ])->toArray();
    }

    /**
     * Get admin/editor stats overview.
     *
     * @return array<string, mixed>
     */
    public function getAdminStats(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        // Overall counts
        $totalPosts = Post::withoutTrashed()->count();
        $publishedToday = Post::where('status', Post::STATUS_PUBLISHED)
            ->whereDate('published_at', today())
            ->count();
        $publishedThisWeek = Post::where('status', Post::STATUS_PUBLISHED)
            ->whereBetween('published_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $publishedThisMonth = Post::where('status', Post::STATUS_PUBLISHED)
            ->whereBetween('published_at', [$from, $to])
            ->count();
        $pendingReview = Post::whereIn('workflow_status', ['review', 'copydesk'])->count();

        // Writer stats
        $totalWriters = User::role(['Writer', 'Editor', 'Admin'])->count();
        $activeWriters = Post::whereBetween('created_at', [now()->subDays(7), now()])
            ->distinct('author_id')
            ->count('author_id');

        // Top writers this month
        $topWriters = $this->getTopWriters($from, $to, 5);

        // Posts per day (for chart)
        $postsPerDay = $this->getPostsPerDay(14);

        // Posts by type
        $postsByType = Post::withoutTrashed()
            ->select('post_type', DB::raw('count(*) as count'))
            ->groupBy('post_type')
            ->get()
            ->map(fn ($item) => [
                'type' => $item->post_type,
                'count' => $item->count,
            ])
            ->toArray();

        // Posts by workflow status
        $postsByStatus = Post::withoutTrashed()
            ->select('workflow_status', DB::raw('count(*) as count'))
            ->groupBy('workflow_status')
            ->get()
            ->map(fn ($item) => [
                'status' => $item->workflow_status ?? 'none',
                'count' => $item->count,
            ])
            ->toArray();

        return [
            'total_posts' => $totalPosts,
            'published_today' => $publishedToday,
            'published_this_week' => $publishedThisWeek,
            'published_this_month' => $publishedThisMonth,
            'pending_review' => $pendingReview,
            'total_writers' => $totalWriters,
            'active_writers' => $activeWriters,
            'top_writers' => $topWriters,
            'posts_per_day' => $postsPerDay,
            'posts_by_type' => $postsByType,
            'posts_by_status' => $postsByStatus,
        ];
    }

    /**
     * Get top writers by published posts.
     */
    public function getTopWriters(?Carbon $from = null, ?Carbon $to = null, int $limit = 10): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        $writers = Post::where('status', Post::STATUS_PUBLISHED)
            ->whereBetween('published_at', [$from, $to])
            ->select('author_id', DB::raw('count(*) as posts_count'))
            ->groupBy('author_id')
            ->orderByDesc('posts_count')
            ->limit($limit)
            ->with('author:id,name,uuid')
            ->get();

        return $writers->map(function ($item) {
            $user = User::find($item->author_id);
            $target = $user?->getCurrentTarget(UserTarget::PERIOD_MONTHLY);

            return [
                'user' => $user ? [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ] : null,
                'posts_count' => $item->posts_count,
                'target_progress' => $target ? $target->getProgress()['percentage'] : null,
            ];
        })->filter(fn ($item) => $item['user'] !== null)->values()->toArray();
    }

    /**
     * Get posts per day for chart.
     */
    public function getPostsPerDay(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();

        $posts = Post::where('status', Post::STATUS_PUBLISHED)
            ->where('published_at', '>=', $startDate)
            ->select(DB::raw('DATE(published_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[] = [
                'date' => $date,
                'count' => $posts->get($date)?->count ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Calculate average time from draft to publish for a user.
     */
    protected function calculateAverageTimeToPublish(User $user): ?float
    {
        $publishedPosts = Post::where('author_id', $user->id)
            ->where('status', Post::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->select('created_at', 'published_at')
            ->limit(50) // Last 50 posts for performance
            ->orderByDesc('published_at')
            ->get();

        if ($publishedPosts->isEmpty()) {
            return null;
        }

        $totalDays = $publishedPosts->sum(function ($post) {
            return $post->created_at->diffInDays($post->published_at);
        });

        return round($totalDays / $publishedPosts->count(), 1);
    }

    /**
     * Calculate activity streak for a user.
     *
     * @return array{current: int, best: int, unit: string}
     */
    protected function calculateStreak(User $user): array
    {
        // Get dates when user published posts (last 90 days)
        $activeDates = Post::where('author_id', $user->id)
            ->where('status', Post::STATUS_PUBLISHED)
            ->where('published_at', '>=', now()->subDays(90))
            ->select(DB::raw('DATE(published_at) as date'))
            ->distinct()
            ->orderByDesc('date')
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        if (empty($activeDates)) {
            return ['current' => 0, 'best' => 0, 'unit' => 'days'];
        }

        // Calculate current streak (consecutive days from today)
        $currentStreak = 0;
        $checkDate = now()->format('Y-m-d');

        // Allow for today or yesterday to start the streak
        if (! in_array($checkDate, $activeDates)) {
            $checkDate = now()->subDay()->format('Y-m-d');
        }

        while (in_array($checkDate, $activeDates)) {
            $currentStreak++;
            $checkDate = Carbon::parse($checkDate)->subDay()->format('Y-m-d');
        }

        // Calculate best streak
        $bestStreak = $currentStreak;
        $tempStreak = 1;

        for ($i = 1; $i < count($activeDates); $i++) {
            $prev = Carbon::parse($activeDates[$i - 1]);
            $curr = Carbon::parse($activeDates[$i]);

            if ($prev->diffInDays($curr) === 1) {
                $tempStreak++;
                $bestStreak = max($bestStreak, $tempStreak);
            } else {
                $tempStreak = 1;
            }
        }

        return [
            'current' => $currentStreak,
            'best' => $bestStreak,
            'unit' => 'days',
        ];
    }

    /**
     * Get posts needing attention for a user.
     */
    public function getPostsNeedingAttention(User $user, int $limit = 5): Collection
    {
        // Posts that are rejected or drafts sitting for too long
        return Post::where('author_id', $user->id)
            ->where(function ($q) {
                // Rejected posts
                $q->where('workflow_status', 'rejected')
                    // Or drafts older than 3 days
                    ->orWhere(function ($q2) {
                        $q2->where('status', Post::STATUS_DRAFT)
                            ->where('updated_at', '<', now()->subDays(3));
                    });
            })
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'uuid', 'title', 'status', 'workflow_status', 'updated_at', 'language_code']);
    }

    /**
     * Get recent activity for admin dashboard.
     */
    public function getRecentActivity(int $limit = 10): Collection
    {
        return Post::with(['author:id,name'])
            ->whereIn('status', [Post::STATUS_PUBLISHED, Post::STATUS_DRAFT])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'uuid', 'title', 'status', 'workflow_status', 'author_id', 'updated_at', 'language_code']);
    }

    /**
     * Get all writers with their target progress.
     */
    public function getWritersWithTargets(string $periodType = UserTarget::PERIOD_MONTHLY): Collection
    {
        $periodStart = UserTarget::getPeriodStart($periodType);

        return User::role(['Writer', 'Editor'])
            ->with(['targets' => fn ($q) => $q->where('period_type', $periodType)
                ->where('period_start', $periodStart), ])
            ->withCount(['posts as published_count' => function ($q) use ($periodStart, $periodType) {
                $periodEnd = match ($periodType) {
                    UserTarget::PERIOD_WEEKLY => $periodStart->copy()->endOfWeek(),
                    UserTarget::PERIOD_MONTHLY => $periodStart->copy()->endOfMonth(),
                    UserTarget::PERIOD_YEARLY => $periodStart->copy()->endOfYear(),
                    default => $periodStart->copy()->endOfMonth(),
                };

                $q->where('status', Post::STATUS_PUBLISHED)
                    ->whereBetween('published_at', [$periodStart, $periodEnd]);
            }])
            ->get()
            ->map(function ($user) {
                $target = $user->targets->first();

                return [
                    'user' => [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'avatar_url' => $user->avatar_url,
                    ],
                    'target' => $target?->target_count,
                    'current' => $user->published_count,
                    'percentage' => $target && $target->target_count > 0
                        ? min(100, round(($user->published_count / $target->target_count) * 100))
                        : null,
                    'is_assigned' => $target?->isAssigned() ?? false,
                    'target_id' => $target?->id,
                ];
            });
    }
}
