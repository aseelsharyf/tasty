<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\UserTarget;
use App\Services\UserStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserTargetController extends Controller
{
    public function __construct(
        protected UserStatsService $statsService
    ) {}

    /**
     * Display targets page (admin sees all writers, user sees own).
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $periodType = $request->get('period', UserTarget::PERIOD_MONTHLY);

        $isAdmin = $user->hasAnyRole(['Admin', 'Editor', 'Developer']);

        if ($isAdmin) {
            return $this->adminIndex($request, $periodType);
        }

        return $this->userIndex($user, $periodType);
    }

    /**
     * Admin view: see all writers with targets.
     */
    protected function adminIndex(Request $request, string $periodType): Response
    {
        $periodStart = UserTarget::getPeriodStart($periodType);

        // Get all writers
        $writers = User::role(['Writer', 'Editor', 'Photographer'])
            ->with(['targets' => fn ($q) => $q->with(['category', 'assigner'])
                ->where('period_type', $periodType)
                ->where('period_start', $periodStart), ])
            ->orderBy('name')
            ->get()
            ->map(function ($writer) {
                $targets = $writer->targets->map(fn (UserTarget $target) => [
                    'id' => $target->id,
                    'target_count' => $target->target_count,
                    'category_id' => $target->category_id,
                    'category_name' => $target->category?->name,
                    'is_assigned' => $target->isAssigned(),
                    'assigned_by_name' => $target->assigner?->name,
                    'notes' => $target->notes,
                    'progress' => $target->getProgress(),
                ]);

                return [
                    'id' => $writer->id,
                    'uuid' => $writer->uuid,
                    'name' => $writer->name,
                    'email' => $writer->email,
                    'avatar_url' => $writer->avatar_url,
                    'targets' => $targets,
                ];
            });

        $categories = Category::orderBy('order')->get(['id', 'name', 'slug'])
            ->map(fn (Category $cat) => [
                'id' => $cat->id,
                'name' => $cat->name, // Uses translated accessor
                'slug' => $cat->slug,
            ]);

        return Inertia::render('Targets/Index', [
            'writers' => $writers,
            'categories' => $categories,
            'periodType' => $periodType,
            'periodLabel' => UserTarget::getPeriodLabel($periodType),
            'isAdmin' => true,
        ]);
    }

    /**
     * User view: see own targets.
     */
    protected function userIndex(User $user, string $periodType): Response
    {
        $targets = $this->statsService->getUserTargets($user, $periodType);
        $stats = $this->statsService->getWriterStats($user);
        $categories = Category::orderBy('order')->get(['id', 'name', 'slug'])
            ->map(fn (Category $cat) => [
                'id' => $cat->id,
                'name' => $cat->name, // Uses translated accessor
                'slug' => $cat->slug,
            ]);

        return Inertia::render('Targets/MyTargets', [
            'targets' => $targets,
            'stats' => $stats,
            'categories' => $categories,
            'periodType' => $periodType,
            'periodLabel' => UserTarget::getPeriodLabel($periodType),
            'isAdmin' => false,
        ]);
    }

    /**
     * Set own target (for regular users).
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'period_type' => ['required', 'in:weekly,monthly,yearly'],
            'target_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $periodStart = UserTarget::getPeriodStart($validated['period_type']);
        $categoryId = $validated['category_id'] ?? null;

        // Check if target already exists for this period + category
        $existingTarget = UserTarget::forUser($user->id)
            ->forPeriod($validated['period_type'])
            ->forCategory($categoryId)
            ->first();

        if ($existingTarget) {
            // Can only update if it's self-set
            if ($existingTarget->isAssigned()) {
                return redirect()->back()->withErrors([
                    'target' => 'This target was assigned by an admin and cannot be changed.',
                ]);
            }

            $existingTarget->update([
                'target_count' => $validated['target_count'],
            ]);

            return redirect()->back()->with('success', 'Target updated successfully.');
        }

        UserTarget::create([
            'user_id' => $user->id,
            'category_id' => $categoryId,
            'period_type' => $validated['period_type'],
            'period_start' => $periodStart,
            'target_count' => $validated['target_count'],
            'assigned_by' => null, // Self-set
        ]);

        return redirect()->back()->with('success', 'Target set successfully.');
    }

    /**
     * Update an existing target (user can only update self-set targets).
     */
    public function update(Request $request, UserTarget $target): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $target->canEdit($user)) {
            abort(403, 'You cannot edit this target.');
        }

        $validated = $request->validate([
            'target_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $target->update($validated);

        return redirect()->back()->with('success', 'Target updated successfully.');
    }

    /**
     * Assign target to a user (admin only).
     */
    public function assign(Request $request, User $targetUser): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasAnyRole(['Admin', 'Editor', 'Developer'])) {
            abort(403);
        }

        $validated = $request->validate([
            'period_type' => ['required', 'in:weekly,monthly,yearly'],
            'target_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $periodStart = UserTarget::getPeriodStart($validated['period_type']);
        $categoryId = $validated['category_id'] ?? null;

        UserTarget::updateOrCreate(
            [
                'user_id' => $targetUser->id,
                'category_id' => $categoryId,
                'period_type' => $validated['period_type'],
                'period_start' => $periodStart,
            ],
            [
                'target_count' => $validated['target_count'],
                'assigned_by' => $user->id,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        $categoryName = $categoryId ? Category::find($categoryId)?->name : 'Overall';

        return redirect()->back()->with('success', "Target ({$categoryName}) assigned to {$targetUser->name}.");
    }

    /**
     * Remove a target.
     */
    public function destroy(UserTarget $target): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $target->canEdit($user)) {
            abort(403, 'You cannot delete this target.');
        }

        $target->delete();

        return redirect()->back()->with('success', 'Target removed.');
    }

    /**
     * Get target data for API (used in dashboard).
     */
    public function current(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $periodType = $request->get('period', UserTarget::PERIOD_MONTHLY);

        $targets = $this->statsService->getUserTargets($user, $periodType);

        return response()->json($targets);
    }
}
