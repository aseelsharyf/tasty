<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountCodeController extends Controller
{
    public function __construct(
        protected CartService $cart
    ) {}

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $code = DiscountCode::where('code', strtoupper(trim($request->code)))->first();

        if (! $code) {
            return response()->json(['success' => false, 'message' => 'Invalid discount code.'], 422);
        }

        $subtotal = $this->cart->getTotal();
        $validation = $code->validate($subtotal);

        if (! $validation['valid']) {
            return response()->json(['success' => false, 'message' => $validation['message']], 422);
        }

        $discountAmount = $code->calculateDiscount($subtotal);

        // Store in session
        session()->put('discount', [
            'code_id' => $code->id,
            'code' => $code->code,
            'label' => $code->discount_label,
            'amount' => $discountAmount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Discount code applied.',
            'discount' => [
                'code' => $code->code,
                'label' => $code->discount_label,
                'amount' => $discountAmount,
                'total' => max(0, $subtotal - $discountAmount),
            ],
        ]);
    }

    public function remove(): JsonResponse
    {
        session()->forget('discount');

        return response()->json([
            'success' => true,
            'message' => 'Discount code removed.',
            'total' => $this->cart->getTotal(),
        ]);
    }
}
