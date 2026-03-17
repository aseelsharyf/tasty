<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\DeliveryLocation;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\OrderService;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cart,
        protected OrderService $orderService
    ) {}

    public function index(): \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $deliveryLocations = DeliveryLocation::active()->ordered()->get();
        $items = $this->cart->getItemsWithProducts();

        // If any item is in-house, we collect payment now (auto-accepted order)
        // If all items are affiliate, order needs manual acceptance first — payment later
        $hasInHouseItems = collect($items)->contains(fn ($item) => $item['product']['product_type'] === 'in_house');
        $collectPaymentNow = $hasInHouseItems;

        $paymentMethods = $collectPaymentNow
            ? collect(Setting::getPaymentMethods())->where('is_active', true)->values()
            : collect();
        $bankAccounts = $collectPaymentNow ? Setting::getBankAccounts() : [];

        $subtotal = $this->cart->getTotal();
        $discount = session('discount');
        $discountAmount = $discount['amount'] ?? 0;
        $total = max(0, $subtotal - $discountAmount);

        return view('checkout.index', [
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $total,
            'itemCount' => $this->cart->getItemCount(),
            'deliveryLocations' => $deliveryLocations,
            'paymentMethods' => $paymentMethods,
            'bankAccounts' => $bankAccounts,
            'collectPaymentNow' => $collectPaymentNow,
            'discount' => $discount,
        ]);
    }

    public function store(CheckoutRequest $request): \Illuminate\Http\RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $order = $this->orderService->createFromCart($this->cart, $request->validated());

        return redirect()->route('checkout.thank-you', $order);
    }

    public function thankYou(Order $order): \Illuminate\Contracts\View\View
    {
        $order->load(['items', 'deliveryLocation']);

        return view('checkout.thank-you', [
            'order' => $order,
        ]);
    }
}
