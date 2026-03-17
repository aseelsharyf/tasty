<?php

namespace App\Services\Payment;

class PaymentCallbackResult
{
    public function __construct(
        public bool $success,
        public ?string $transactionId = null,
        public ?string $orderNumber = null,
        public ?string $errorMessage = null
    ) {}
}
