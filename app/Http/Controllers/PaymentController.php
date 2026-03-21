<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Payment\BmlGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        $bankAccounts = Setting::getBankAccounts();

        return view('payment.index', [
            'order' => $order,
            'paymentMethods' => $paymentMethods,
            'bankAccounts' => $bankAccounts,
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

        $fromStatus = $order->status->value;

        $order->update([
            'payment_method' => 'bank_transfer',
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::PaymentPendingApproval,
        ]);

        $order->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => OrderStatus::PaymentPendingApproval->value,
            'notes' => 'Payment receipt uploaded.',
        ]);

        return redirect()->route('payment.confirmation', $order);
    }

    public function processGateway(Request $request, Order $order): RedirectResponse
    {
        $method = $request->input('method', 'bml_gateway');

        if ($method === 'bml_gateway') {
            $service = new BmlGatewayService;
            $result = $service->initiate($order);

            if ($result->success && $result->redirectUrl) {
                return redirect()->away($result->redirectUrl);
            }

            return redirect()->back()->with('error', $result->errorMessage ?? 'Payment initiation failed.');
        }

        $order->update([
            'payment_method' => $method,
            'status' => OrderStatus::PaymentPending,
        ]);

        return redirect()->route('payment.confirmation', $order);
    }

    public function bmlRedirect(Order $order): \Illuminate\Contracts\View\View|RedirectResponse
    {
        $order->load('items');

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('payment.confirmation', $order);
        }

        $transactionId = $order->metadata['bml_transaction_id'] ?? null;

        if ($transactionId) {
            $service = new BmlGatewayService;
            $verification = $service->verify($transactionId);

            if ($verification->verified) {
                $fromStatus = $order->status->value;

                $order->update([
                    'payment_status' => PaymentStatus::Paid,
                    'status' => OrderStatus::PaymentReceived,
                    'paid_at' => now(),
                ]);

                $order->statusHistory()->create([
                    'from_status' => $fromStatus,
                    'to_status' => OrderStatus::PaymentReceived->value,
                    'notes' => 'Payment confirmed via BML Connect.',
                ]);

                return redirect()->route('payment.confirmation', $order);
            }
        }

        return view('payment.bml-redirect', [
            'order' => $order,
            'status' => $order->payment_status,
        ]);
    }

    public function bmlWebhook(Request $request): Response
    {
        $service = new BmlGatewayService;
        $service->handleCallback($request);

        return response('OK', 200);
    }

    public function gatewayCallback(Request $request): RedirectResponse
    {
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
