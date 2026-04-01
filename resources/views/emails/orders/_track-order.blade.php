<div style="background-color: #f9fafb; border-radius: 6px; padding: 16px; margin: 20px 0;">
    <div style="font-size: 13px; color: #888888; margin-bottom: 8px;">Track your order</div>
    <div style="margin-bottom: 8px;">
        <a href="{{ $trackingUrl }}" style="color: #0a0924; text-decoration: none; font-size: 14px;">{{ $trackingUrl }}</a>
    </div>
    <div style="font-size: 13px; color: #888888;">
        Enter your order number <strong style="color: #0a0924;">{{ $order->order_number }}</strong> along with the contact number or email address you used when placing the order.
    </div>
</div>
