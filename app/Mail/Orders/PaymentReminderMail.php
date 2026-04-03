<?php

namespace App\Mail\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Action needed — Complete your payment for order {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.payment-reminder',
            with: [
                'order' => $this->order,
                'paymentUrl' => config('cms.website_url').'/payment/'.$this->order->uuid,
                'trackingUrl' => config('cms.website_url').'/order/track',
            ],
        );
    }
}
