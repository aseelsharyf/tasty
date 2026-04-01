<?php

namespace App\Mail\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BankTransferReceiptSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Transfer receipt received — Order {$this->order->order_number} pending approval",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.bank-transfer-receipt-submitted',
            with: [
                'order' => $this->order,
                'trackingUrl' => route('order.track'),
            ],
        );
    }
}
