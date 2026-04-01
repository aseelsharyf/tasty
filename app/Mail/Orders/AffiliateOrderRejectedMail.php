<?php

namespace App\Mail\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateOrderRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  'full'|'partial'  $rejectionType
     */
    public function __construct(
        public Order $order,
        public string $rejectionType = 'full',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Update on your order {$this->order->order_number} — Some items could not be fulfilled",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.affiliate-order-rejected',
            with: [
                'order' => $this->order,
                'rejectionType' => $this->rejectionType,
                'trackingUrl' => route('order.track'),
            ],
        );
    }
}
