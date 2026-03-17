<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Order $order): \Illuminate\Contracts\View\View|RedirectResponse
    {
        if (! in_array($order->status, [OrderStatus::Accepted, OrderStatus::PaymentPending])) {
            return redirect()->route('order.track')
                ->with('error', 'This order is not ready for payment.');
        }

        $paymentMethods = collect(Setting::getPaymentMethods())
            ->where('is_active', true)
            ->values();

        return view('payment.index', [
            'order' => $order,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function processBankTransfer(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $request->file('receipt')->store('receipts', 'public');

        $order->receipts()->create([
            'file_path' => $path,
            'original_filename' => $request->file('receipt')->getClientOriginalName(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $order->update([
            'payment_method' => 'bank_transfer',
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::PaymentPending,
        ]);

        return redirect()->route('payment.confirmation', $order);
    }

    public function processGateway(Request $request, Order $order): RedirectResponse
    {
        // Stub: gateway integration would go here
        $order->update([
            'payment_method' => $request->input('method', 'bml_gateway'),
            'status' => OrderStatus::PaymentPending,
        ]);

        return redirect()->route('payment.confirmation', $order);
    }

    public function gatewayCallback(Request $request): RedirectResponse
    {
        // Stub: handle gateway callback
        return redirect()->route('home');
    }

    public function confirmation(Order $order): \Illuminate\Contracts\View\View
    {
        $order->load(['items', 'receipts']);

        $bankAccounts = Setting::getBankAccounts();

        return view('payment.confirmation', [
            'order' => $order,
            'bankAccounts' => $bankAccounts,
        ]);
    }
}
