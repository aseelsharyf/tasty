<?php

namespace App\Services\Payment;

class PaymentInitiationResult
{
    public function __construct(
        public bool $success,
        public ?string $redirectUrl = null,
        public ?string $transactionId = null,
        public ?string $errorMessage = null
    ) {}
}
