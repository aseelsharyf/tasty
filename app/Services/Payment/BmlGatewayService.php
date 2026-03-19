<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use BmlConnect\BmlConnect;
use BmlConnect\Enums\TransactionState;
use BmlConnect\Exceptions\BmlConnectException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BmlGatewayService implements PaymentGatewayInterface
{
    public function initiate(Order $order): PaymentInitiationResult
    {
        try {
            $bml = app(BmlConnect::class);
            $transaction = $bml->transactions->create([
                'amount' => (int) round($order->total * 100),
                'currency' => $order->currency ?? 'MVR',
                'redirectUrl' => route('payment.bml.redirect', $order),
                'localId' => $order->order_number,
            ]);

            $fromStatus = $order->status->value;

            $order->update([
                'payment_method' => PaymentMethod::BmlGateway,
                'payment_status' => PaymentStatus::Unpaid,
                'status' => OrderStatus::PaymentPending,
                'metadata' => array_merge($order->metadata ?? [], [
                    'bml_transaction_id' => $transaction->id,
                    'bml_transaction_url' => $transaction->url,
                ]),
            ]);

            $order->statusHistory()->create([
                'from_status' => $fromStatus,
                'to_status' => OrderStatus::PaymentPending->value,
                'notes' => 'Payment initiated via BML Connect.',
            ]);

            return new PaymentInitiationResult(
                success: true,
                redirectUrl: $transaction->url,
                transactionId: $transaction->id,
            );
        } catch (BmlConnectException $e) {
            Log::error('BML Gateway initiation failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return new PaymentInitiationResult(
                success: false,
                errorMessage: 'Payment gateway is currently unavailable. Please try again later.',
            );
        }
    }

    public function handleCallback(Request $request): PaymentCallbackResult
    {
        try {
            $bml = app(BmlConnect::class);
            $event = $bml->webhooks->parsePayload(
                jsonBody: $request->getContent(),
                headers: $request->headers->all(),
                apiKey: config('bml-connect.api_key'),
            );

            $order = Order::where('order_number', $event->localId)->first();

            if (! $order) {
                Log::warning('BML webhook: order not found', ['localId' => $event->localId]);

                return new PaymentCallbackResult(
                    success: false,
                    errorMessage: 'Order not found.',
                );
            }

            $state = $event->state;

            if ($state === 'CONFIRMED') {
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

                Log::info('BML payment confirmed', [
                    'order' => $order->order_number,
                    'transaction' => $event->transactionId,
                ]);

                return new PaymentCallbackResult(
                    success: true,
                    transactionId: $event->transactionId,
                    orderNumber: $order->order_number,
                );
            }

            if (in_array($state, ['CANCELLED', 'FAILED'])) {
                $order->update([
                    'payment_status' => PaymentStatus::Failed,
                ]);

                Log::info('BML payment failed/cancelled', [
                    'order' => $order->order_number,
                    'state' => $state,
                ]);

                return new PaymentCallbackResult(
                    success: false,
                    transactionId: $event->transactionId,
                    orderNumber: $order->order_number,
                    errorMessage: "Payment {$state}.",
                );
            }

            return new PaymentCallbackResult(
                success: false,
                transactionId: $event->transactionId,
                orderNumber: $order->order_number,
                errorMessage: "Unhandled transaction state: {$state}.",
            );
        } catch (BmlConnectException $e) {
            Log::error('BML webhook verification failed', ['error' => $e->getMessage()]);

            return new PaymentCallbackResult(
                success: false,
                errorMessage: 'Webhook verification failed.',
            );
        }
    }

    public function verify(string $transactionId): PaymentVerificationResult
    {
        try {
            $bml = app(BmlConnect::class);
            $transaction = $bml->transactions->get($transactionId);

            $isConfirmed = $transaction->state === TransactionState::CONFIRMED;

            return new PaymentVerificationResult(
                verified: $isConfirmed,
                status: $transaction->state?->value,
                amount: $transaction->amount ? $transaction->amount / 100 : null,
            );
        } catch (BmlConnectException $e) {
            Log::error('BML transaction verification failed', [
                'transaction' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return new PaymentVerificationResult(
                verified: false,
                errorMessage: 'Could not verify transaction.',
            );
        }
    }
}
