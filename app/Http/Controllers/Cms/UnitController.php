<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreUnitRequest;
use App\Http\Requests\Cms\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'abbreviation', 'is_active', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'name';
        }

        $query = Unit::query();

        if ($request->filled('search')) {
            $query->search($request->get('search'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $direction);

        $units = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Unit $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'name_dv' => $unit->name_dv,
                'abbreviation' => $unit->abbreviation,
                'abbreviation_dv' => $unit->abbreviation_dv,
                'is_active' => $unit->is_active,
                'created_at' => $unit->created_at,
            ]);

        return Inertia::render('Units/Index', [
            'units' => $units,
            'filters' => $request->only(['search', 'sort', 'direction', 'is_active']),
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        return redirect()->route('cms.units.index')
            ->with('success', 'Unit created successfully.');
    }

    public function edit(Request $request, Unit $unit): Response|\Illuminate\Http\JsonResponse
    {
        $unitData = [
            'id' => $unit->id,
            'name' => $unit->name,
            'name_dv' => $unit->name_dv,
            'abbreviation' => $unit->abbreviation,
            'abbreviation_dv' => $unit->abbreviation_dv,
            'is_active' => $unit->is_active,
            'created_at' => $unit->created_at,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'props' => [
                    'unit' => $unitData,
                ],
            ]);
        }

        return Inertia::render('Units/Edit', [
            'unit' => $unitData,
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()->route('cms.units.index')
            ->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return redirect()->route('cms.units.index')
            ->with('success', 'Unit deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:units,id'],
        ]);

        $count = Unit::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.units.index')
            ->with('success', "{$count} units deleted successfully.");
    }

    /**
     * Search units for autocomplete (JSON API).
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->get('q', '');
        $limit = min((int) $request->get('limit', 20), 50);

        $query = Unit::query()->active();

        if ($search) {
            $query->search($search);
        }

        $units = $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'name_dv' => $unit->name_dv,
                'abbreviation' => $unit->abbreviation,
                'abbreviation_dv' => $unit->abbreviation_dv,
            ]);

        return response()->json($units);
    }
}
