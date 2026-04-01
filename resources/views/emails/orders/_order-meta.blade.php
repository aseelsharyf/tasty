<div class="order-meta">
    Order Date: <span style="color: #0a0924;">{{ $order->created_at->format('d M Y') }}</span>
    @if ($order->payment_method)
        | Payment Method: {{ $order->payment_method->label() }}
    @endif
    | Order No: <span style="color: #0a0924;">{{ $order->order_number }}</span>
    @if (!empty($statusLabel))
        | Status: <strong>{{ $statusLabel }}</strong>
    @endif
</div>
