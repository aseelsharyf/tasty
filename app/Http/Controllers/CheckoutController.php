<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\DeliveryLocation;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payment\BmlGatewayService;

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
        $afterDiscount = max(0, $subtotal - $discountAmount);

        $taxConfig = Setting::getTaxConfig();
        $taxAmount = 0;
        $taxLabel = $taxConfig['label'] ?? 'Tax';

        if ($taxConfig['enabled'] && $taxConfig['rate'] > 0) {
            $rate = (float) $taxConfig['rate'];

            if ($taxConfig['inclusive'] ?? false) {
                $taxAmount = round($afterDiscount - ($afterDiscount / (1 + $rate / 100)), 2);
            } else {
                $taxAmount = round($afterDiscount * $rate / 100, 2);
            }
        }

        $total = ($taxConfig['inclusive'] ?? false) ? $afterDiscount : $afterDiscount + $taxAmount;

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
            'taxAmount' => $taxAmount,
            'taxLabel' => $taxLabel,
            'taxInclusive' => $taxConfig['inclusive'] ?? false,
        ]);
    }

    public function store(CheckoutRequest $request): \Illuminate\Http\RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $order = $this->orderService->createFromCart($this->cart, $request->validated());

        // Auto-accepted orders: skip thank-you page and go directly to payment
        if (! $order->has_affiliate_products && $order->payment_method) {
            if ($order->payment_method === 'bml_gateway') {
                $service = new BmlGatewayService;
                $result = $service->initiate($order);

                if ($result->success && $result->redirectUrl) {
                    return redirect()->away($result->redirectUrl);
                }

                return redirect()->route('payment.index', $order)
                    ->with('error', $result->errorMessage ?? 'Payment initiation failed.');
            }

            // Bank transfer or other methods → payment page directly
            return redirect()->route('payment.index', $order);
        }

        // Affiliate orders → thank-you page (waiting for seller confirmation)
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
