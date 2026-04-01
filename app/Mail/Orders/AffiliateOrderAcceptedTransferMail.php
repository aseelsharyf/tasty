<?php

namespace App\Mail\Orders;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateOrderAcceptedTransferMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your order {$this->order->order_number} is confirmed — Please complete your bank transfer",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.affiliate-order-accepted-transfer',
            with: [
                'order' => $this->order,
                'bankAccounts' => Setting::getBankAccounts(),
                'paymentUrl' => route('payment.index', $this->order),
                'trackingUrl' => route('order.track'),
            ],
        );
    }
}
