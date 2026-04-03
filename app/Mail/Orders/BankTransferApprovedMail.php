<?php

namespace App\Mail\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BankTransferApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment approved — Your order {$this->order->order_number} is confirmed",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.bank-transfer-approved',
            with: [
                'order' => $this->order,
                'trackingUrl' => config('cms.website_url').'/order/track',
            ],
        );
    }
}
