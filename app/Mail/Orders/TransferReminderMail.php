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

class TransferReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder — Complete your bank transfer for order {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.transfer-reminder',
            with: [
                'order' => $this->order,
                'bankAccounts' => Setting::getBankAccounts(),
                'paymentUrl' => route('payment.index', $this->order),
                'trackingUrl' => route('order.track'),
            ],
        );
    }
}
