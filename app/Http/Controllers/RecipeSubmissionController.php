<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipeSubmissionRequest;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\RecipeSubmission;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RecipeSubmissionController extends Controller
{
    public function create(): View
    {
        // Get only recipe subcategories (children of the "Recipe" parent category)
        $recipeParent = Category::where('slug', 'recipe')->first();
        $categories = $recipeParent
            ? Category::where('parent_id', $recipeParent->id)
                ->orderBy('order')
                ->get(['id', 'name', 'slug'])
            : collect();

        $ingredients = Ingredient::active()->orderBy('name')->get(['id', 'name', 'name_dv', 'slug']);
        $units = Unit::active()->orderBy('name')->get(['id', 'name', 'name_dv', 'abbreviation']);

        $user = Auth::user();
        $authUser = $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar ?? $user->avatar_url,
        ] : null;

        return view('recipes.submit', compact('categories', 'ingredients', 'units', 'authUser'));
    }

    public function store(StoreRecipeSubmissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('recipe-submissions', 'public');
        }

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('submitter_avatar')) {
            $avatarPath = $request->file('submitter_avatar')->store('recipe-submissions/avatars', 'public');
        }

        // Create main submission
        $submission = RecipeSubmission::create([
            'uuid' => Str::uuid(),
            'submission_type' => $validated['submission_type'],
            'submitter_name' => $validated['submitter_name'],
            'submitter_email' => $validated['submitter_email'],
            'submitter_phone' => $validated['submitter_phone'] ?? null,
            'submitter_avatar' => $avatarPath,
            'is_chef' => $validated['is_chef'],
            'chef_name' => $validated['chef_name'] ?? null,
            'recipe_name' => $validated['recipe_name'],
            'slug' => Str::slug($validated['recipe_name']),
            'description' => $validated['description'],
            'prep_time' => $validated['prep_time'] ?? null,
            'cook_time' => $validated['cook_time'] ?? null,
            'servings' => $validated['servings'] ?? null,
            'categories' => $validated['categories'] ?? [],
            'meal_times' => $validated['meal_times'] ?? [],
            'ingredients' => $validated['ingredients'],
            'instructions' => $validated['instructions'],
            'image_path' => $imagePath,
            'status' => RecipeSubmission::STATUS_PENDING,
        ]);

        // Handle composite meal child recipes
        if ($validated['submission_type'] === RecipeSubmission::TYPE_COMPOSITE && ! empty($validated['child_recipes'])) {
            foreach ($validated['child_recipes'] as $childRecipe) {
                RecipeSubmission::create([
                    'uuid' => Str::uuid(),
                    'submission_type' => RecipeSubmission::TYPE_SINGLE,
                    'submitter_name' => $validated['submitter_name'],
                    'submitter_email' => $validated['submitter_email'],
                    'is_chef' => $validated['is_chef'],
                    'chef_name' => $validated['chef_name'] ?? null,
                    'recipe_name' => $childRecipe['recipe_name'],
                    'slug' => Str::slug($childRecipe['recipe_name']),
                    'description' => $childRecipe['description'],
                    'categories' => $validated['categories'] ?? [],
                    'parent_submission_id' => $submission->id,
                    'status' => RecipeSubmission::STATUS_PENDING,
                ]);
            }

            // Store child recipe references in parent
            $submission->update([
                'child_recipes' => $validated['child_recipes'],
            ]);
        }

        return redirect()->route('recipes.submit.success')
            ->with('submission_id', $submission->uuid);
    }

    public function success(): View
    {
        return view('recipes.submit-success');
    }

    public function apiIngredients(): \Illuminate\Http\JsonResponse
    {
        $ingredients = Ingredient::active()
            ->orderBy('name')
            ->get(['id', 'name', 'name_dv', 'slug']);

        return response()->json($ingredients);
    }

    public function apiUnits(): \Illuminate\Http\JsonResponse
    {
        $units = Unit::active()
            ->orderBy('name')
            ->get(['id', 'name', 'name_dv', 'abbreviation']);

        return response()->json($units);
    }
}
