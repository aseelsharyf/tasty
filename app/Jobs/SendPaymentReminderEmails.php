<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPaymentReminderEmails implements ShouldQueue
{
    use Queueable;

    public function handle(OrderEmailService $emailService): void
    {
        // Non-affiliate orders: payment not completed within 10 minutes
        $this->sendRemindersForNonAffiliateOrders($emailService);

        // Affiliate orders: accepted but payment not attempted within 24 hours
        $this->sendRemindersForAcceptedAffiliateOrders($emailService);
    }

    private function sendRemindersForNonAffiliateOrders(OrderEmailService $emailService): void
    {
        $orders = Order::query()
            ->where('has_affiliate_products', false)
            ->whereIn('status', [OrderStatus::Accepted, OrderStatus::PaymentPending])
            ->where('payment_status', PaymentStatus::Unpaid)
            ->where('created_at', '<=', now()->subMinutes(10))
            ->whereNull('cancelled_at')
            ->get();

        foreach ($orders as $order) {
            if ($this->hasAlreadySentReminder($order)) {
                continue;
            }

            $emailService->sendPaymentReminderEmail($order);
            $this->markReminderSent($order);

            Log::info('Payment reminder sent', ['order' => $order->order_number]);
        }
    }

    private function sendRemindersForAcceptedAffiliateOrders(OrderEmailService $emailService): void
    {
        $orders = Order::query()
            ->where('has_affiliate_products', true)
            ->where('status', OrderStatus::Accepted)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->where('accepted_at', '<=', now()->subHours(24))
            ->whereNull('cancelled_at')
            ->get();

        foreach ($orders as $order) {
            if ($this->hasAlreadySentReminder($order)) {
                continue;
            }

            $emailService->sendPaymentReminderEmail($order);
            $this->markReminderSent($order);

            Log::info('Payment reminder sent (affiliate)', ['order' => $order->order_number]);
        }
    }

    private function hasAlreadySentReminder(Order $order): bool
    {
        return ! empty($order->metadata['payment_reminder_sent_at']);
    }

    private function markReminderSent(Order $order): void
    {
        $order->update([
            'metadata' => array_merge($order->metadata ?? [], [
                'payment_reminder_sent_at' => now()->toISOString(),
            ]),
        ]);
    }
}
