<?php

namespace App\Http\Controllers\Cms;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentReceipt;
use App\Services\OrderEmailService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request): Response
    {
        $query = Order::query()
            ->with(['deliveryLocation'])
            ->withCount('items');

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_color' => $order->status->color(),
                'payment_status' => $order->payment_status->value,
                'payment_status_label' => $order->payment_status->label(),
                'payment_status_color' => $order->payment_status->color(),
                'total' => $order->total,
                'currency' => $order->currency,
                'contact_person' => $order->contact_person,
                'contact_number' => $order->contact_number,
                'items_count' => $order->items_count,
                'has_affiliate_products' => $order->has_affiliate_products,
                'created_at' => $order->created_at?->toISOString(),
            ]);

        $statusOptions = collect(OrderStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
        ]);

        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', OrderStatus::Pending)->count(),
            'total_revenue' => (float) Order::whereIn('status', [
                OrderStatus::Accepted,
                OrderStatus::Processing,
                OrderStatus::Shipped,
                OrderStatus::Completed,
            ])->sum('total'),
            'unpaid_orders' => Order::where('payment_status', PaymentStatus::Unpaid)
                ->whereNotIn('status', [OrderStatus::Cancelled])
                ->count(),
        ];

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'payment_status']),
            'statusOptions' => $statusOptions,
            'stats' => $stats,
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load([
            'items.product.featuredMedia',
            'deliveryLocation',
            'receipts.verifier',
            'statusHistory' => fn ($q) => $q->with('changedBy')->latest(),
        ]);

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'status_color' => $order->status->color(),
                'payment_status' => $order->payment_status->value,
                'payment_status_label' => $order->payment_status->label(),
                'payment_status_color' => $order->payment_status->color(),
                'payment_method' => $order->payment_method?->value,
                'payment_method_label' => $order->payment_method?->label(),
                'metadata' => $order->metadata,
                'subtotal' => $order->subtotal,
                'total' => $order->total,
                'currency' => $order->currency,
                'contact_person' => $order->contact_person,
                'contact_number' => $order->contact_number,
                'email' => $order->email,
                'delivery_location' => $order->deliveryLocation ? [
                    'id' => $order->deliveryLocation->id,
                    'name' => $order->deliveryLocation->name,
                ] : null,
                'address' => $order->address,
                'additional_info' => $order->additional_info,
                'discount_code' => $order->discount_code,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'tax_rate' => $order->tax_rate,
                'has_affiliate_products' => $order->has_affiliate_products,
                'accepted_at' => $order->accepted_at?->toISOString(),
                'paid_at' => $order->paid_at?->toISOString(),
                'shipped_at' => $order->shipped_at?->toISOString(),
                'completed_at' => $order->completed_at?->toISOString(),
                'cancelled_at' => $order->cancelled_at?->toISOString(),
                'cancellation_reason' => $order->cancellation_reason,
                'created_at' => $order->created_at?->toISOString(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'uuid' => $item->uuid,
                    'product_title' => $item->product_title,
                    'variant_name' => $item->variant_name,
                    'product_type' => $item->product_type,
                    'sku' => $item->sku,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'product_image' => $item->product?->featured_image_url,
                    'product_slug' => $item->product?->slug,
                ])->toArray(),
                'receipts' => $order->receipts->map(fn ($r) => [
                    'id' => $r->id,
                    'uuid' => $r->uuid,
                    'original_filename' => $r->original_filename,
                    'notes' => $r->notes,
                    'verified_at' => $r->verified_at?->toISOString(),
                    'verifier' => $r->verifier ? ['name' => $r->verifier->name] : null,
                    'created_at' => $r->created_at?->toISOString(),
                    'preview_url' => Storage::disk('do')->url($r->file_path),
                    'is_image' => (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', $r->file_path),
                ])->toArray(),
                'status_history' => $order->statusHistory->map(fn ($h) => [
                    'from_status' => $h->from_status,
                    'to_status' => $h->to_status,
                    'changed_by' => $h->changedBy ? ['name' => $h->changedBy->name] : null,
                    'notes' => $h->notes,
                    'created_at' => $h->created_at?->toISOString(),
                ])->toArray(),
            ],
            'isEditable' => $this->isOrderEditable($order),
            'statusOptions' => collect(OrderStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = OrderStatus::from($validated['status']);

        $this->orderService->updateStatus($order, $newStatus, $request->user(), $validated['notes'] ?? null);

        return redirect()->back()->with('success', "Order status updated to {$newStatus->label()}.");
    }

    public function acceptOrder(Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::Pending) {
            return redirect()->back()->with('error', 'Only pending orders can be accepted.');
        }

        $this->orderService->accept($order);

        return redirect()->back()->with('success', 'Order accepted.');
    }

    public function verifyPayment(Order $order): RedirectResponse
    {
        $this->orderService->verifyPayment($order, auth()->user());

        return redirect()->back()->with('success', 'Payment verified.');
    }

    public function updateItem(Request $request, Order $order, OrderItem $orderItem): RedirectResponse
    {
        if (! $this->isOrderEditable($order)) {
            return redirect()->back()->with('error', 'This order cannot be modified.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $orderItem->update([
            'quantity' => $validated['quantity'],
            'total' => $orderItem->price * $validated['quantity'],
        ]);

        $this->recalculateOrderTotals($order);

        return redirect()->back()->with('success', 'Item quantity updated.');
    }

    public function removeItem(Order $order, OrderItem $orderItem): RedirectResponse
    {
        if (! $this->isOrderEditable($order)) {
            return redirect()->back()->with('error', 'This order cannot be modified.');
        }

        $title = $orderItem->product_title;
        $orderItem->delete();

        // If no items left, cancel the order
        if ($order->items()->count() === 0) {
            $this->orderService->updateStatus($order, OrderStatus::Cancelled, auth()->user(), 'All items removed.');

            return redirect()->back()->with('success', "'{$title}' removed. Order cancelled as no items remain.");
        }

        $this->recalculateOrderTotals($order);

        return redirect()->back()->with('success', "'{$title}' removed from order.");
    }

    private function isOrderEditable(Order $order): bool
    {
        return in_array($order->payment_status, [PaymentStatus::Unpaid, PaymentStatus::Failed]);
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $order->load('items');

        $subtotal = $order->items->sum('total');
        $discountAmount = (float) $order->discount_amount;
        $afterDiscount = max(0, $subtotal - $discountAmount);

        $taxRate = (float) $order->tax_rate;
        $taxAmount = 0;

        if ($taxRate > 0) {
            $taxAmount = round($afterDiscount * $taxRate / 100, 2);
        }

        $total = $afterDiscount + $taxAmount;

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    public function rejectReceipt(Request $request, Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::PaymentPendingApproval) {
            return redirect()->back()->with('error', 'This order does not have a pending receipt to reject.');
        }

        $fromStatus = $order->status->value;

        $order->update([
            'payment_status' => PaymentStatus::Failed,
            'status' => OrderStatus::PaymentPending,
        ]);

        $order->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => OrderStatus::PaymentPending->value,
            'changed_by' => $request->user()?->id,
            'notes' => $request->input('notes', 'Bank transfer receipt rejected.'),
        ]);

        app(OrderEmailService::class)->sendBankTransferRejectedEmail($order);

        return redirect()->back()->with('success', 'Receipt rejected and customer notified.');
    }

    public function viewReceipt(PaymentReceipt $receipt): StreamedResponse
    {
        return Storage::disk('do')->download($receipt->file_path, $receipt->original_filename);
    }
}
