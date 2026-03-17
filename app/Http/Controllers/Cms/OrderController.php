<?php

namespace App\Http\Controllers\Cms;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentReceipt;
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
            'items',
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
                    'product_title' => $item->product_title,
                    'variant_name' => $item->variant_name,
                    'product_type' => $item->product_type,
                    'sku' => $item->sku,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                ])->toArray(),
                'receipts' => $order->receipts->map(fn ($r) => [
                    'id' => $r->id,
                    'uuid' => $r->uuid,
                    'original_filename' => $r->original_filename,
                    'notes' => $r->notes,
                    'verified_at' => $r->verified_at?->toISOString(),
                    'verifier' => $r->verifier ? ['name' => $r->verifier->name] : null,
                    'created_at' => $r->created_at?->toISOString(),
                ])->toArray(),
                'status_history' => $order->statusHistory->map(fn ($h) => [
                    'from_status' => $h->from_status,
                    'to_status' => $h->to_status,
                    'changed_by' => $h->changedBy ? ['name' => $h->changedBy->name] : null,
                    'notes' => $h->notes,
                    'created_at' => $h->created_at?->toISOString(),
                ])->toArray(),
            ],
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

    public function viewReceipt(PaymentReceipt $receipt): StreamedResponse
    {
        return Storage::disk('public')->download($receipt->file_path, $receipt->original_filename);
    }
}
