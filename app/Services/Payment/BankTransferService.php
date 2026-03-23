<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

class BankTransferService implements PaymentGatewayInterface
{
    public function initiate(Order $order): PaymentInitiationResult
    {
        return new PaymentInitiationResult(
            success: true,
            redirectUrl: route('payment.index', $order)
        );
    }

    public function handleCallback(Request $request): PaymentCallbackResult
    {
        return new PaymentCallbackResult(success: true);
    }

    public function verify(string $transactionId): PaymentVerificationResult
    {
        return new PaymentVerificationResult(verified: true, status: 'manual_verification');
    }
}
