<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Services\UserStatsService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected UserStatsService $statsService
    ) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasAnyRole(['Admin', 'Editor', 'Developer'])) {
            return $this->adminDashboard($user);
        }

        return $this->writerDashboard($user);
    }

    /**
     * Dashboard for writers/photographers.
     */
    protected function writerDashboard(User $user): Response
    {
        $stats = $this->statsService->getWriterStats($user);
        $needsAttention = $this->statsService->getPostsNeedingAttention($user);

        // Get recent posts
        $recentPosts = Post::where('author_id', $user->id)
            ->withoutTrashed()
            ->orderByDesc('updated_at')
            ->take(5)
            ->get(['id', 'uuid', 'title', 'status', 'workflow_status', 'post_type', 'language_code', 'updated_at'])
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'status' => $post->status,
                'workflow_status' => $post->workflow_status,
                'post_type' => $post->post_type,
                'language_code' => $post->language_code,
                'updated_at' => $post->updated_at,
            ]);

        return Inertia::render('Dashboard/Writer', [
            'greeting' => $this->getGreeting($user),
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'needsAttention' => $needsAttention->map(fn (Post $post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'status' => $post->status,
                'workflow_status' => $post->workflow_status,
                'language_code' => $post->language_code,
                'updated_at' => $post->updated_at,
                'days_old' => $post->updated_at->diffInDays(now()),
            ]),
            'postTypes' => $this->getPostTypes(),
            'defaultLanguage' => $this->getDefaultLanguage(),
        ]);
    }

    /**
     * Dashboard for admins/editors.
     */
    protected function adminDashboard(User $user): Response
    {
        $stats = $this->statsService->getAdminStats();

        // Get posts pending review
        $pendingReview = Post::with(['author:id,name'])
            ->whereIn('workflow_status', ['review', 'copydesk'])
            ->orderByDesc('updated_at')
            ->take(5)
            ->get(['id', 'uuid', 'title', 'workflow_status', 'author_id', 'language_code', 'updated_at'])
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'workflow_status' => $post->workflow_status,
                'language_code' => $post->language_code,
                'author' => $post->author ? [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                ] : null,
                'updated_at' => $post->updated_at,
            ]);

        // Get recent activity
        $recentActivity = $this->statsService->getRecentActivity(10)
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'status' => $post->status,
                'workflow_status' => $post->workflow_status,
                'language_code' => $post->language_code,
                'author' => $post->author ? [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                ] : null,
                'updated_at' => $post->updated_at,
            ]);

        return Inertia::render('Dashboard/Admin', [
            'greeting' => $this->getGreeting($user),
            'stats' => $stats,
            'pendingReview' => $pendingReview,
            'recentActivity' => $recentActivity,
            'postTypes' => $this->getPostTypes(),
            'defaultLanguage' => $this->getDefaultLanguage(),
        ]);
    }

    /**
     * Get time-based greeting.
     */
    protected function getGreeting(User $user): string
    {
        $hour = now()->hour;
        $name = explode(' ', $user->name)[0]; // First name

        if ($hour < 12) {
            return "Good morning, {$name}!";
        } elseif ($hour < 17) {
            return "Good afternoon, {$name}!";
        } else {
            return "Good evening, {$name}!";
        }
    }

    /**
     * Get post types for quick-draft dropdown.
     */
    protected function getPostTypes(): array
    {
        return collect(Setting::getPostTypes())->map(fn ($type) => [
            'value' => $type['slug'],
            'label' => $type['name'],
            'icon' => $type['icon'] ?? null,
        ])->values()->all();
    }

    /**
     * Get default language code.
     */
    protected function getDefaultLanguage(): string
    {
        return config('localization.default_language', 'en');
    }
}
