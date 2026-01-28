<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Models\MediaItem;
use App\Models\Post;
use App\Models\RecipeSubmission;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RecipeSubmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $status = $request->get('status', 'pending');

        $allowedSorts = ['recipe_name', 'submitter_name', 'submitter_email', 'status', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $query = RecipeSubmission::query()
            ->whereNull('parent_submission_id')
            ->with(['reviewer', 'convertedPost', 'childSubmissions']);

        // Filter by status
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by submission type
        if ($request->filled('type')) {
            $query->where('submission_type', $request->get('type'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('recipe_name', 'ilike', "%{$search}%")
                    ->orWhere('submitter_name', 'ilike', "%{$search}%")
                    ->orWhere('submitter_email', 'ilike', "%{$search}%");
            });
        }

        // Sort
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $direction);

        $submissions = $query->paginate(20)
            ->withQueryString()
            ->through(fn (RecipeSubmission $submission) => [
                'id' => $submission->id,
                'uuid' => $submission->uuid,
                'submission_type' => $submission->submission_type,
                'recipe_name' => $submission->recipe_name,
                'slug' => $submission->slug,
                'submitter_name' => $submission->submitter_name,
                'submitter_email' => $submission->submitter_email,
                'is_chef' => $submission->is_chef,
                'chef_name' => $submission->chef_name,
                'status' => $submission->status,
                'image_url' => $submission->image_path ? Storage::url($submission->image_path) : null,
                'child_count' => $submission->childSubmissions->count(),
                'reviewer' => $submission->reviewer ? [
                    'id' => $submission->reviewer->id,
                    'name' => $submission->reviewer->name,
                ] : null,
                'reviewed_at' => $submission->reviewed_at?->format('Y-m-d H:i'),
                'converted_post' => $submission->convertedPost ? [
                    'id' => $submission->convertedPost->id,
                    'uuid' => $submission->convertedPost->uuid,
                    'title' => $submission->convertedPost->title,
                ] : null,
                'created_at' => $submission->created_at->format('Y-m-d H:i'),
            ]);

        // Get counts for status tabs
        $counts = [
            'all' => RecipeSubmission::whereNull('parent_submission_id')->count(),
            'pending' => RecipeSubmission::whereNull('parent_submission_id')->pending()->count(),
            'approved' => RecipeSubmission::whereNull('parent_submission_id')->approved()->count(),
            'rejected' => RecipeSubmission::whereNull('parent_submission_id')->rejected()->count(),
            'converted' => RecipeSubmission::whereNull('parent_submission_id')->converted()->count(),
        ];

        return Inertia::render('RecipeSubmissions/Index', [
            'submissions' => $submissions,
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'type']),
            'counts' => $counts,
        ]);
    }

    public function show(RecipeSubmission $submission): Response
    {
        $submission->load(['reviewer', 'convertedPost', 'childSubmissions', 'parentSubmission']);

        // Get category names
        $categoryNames = [];
        if ($submission->categories) {
            $categoryNames = Category::whereIn('slug', $submission->categories)
                ->get()
                ->pluck('name')
                ->toArray();
        }

        // Get authors for convert modal
        $authors = User::orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        // Get languages for convert modal
        $languages = Language::active()
            ->ordered()
            ->get(['code', 'name', 'native_name'])
            ->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
            ]);

        return Inertia::render('RecipeSubmissions/Show', [
            'submission' => [
                'id' => $submission->id,
                'uuid' => $submission->uuid,
                'submission_type' => $submission->submission_type,
                'submitter_name' => $submission->submitter_name,
                'submitter_email' => $submission->submitter_email,
                'submitter_phone' => $submission->submitter_phone,
                'is_chef' => $submission->is_chef,
                'chef_name' => $submission->chef_name,
                'chef_display_name' => $submission->getChefDisplayName(),
                'recipe_name' => $submission->recipe_name,
                'headline' => $submission->headline,
                'slug' => $submission->slug,
                'description' => $submission->description,
                'prep_time' => $submission->prep_time,
                'cook_time' => $submission->cook_time,
                'total_time' => $submission->total_time,
                'servings' => $submission->servings,
                'categories' => $submission->categories,
                'category_names' => $categoryNames,
                'meal_times' => $submission->meal_times,
                'ingredients' => $submission->ingredients,
                'instructions' => $submission->instructions,
                'image_url' => $submission->image_path ? Storage::url($submission->image_path) : null,
                'status' => $submission->status,
                'review_notes' => $submission->review_notes,
                'reviewer' => $submission->reviewer ? [
                    'id' => $submission->reviewer->id,
                    'name' => $submission->reviewer->name,
                ] : null,
                'reviewed_at' => $submission->reviewed_at?->format('Y-m-d H:i'),
                'converted_post' => $submission->convertedPost ? [
                    'id' => $submission->convertedPost->id,
                    'uuid' => $submission->convertedPost->uuid,
                    'title' => $submission->convertedPost->title,
                    'status' => $submission->convertedPost->status,
                ] : null,
                'child_submissions' => $submission->childSubmissions->map(fn ($child) => [
                    'id' => $child->id,
                    'uuid' => $child->uuid,
                    'recipe_name' => $child->recipe_name,
                    'description' => $child->description,
                    'status' => $child->status,
                ]),
                'parent_submission' => $submission->parentSubmission ? [
                    'id' => $submission->parentSubmission->id,
                    'uuid' => $submission->parentSubmission->uuid,
                    'recipe_name' => $submission->parentSubmission->recipe_name,
                ] : null,
                'created_at' => $submission->created_at->format('Y-m-d H:i'),
                'updated_at' => $submission->updated_at->format('Y-m-d H:i'),
            ],
            'authors' => $authors,
            'languages' => $languages,
        ]);
    }

    public function approve(Request $request, RecipeSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Approve the submission
        $submission->approve($request->user(), $validated['notes'] ?? null);

        // Auto-convert to post
        return $this->convertSubmissionToPost($request, $submission);
    }

    /**
     * Convert an approved submission to a post automatically.
     */
    protected function convertSubmissionToPost(Request $request, RecipeSubmission $submission): RedirectResponse
    {
        $languageCode = Language::active()->ordered()->first()?->code ?? 'en';
        $authorId = $request->user()->id;

        // Get the category IDs
        $categoryIds = [];
        if ($submission->categories) {
            $categoryIds = Category::whereIn('slug', $submission->categories)
                ->pluck('id')
                ->toArray();
        }

        // Generate unique slug
        $baseSlug = $submission->slug;
        $slug = $baseSlug;
        $counter = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        // Convert ingredients to the expected format: [{ section: "Name", items: ["ingredient 1"] }]
        $formattedIngredients = $this->formatIngredients($submission->ingredients);

        // Build custom fields for recipe
        $customFields = [
            'prep_time' => (string) ($submission->prep_time ?? ''),
            'cook_time' => (string) ($submission->cook_time ?? ''),
            'servings' => (string) ($submission->servings ?? ''),
            'introduction' => '',
            'ingredients' => $formattedIngredients,
        ];

        // Build content blocks: collapsible for each instruction step
        $contentBlocks = [];

        // Add instruction steps as collapsibles
        if ($submission->instructions) {
            foreach ($submission->instructions as $index => $group) {
                $stepName = $group['group_name'] ?? '';
                $stepTitle = 'Step '.($index + 1).($stepName ? ': '.$stepName : '');
                $stepContent = $group['steps'][0] ?? '';

                // Create collapsible block with nested paragraph
                $contentBlocks[] = [
                    'id' => Str::random(10),
                    'type' => 'collapsible',
                    'data' => [
                        'title' => $stepTitle,
                        'expanded' => false,
                        'content' => [
                            'time' => now()->timestamp * 1000,
                            'blocks' => [
                                [
                                    'id' => Str::random(10),
                                    'type' => 'paragraph',
                                    'data' => ['text' => $stepContent],
                                ],
                            ],
                            'version' => '2.28.2',
                        ],
                    ],
                ];
            }
        }

        // Handle featured image - create MediaItem from submission image
        $featuredMediaId = null;
        if ($submission->image_path) {
            $mediaItem = MediaItem::create([
                'type' => MediaItem::TYPE_IMAGE,
                'category' => MediaItem::CATEGORY_MEDIA,
                'title' => ['en' => $submission->recipe_name],
                'alt_text' => ['en' => $submission->recipe_name],
                'uploaded_by' => $authorId,
            ]);

            // Copy the file from submission to media item
            $sourcePath = Storage::disk('public')->path($submission->image_path);
            if (file_exists($sourcePath)) {
                $mediaItem->addMedia($sourcePath)
                    ->preservingOriginal()
                    ->toMediaCollection('default');
                $featuredMediaId = $mediaItem->id;
            }
        }

        // Create the post
        // Kicker = Recipe Name, Headline (subtitle) = headline field, Title = Recipe Name
        // Description goes to excerpt (deck field)
        $post = Post::create([
            'uuid' => Str::uuid(),
            'author_id' => $authorId,
            'language_code' => $languageCode,
            'title' => $submission->recipe_name,
            'kicker' => $submission->recipe_name,
            'subtitle' => $submission->headline ?? '',
            'slug' => $slug,
            'excerpt' => $submission->description ?? '',
            'content' => [
                'time' => now()->timestamp * 1000,
                'blocks' => $contentBlocks,
                'version' => '2.28.2',
            ],
            'post_type' => Post::TYPE_RECIPE,
            'status' => Post::STATUS_DRAFT,
            'workflow_status' => 'draft',
            'custom_fields' => $customFields,
            'featured_media_id' => $featuredMediaId,
            'allow_comments' => true,
            'show_author' => true,
        ]);

        // Attach categories
        if (! empty($categoryIds)) {
            $post->categories()->attach($categoryIds);
        }

        // Create/attach meal times as tags
        if ($submission->meal_times) {
            $tagIds = [];
            foreach ($submission->meal_times as $mealTime) {
                $tagName = ucwords(str_replace('-', ' ', $mealTime));
                $tagSlug = Str::slug($mealTime);

                $tag = Tag::firstOrCreate(
                    ['slug' => $tagSlug],
                    ['name' => ['en' => $tagName]]
                );
                $tagIds[] = $tag->id;
            }
            $post->tags()->attach($tagIds);
        }

        // Mark submission as converted
        $submission->markAsConverted($post);

        return redirect()
            ->route('cms.posts.edit', ['language' => $languageCode, 'post' => $post->uuid])
            ->with('success', 'Recipe approved and converted to post. Please review and publish when ready.');
    }

    public function reject(Request $request, RecipeSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $submission->reject($request->user(), $validated['notes'] ?? null);

        return redirect()->back()->with('success', 'Submission rejected.');
    }

    public function convertToPost(Request $request, RecipeSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'language_code' => ['required', 'string', 'exists:languages,code'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'create_author' => ['nullable', 'boolean'],
        ]);

        // Determine the author
        $authorId = $validated['author_id'] ?? $request->user()->id;

        // Create a new "ghost" author if requested
        if (! empty($validated['create_author']) && $submission->getChefDisplayName()) {
            $ghostAuthor = User::firstOrCreate(
                ['email' => 'contributor_'.Str::slug($submission->getChefDisplayName()).'@tasty.mv'],
                [
                    'name' => $submission->getChefDisplayName(),
                    'password' => bcrypt(Str::random(32)), // Random password - they can't login
                ]
            );
            $authorId = $ghostAuthor->id;
        }

        // Get the category IDs
        $categoryIds = [];
        if ($submission->categories) {
            $categoryIds = Category::whereIn('slug', $submission->categories)
                ->pluck('id')
                ->toArray();
        }

        // Generate unique slug
        $baseSlug = $submission->slug;
        $slug = $baseSlug;
        $counter = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        // Convert ingredients to the expected format: [{ section: "Name", items: ["ingredient 1"] }]
        $formattedIngredients = $this->formatIngredients($submission->ingredients);

        // Build custom fields for recipe
        $customFields = [
            'prep_time' => (string) ($submission->prep_time ?? ''),
            'cook_time' => (string) ($submission->cook_time ?? ''),
            'servings' => (string) ($submission->servings ?? ''),
            'introduction' => '',
            'ingredients' => $formattedIngredients,
        ];

        // Build content blocks from instructions
        $contentBlocks = [];

        // Add instruction steps as content blocks
        if ($submission->instructions) {
            foreach ($submission->instructions as $group) {
                // Add section header if group has a name
                if (! empty($group['group_name'])) {
                    $contentBlocks[] = [
                        'id' => Str::random(10),
                        'type' => 'header',
                        'data' => ['text' => $group['group_name'], 'level' => 3],
                    ];
                }

                // Add steps as ordered list
                if (! empty($group['steps'])) {
                    $contentBlocks[] = [
                        'id' => Str::random(10),
                        'type' => 'list',
                        'data' => [
                            'style' => 'ordered',
                            'items' => array_values($group['steps']),
                        ],
                    ];
                }
            }
        }

        // Create the post
        // Description goes to excerpt (deck field)
        $post = Post::create([
            'uuid' => Str::uuid(),
            'author_id' => $authorId,
            'language_code' => $validated['language_code'],
            'title' => $submission->recipe_name,
            'slug' => $slug,
            'excerpt' => $submission->description ?? '',
            'content' => [
                'time' => now()->timestamp * 1000,
                'blocks' => $contentBlocks,
                'version' => '2.28.2',
            ],
            'post_type' => Post::TYPE_RECIPE,
            'status' => Post::STATUS_DRAFT,
            'workflow_status' => 'draft',
            'custom_fields' => $customFields,
            'allow_comments' => true,
            'show_author' => true,
        ]);

        // Attach categories
        if (! empty($categoryIds)) {
            $post->categories()->attach($categoryIds);
        }

        // Create/attach meal times as tags
        if ($submission->meal_times) {
            $tagIds = [];
            foreach ($submission->meal_times as $mealTime) {
                // Format meal time name (e.g., "evening-tea" -> "Evening Tea")
                $tagName = ucwords(str_replace('-', ' ', $mealTime));
                $tagSlug = Str::slug($mealTime);

                // Find or create the tag
                $tag = Tag::firstOrCreate(
                    ['slug' => $tagSlug],
                    ['name' => ['en' => $tagName]]
                );
                $tagIds[] = $tag->id;
            }
            $post->tags()->attach($tagIds);
        }

        // Mark submission as converted
        $submission->markAsConverted($post);

        return redirect()
            ->route('cms.posts.edit', ['language' => $validated['language_code'], 'post' => $post->uuid])
            ->with('success', 'Recipe converted to post. Please review and publish when ready.');
    }

    /**
     * Format submission ingredients into the post custom_fields format.
     *
     * @param  array<int, array{group_name: ?string, items: array<int, array{ingredient: string, quantity: ?string, unit: ?string, prep_note: ?string}>}>|null  $ingredients
     * @return array<int, array{section: string, items: array<int, string>}>
     */
    private function formatIngredients(?array $ingredients): array
    {
        if (! $ingredients) {
            return [];
        }

        $formatted = [];
        foreach ($ingredients as $group) {
            $items = [];
            if (! empty($group['items'])) {
                foreach ($group['items'] as $item) {
                    $ingredientParts = [];
                    if (! empty($item['quantity'])) {
                        $ingredientParts[] = $item['quantity'];
                    }
                    if (! empty($item['unit'])) {
                        $ingredientParts[] = $item['unit'];
                    }
                    $ingredientParts[] = $item['ingredient'] ?? '';
                    if (! empty($item['prep_note'])) {
                        $ingredientParts[] = '('.$item['prep_note'].')';
                    }
                    $items[] = trim(implode(' ', $ingredientParts));
                }
            }
            $formatted[] = [
                'section' => $group['group_name'] ?? 'Ingredients',
                'items' => $items,
            ];
        }

        return $formatted;
    }

    public function destroy(RecipeSubmission $submission): RedirectResponse
    {
        // Delete associated image
        if ($submission->image_path) {
            Storage::disk('public')->delete($submission->image_path);
        }

        // Delete child submissions
        foreach ($submission->childSubmissions as $child) {
            $child->delete();
        }

        $submission->delete();

        return redirect()->route('cms.recipe-submissions.index')
            ->with('success', 'Submission deleted successfully.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:recipe_submissions,id'],
            'action' => ['required', 'in:approve,reject,delete'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];
        $notes = $validated['notes'] ?? null;

        $submissions = RecipeSubmission::whereIn('id', $ids)->get();

        foreach ($submissions as $submission) {
            switch ($action) {
                case 'approve':
                    $submission->approve($request->user(), $notes);
                    break;
                case 'reject':
                    $submission->reject($request->user(), $notes);
                    break;
                case 'delete':
                    if ($submission->image_path) {
                        Storage::disk('public')->delete($submission->image_path);
                    }
                    $submission->childSubmissions()->delete();
                    $submission->delete();
                    break;
            }
        }

        $actionText = match ($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'delete' => 'deleted',
        };

        return redirect()->back()
            ->with('success', count($ids)." submission(s) {$actionText} successfully.");
    }
}
