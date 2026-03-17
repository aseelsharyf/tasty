<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

class BmlGatewayService implements PaymentGatewayInterface
{
    public function initiate(Order $order): PaymentInitiationResult
    {
        // Stub: BML Gateway integration
        return new PaymentInitiationResult(
            success: false,
            errorMessage: 'BML Gateway integration not yet configured.'
        );
    }

    public function handleCallback(Request $request): PaymentCallbackResult
    {
        return new PaymentCallbackResult(
            success: false,
            errorMessage: 'BML Gateway callback not yet implemented.'
        );
    }

    public function verify(string $transactionId): PaymentVerificationResult
    {
        return new PaymentVerificationResult(
            verified: false,
            errorMessage: 'BML Gateway verification not yet implemented.'
        );
    }
}
