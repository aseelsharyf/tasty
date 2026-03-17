<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryLocationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DeliveryLocation::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereRaw('name::text ILIKE ?', ["%{$search}%"]);
        }

        $query->orderBy('order');

        $activeLanguages = Language::active()->ordered()->get();

        $locations = $query->paginate(20)
            ->withQueryString()
            ->through(fn (DeliveryLocation $location) => [
                'id' => $location->id,
                'uuid' => $location->uuid,
                'name' => $location->name,
                'is_active' => $location->is_active,
                'order' => $location->order,
                'translated_locales' => array_keys($location->getTranslations('name')),
            ]);

        return Inertia::render('Settings/DeliveryLocations', [
            'locations' => $locations,
            'filters' => $request->only(['search']),
            'languages' => $activeLanguages->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'direction' => $lang->direction,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $maxOrder = DeliveryLocation::max('order') ?? 0;

        DeliveryLocation::create([
            'name' => $name,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('cms.delivery-locations.index')
            ->with('success', 'Delivery location created successfully.');
    }

    public function update(Request $request, DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = $validated['name'];
        if (is_array($name)) {
            $name = array_filter($name, fn ($v) => $v !== null && $v !== '');
        }

        $deliveryLocation->update([
            'name' => $name,
            'is_active' => $validated['is_active'] ?? $deliveryLocation->is_active,
        ]);

        return redirect()->route('cms.delivery-locations.index')
            ->with('success', 'Delivery location updated successfully.');
    }

    public function destroy(DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $deliveryLocation->delete();

        return redirect()->route('cms.delivery-locations.index')
            ->with('success', 'Delivery location deleted successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:delivery_locations,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            DeliveryLocation::where('id', $item['id'])->update([
                'order' => $item['order'],
            ]);
        }

        return redirect()->back()
            ->with('success', 'Delivery locations reordered successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:delivery_locations,id'],
        ]);

        $count = DeliveryLocation::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.delivery-locations.index')
            ->with('success', "{$count} delivery locations deleted successfully.");
    }
}
