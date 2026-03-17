<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index(): \Illuminate\Contracts\View\View
    {
        return view('order.track');
    }

    public function track(Request $request): \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where(function ($query) use ($validated) {
                $query->where('contact_number', $validated['contact'])
                    ->orWhere('email', $validated['contact']);
            })
            ->first();

        if (! $order) {
            return redirect()->route('order.track')
                ->with('error', 'Order not found. Please check your order number and contact information.');
        }

        $order->load(['items', 'deliveryLocation', 'statusHistory' => fn ($q) => $q->latest()]);

        return view('order.status', [
            'order' => $order,
        ]);
    }
}
