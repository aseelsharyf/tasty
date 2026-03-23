<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cart
    ) {}

    public function index(): \Illuminate\Contracts\View\View
    {
        return view('cart.index', [
            'items' => $this->cart->getItemsWithProducts(),
            'total' => $this->cart->getTotal(),
            'itemCount' => $this->cart->getItemCount(),
            'discount' => session('discount'),
        ]);
    }

    public function add(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $result = $this->cart->add(
            $validated['product_id'],
            $validated['quantity'] ?? 1,
            $validated['variant_id'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'cartCount' => $this->cart->getItemCount(),
            ]);
        }

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'cart_item_id' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $result = $this->cart->update($validated['cart_item_id'], $validated['quantity']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'cartCount' => $this->cart->getItemCount(),
                'total' => $this->cart->getTotal(),
            ]);
        }

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function remove(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'cart_item_id' => ['required', 'string'],
        ]);

        $result = $this->cart->remove($validated['cart_item_id']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'cartCount' => $this->cart->getItemCount(),
                'total' => $this->cart->getTotal(),
            ]);
        }

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function clear(Request $request): RedirectResponse|JsonResponse
    {
        $this->cart->clear();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Cart cleared.']);
        }

        return redirect()->back()->with('success', 'Cart cleared.');
    }

    public function count(): JsonResponse
    {
        $items = $this->cart->getItems();
        $cartMap = [];
        foreach ($items as $item) {
            $pid = $item['product_id'];
            if (! isset($cartMap[$pid])) {
                $cartMap[$pid] = ['qty' => 0, 'ids' => []];
            }
            $cartMap[$pid]['qty'] += $item['quantity'];
            $cartMap[$pid]['ids'][] = $item['id'];
        }

        return response()->json([
            'count' => $this->cart->getItemCount(),
            'cartMap' => $cartMap,
        ]);
    }

    public function preview(): JsonResponse
    {
        return response()->json([
            'items' => $this->cart->getItemsWithProducts(),
            'total' => $this->cart->getTotal(),
            'itemCount' => $this->cart->getItemCount(),
        ]);
    }
}
