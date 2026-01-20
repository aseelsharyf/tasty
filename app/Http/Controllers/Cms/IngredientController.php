<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreIngredientRequest;
use App\Http\Requests\Cms\UpdateIngredientRequest;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IngredientController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'slug', 'is_active', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'name';
        }

        $query = Ingredient::query();

        if ($request->filled('search')) {
            $query->search($request->get('search'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $direction);

        $ingredients = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Ingredient $ingredient) => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'name_dv' => $ingredient->name_dv,
                'slug' => $ingredient->slug,
                'is_active' => $ingredient->is_active,
                'created_at' => $ingredient->created_at,
            ]);

        return Inertia::render('Ingredients/Index', [
            'ingredients' => $ingredients,
            'filters' => $request->only(['search', 'sort', 'direction', 'is_active']),
        ]);
    }

    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        Ingredient::create($request->validated());

        return redirect()->route('cms.ingredients.index')
            ->with('success', 'Ingredient created successfully.');
    }

    public function edit(Request $request, Ingredient $ingredient): Response|\Illuminate\Http\JsonResponse
    {
        $ingredientData = [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'name_dv' => $ingredient->name_dv,
            'slug' => $ingredient->slug,
            'is_active' => $ingredient->is_active,
            'created_at' => $ingredient->created_at,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'ingredient' => $ingredientData,
                ],
            ]);
        }

        return Inertia::render('Ingredients/Edit', [
            'ingredient' => $ingredientData,
        ]);
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $ingredient->update($request->validated());

        return redirect()->route('cms.ingredients.index')
            ->with('success', 'Ingredient updated successfully.');
    }

    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $ingredient->delete();

        return redirect()->route('cms.ingredients.index')
            ->with('success', 'Ingredient deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:ingredients,id'],
        ]);

        $count = Ingredient::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.ingredients.index')
            ->with('success', "{$count} ingredients deleted successfully.");
    }

    /**
     * Search ingredients for autocomplete (JSON API).
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->get('q', '');
        $limit = min((int) $request->get('limit', 20), 50);

        $query = Ingredient::query()->active();

        if ($search) {
            $query->search($search);
        }

        $ingredients = $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Ingredient $ingredient) => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'name_dv' => $ingredient->name_dv,
                'slug' => $ingredient->slug,
            ]);

        return response()->json($ingredients);
    }
}
