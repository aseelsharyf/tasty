<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case PaymentPending = 'payment_pending';
    case PaymentReceived = 'payment_received';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::PaymentPending => 'Payment Pending',
            self::PaymentReceived => 'Payment Received',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Accepted => 'info',
            self::PaymentPending => 'warning',
            self::PaymentReceived => 'success',
            self::Processing => 'info',
            self::Shipped => 'info',
            self::Completed => 'success',
            self::Cancelled => 'error',
        };
    }
}
