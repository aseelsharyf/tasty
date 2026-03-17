<?php

namespace App\Services\Payment;

class PaymentVerificationResult
{
    public function __construct(
        public bool $verified,
        public ?string $status = null,
        public ?float $amount = null,
        public ?string $errorMessage = null
    ) {}
}
