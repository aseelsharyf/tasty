<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function initiate(Order $order): PaymentInitiationResult;

    public function handleCallback(Request $request): PaymentCallbackResult;

    public function verify(string $transactionId): PaymentVerificationResult;
}
