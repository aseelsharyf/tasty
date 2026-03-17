<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DiscountCode::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $query->latest();

        $discountCodes = $query->paginate(20)
            ->withQueryString()
            ->through(fn (DiscountCode $code) => [
                'id' => $code->id,
                'uuid' => $code->uuid,
                'code' => $code->code,
                'description' => $code->description,
                'type' => $code->type->value,
                'type_label' => $code->type->label(),
                'value' => $code->value,
                'discount_label' => $code->discount_label,
                'min_order_amount' => $code->min_order_amount,
                'max_discount_amount' => $code->max_discount_amount,
                'max_uses' => $code->max_uses,
                'times_used' => $code->times_used,
                'starts_at' => $code->starts_at?->format('Y-m-d H:i'),
                'expires_at' => $code->expires_at?->format('Y-m-d H:i'),
                'is_active' => $code->is_active,
            ]);

        return Inertia::render('Settings/DiscountCodes', [
            'discountCodes' => $discountCodes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:discount_codes,code'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        DiscountCode::create($validated);

        return redirect()->route('cms.discount-codes.index')
            ->with('success', 'Discount code created successfully.');
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:discount_codes,code,'.$discountCode->id],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $discountCode->update($validated);

        return redirect()->route('cms.discount-codes.index')
            ->with('success', 'Discount code updated successfully.');
    }

    public function destroy(DiscountCode $discountCode): RedirectResponse
    {
        $discountCode->delete();

        return redirect()->route('cms.discount-codes.index')
            ->with('success', 'Discount code deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:discount_codes,id'],
        ]);

        $count = DiscountCode::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.discount-codes.index')
            ->with('success', "{$count} discount codes deleted successfully.");
    }
}
