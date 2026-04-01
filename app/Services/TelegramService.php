<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function __construct(
        protected string $botToken,
        protected string $chatId,
    ) {}

    /**
     * Send a message to the configured Telegram chat.
     */
    public function sendMessage(string $text): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram credentials are not configured.');

            return false;
        }

        $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'MarkdownV2',
        ]);

        if ($response->failed()) {
            Log::error('Telegram message failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Escape special characters for MarkdownV2.
     */
    protected function escape(string $text): string
    {
        return preg_replace('/([_\*\[\]\(\)~`>#+\-=|{}.!\\\\])/', '\\\\$1', $text);
    }

    /**
     * Send a notification for a newly created order.
     */
    public function notifyNewOrder(Order $order): bool
    {
        $currency = $order->currency ?? 'MVR';
        $e = fn (string $text): string => $this->escape($text);

        $items = $order->items->map(function ($item) use ($currency, $e) {
            $line = "  • {$e($item->product_title)}";
            if ($item->variant_name) {
                $line .= " \\({$e($item->variant_name)}\\)";
            }
            $line .= " x{$item->quantity} — {$e($currency)} {$e(number_format($item->total, 2))}";

            return $line;
        })->implode("\n");

        $text = "🛒 *New Order Received\\!*\n\n"
            ."*Order:* \\#{$e($order->order_number)}\n"
            ."*Customer:* {$e($order->contact_person)}\n"
            ."*Phone:* {$e($order->contact_number)}\n"
            .($order->email ? "*Email:* {$e($order->email)}\n" : '')
            ."*Address:* {$e($order->address)}\n"
            .($order->additional_info ? "*Notes:* {$e($order->additional_info)}\n" : '')
            ."\n*Items:*\n{$items}\n"
            .($order->discount_amount > 0 ? "\n*Discount:* \\-{$e($currency)} {$e(number_format($order->discount_amount, 2))}\n" : '')
            .($order->tax_amount > 0 ? "*Tax:* {$e($currency)} {$e(number_format($order->tax_amount, 2))}\n" : '')
            ."\n*Total: {$e($currency)} {$e(number_format($order->total, 2))}*\n"
            ."*Status:* {$e($order->status->label())}";

        return $this->sendMessage($text);
    }
}
