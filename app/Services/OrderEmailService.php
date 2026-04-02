<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Mail\Orders\AffiliateOrderAcceptedGatewayMail;
use App\Mail\Orders\AffiliateOrderAcceptedTransferMail;
use App\Mail\Orders\AffiliateOrderReceivedGatewayMail;
use App\Mail\Orders\AffiliateOrderReceivedTransferMail;
use App\Mail\Orders\AffiliateOrderRejectedMail;
use App\Mail\Orders\BankTransferApprovedMail;
use App\Mail\Orders\BankTransferReceiptSubmittedMail;
use App\Mail\Orders\BankTransferRejectedMail;
use App\Mail\Orders\OrderCancelledMail;
use App\Mail\Orders\OrderReceivedMail;
use App\Mail\Orders\PaymentCompleteMail;
use App\Mail\Orders\PaymentFailedMail;
use App\Mail\Orders\PaymentReminderMail;
use App\Mail\Orders\TransferReminderMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderEmailService
{
    /**
     * Send email when an order is first placed.
     *
     * Non-affiliate orders don't get an "order placed" email here because
     * they go straight to payment. Affiliate orders get Template 8 or 9.
     */
    public function sendOrderPlacedEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        if ($order->has_affiliate_products) {
            $mailable = $this->isGatewayPayment($order)
                ? new AffiliateOrderReceivedGatewayMail($order)
                : new AffiliateOrderReceivedTransferMail($order);
        } else {
            $mailable = new OrderReceivedMail($order);
        }

        $this->send($order, $mailable);
    }

    /**
     * Send email when payment is completed via gateway (Template 1).
     */
    public function sendPaymentCompletedEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $this->send($order, new PaymentCompleteMail($order));
    }

    /**
     * Send email when bank transfer receipt is submitted (Template 2).
     */
    public function sendBankTransferReceiptSubmittedEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $this->send($order, new BankTransferReceiptSubmittedMail($order));
    }

    /**
     * Send payment reminder email (Template 3 for GW, Template 4 for TF).
     */
    public function sendPaymentReminderEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $mailable = $this->isGatewayPayment($order)
            ? new PaymentReminderMail($order)
            : new TransferReminderMail($order);

        $this->send($order, $mailable);
    }

    /**
     * Send email when gateway payment fails (Template 5).
     */
    public function sendPaymentFailedEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $this->send($order, new PaymentFailedMail($order));
    }

    /**
     * Send email when admin rejects bank transfer receipt (Template 6).
     */
    public function sendBankTransferRejectedEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $this->send($order, new BankTransferRejectedMail($order));
    }

    /**
     * Send email when admin approves bank transfer (Template 7).
     */
    public function sendBankTransferApprovedEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $this->send($order, new BankTransferApprovedMail($order));
    }

    /**
     * Send email when affiliate order is accepted (Template 10 or 11).
     */
    public function sendOrderAcceptedEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $mailable = $this->isGatewayPayment($order)
            ? new AffiliateOrderAcceptedGatewayMail($order)
            : new AffiliateOrderAcceptedTransferMail($order);

        $this->send($order, $mailable);
    }

    /**
     * Send email when affiliate order is rejected (Template 12).
     *
     * @param  'full'|'partial'  $rejectionType
     */
    public function sendOrderRejectedEmail(Order $order, string $rejectionType = 'full'): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $this->send($order, new AffiliateOrderRejectedMail($order, $rejectionType));
    }

    /**
     * Send email when admin cancels an order (Template 13).
     */
    public function sendOrderCancelledEmail(Order $order): void
    {
        if (! $this->canSendEmail($order)) {
            return;
        }

        $this->send($order, new OrderCancelledMail($order));
    }

    private function canSendEmail(Order $order): bool
    {
        return ! empty($order->email);
    }

    private function isGatewayPayment(Order $order): bool
    {
        return $order->payment_method === PaymentMethod::BmlGateway;
    }

    private function send(Order $order, object $mailable): void
    {
        try {
            Mail::to($order->email)->send($mailable);
        } catch (\Throwable $e) {
            Log::error('Failed to send order email', [
                'order' => $order->order_number,
                'mailable' => get_class($mailable),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
