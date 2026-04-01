<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected OrderEmailService $orderEmailService,
        protected TelegramService $telegramService,
    ) {}

    /**
     * Create an order from the cart.
     *
     * @param  array{contact_person: string, contact_number: string, email?: string, delivery_location_id?: int, address: string, additional_info?: string}  $deliveryInfo
     */
    public function createFromCart(CartService $cart, array $deliveryInfo): Order
    {
        $cartItems = $cart->getItemsWithProducts();

        if (empty($cartItems)) {
            throw new \RuntimeException('Cart is empty.');
        }

        $subtotal = array_sum(array_column($cartItems, 'total'));
        $hasAffiliate = collect($cartItems)->contains(fn ($item) => $item['product']['product_type'] === 'affiliate');

        // Apply discount from session
        $discount = session('discount');
        $discountCodeId = null;
        $discountCode = null;
        $discountAmount = 0;

        if ($discount) {
            $discountModel = \App\Models\DiscountCode::find($discount['code_id']);
            if ($discountModel) {
                $validation = $discountModel->validate($subtotal);
                if ($validation['valid']) {
                    $discountCodeId = $discountModel->id;
                    $discountCode = $discountModel->code;
                    $discountAmount = $discountModel->calculateDiscount($subtotal);
                    $discountModel->incrementUsage();
                }
            }
        }

        $afterDiscount = max(0, $subtotal - $discountAmount);

        // Calculate tax
        $taxConfig = Setting::getTaxConfig();
        $taxRate = 0;
        $taxAmount = 0;

        if ($taxConfig['enabled'] && $taxConfig['rate'] > 0) {
            $taxRate = (float) $taxConfig['rate'];

            if ($taxConfig['inclusive'] ?? false) {
                // Tax is already included in the price
                $taxAmount = round($afterDiscount - ($afterDiscount / (1 + $taxRate / 100)), 2);
            } else {
                // Tax is added on top
                $taxAmount = round($afterDiscount * $taxRate / 100, 2);
            }
        }

        $total = $taxConfig['inclusive'] ?? false
            ? $afterDiscount
            : $afterDiscount + $taxAmount;

        $order = Order::create([
            'status' => $hasAffiliate ? OrderStatus::Pending : OrderStatus::Accepted,
            'payment_status' => PaymentStatus::Unpaid,
            'subtotal' => $subtotal,
            'discount_code_id' => $discountCodeId,
            'discount_code' => $discountCode,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'total' => $total,
            'currency' => $cartItems[0]['product']['currency'] ?? 'MVR',
            'contact_person' => $deliveryInfo['contact_person'],
            'contact_number' => $deliveryInfo['contact_number'],
            'email' => $deliveryInfo['email'] ?? null,
            'delivery_location_id' => $deliveryInfo['delivery_location_id'] ?? null,
            'address' => $deliveryInfo['address'],
            'additional_info' => $deliveryInfo['additional_info'] ?? null,
            'payment_method' => $deliveryInfo['payment_method'] ?? null,
            'has_affiliate_products' => $hasAffiliate,
            'accepted_at' => $hasAffiliate ? null : now(),
        ]);

        // Create order items and decrement stock
        foreach ($cartItems as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem['product_id'],
                'product_variant_id' => $cartItem['variant_id'],
                'product_type' => $cartItem['product']['product_type'],
                'product_title' => $cartItem['product']['title'],
                'variant_name' => $cartItem['variant']['name'] ?? null,
                'sku' => $cartItem['variant']['sku'] ?? null,
                'price' => $cartItem['price'],
                'quantity' => $cartItem['quantity'],
                'total' => $cartItem['total'],
            ]);

            // Decrement stock for in-house products
            if ($cartItem['product']['product_type'] === ProductType::InHouse->value) {
                if ($cartItem['variant_id']) {
                    ProductVariant::where('id', $cartItem['variant_id'])
                        ->decrement('stock_quantity', $cartItem['quantity']);
                } else {
                    Product::where('id', $cartItem['product_id'])
                        ->where('track_inventory', true)
                        ->decrement('stock_quantity', $cartItem['quantity']);
                }
            }
        }

        // Record initial status
        $order->statusHistory()->create([
            'from_status' => '',
            'to_status' => $order->status->value,
            'notes' => 'Order created.',
        ]);

        $cart->clear();
        session()->forget('discount');

        $this->notificationService->orderCreated($order);
        $this->orderEmailService->sendOrderPlacedEmail($order);
        $this->telegramService->notifyNewOrder($order);

        return $order;
    }

    /**
     * Accept a pending order (for affiliate orders).
     */
    public function accept(Order $order): void
    {
        $fromStatus = $order->status->value;
        $order->markAsAccepted();

        $order->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => OrderStatus::Accepted->value,
            'notes' => 'Order accepted.',
        ]);

        $this->notificationService->orderStatusChanged($order, $fromStatus, OrderStatus::Accepted);
        $this->orderEmailService->sendOrderAcceptedEmail($order);
    }

    /**
     * Update order status with history tracking.
     */
    public function updateStatus(Order $order, OrderStatus $newStatus, ?User $changedBy = null, ?string $notes = null): void
    {
        $fromStatus = $order->status->value;

        $updateData = ['status' => $newStatus];

        // Set timestamp fields based on status
        match ($newStatus) {
            OrderStatus::Accepted => $updateData['accepted_at'] = now(),
            OrderStatus::Shipped => $updateData['shipped_at'] = now(),
            OrderStatus::Completed => $updateData['completed_at'] = now(),
            OrderStatus::Cancelled => $updateData = array_merge($updateData, [
                'cancelled_at' => now(),
                'cancellation_reason' => $notes,
            ]),
            default => null,
        };

        $order->update($updateData);

        $order->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => $newStatus->value,
            'changed_by' => $changedBy?->id,
            'notes' => $notes,
        ]);

        // Restore stock on cancellation
        if ($newStatus === OrderStatus::Cancelled) {
            $this->restoreStock($order);
        }

        $this->notificationService->orderStatusChanged($order, $fromStatus, $newStatus, $changedBy);

        if ($newStatus === OrderStatus::Cancelled) {
            $this->orderEmailService->sendOrderCancelledEmail($order);
        }
    }

    /**
     * Verify payment for an order.
     */
    public function verifyPayment(Order $order, User $verifiedBy): void
    {
        $order->update([
            'payment_status' => PaymentStatus::Verified,
            'paid_at' => $order->paid_at ?? now(),
        ]);

        // Mark all unverified receipts as verified
        $order->receipts()
            ->whereNull('verified_at')
            ->update([
                'verified_at' => now(),
                'verified_by' => $verifiedBy->id,
            ]);

        $this->notificationService->orderPaymentVerified($order, $verifiedBy);
        $this->orderEmailService->sendBankTransferApprovedEmail($order);
    }

    /**
     * Restore stock for cancelled orders.
     */
    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_type === ProductType::InHouse->value) {
                if ($item->product_variant_id) {
                    ProductVariant::where('id', $item->product_variant_id)
                        ->increment('stock_quantity', $item->quantity);
                } else {
                    Product::where('id', $item->product_id)
                        ->where('track_inventory', true)
                        ->increment('stock_quantity', $item->quantity);
                }
            }
        }
    }
}
