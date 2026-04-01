<div style="margin: 24px 0;">
    <div style="font-size: 13px; font-weight: 600; color: #888888; text-transform: uppercase; margin-bottom: 12px;">
        {{ $title ?? 'Order Summary' }}
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 14px;">
        <thead>
            <tr style="background-color: #f9fafb;">
                <th style="padding: 10px 12px; text-align: left; border-bottom: 1px solid #eeeeee; font-weight: 600; color: #555;">Item</th>
                <th style="padding: 10px 12px; text-align: center; border-bottom: 1px solid #eeeeee; font-weight: 600; color: #555;">Qty</th>
                <th style="padding: 10px 12px; text-align: right; border-bottom: 1px solid #eeeeee; font-weight: 600; color: #555;">Unit Price</th>
                <th style="padding: 10px 12px; text-align: right; border-bottom: 1px solid #eeeeee; font-weight: 600; color: #555;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; color: #0a0924; font-weight: 600;">
                        {{ $item->product_title }}
                        @if ($item->variant_name)
                            <br><span style="font-size: 12px; color: #999;">{{ $item->variant_name }}</span>
                        @endif
                        @if (!empty($item->status_label))
                            <br><span style="font-size: 12px; color: {{ $item->status_color ?? '#999' }};">{{ $item->status_label }}</span>
                        @endif
                    </td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; text-align: center; color: #0a0924; font-weight: 600;">
                        @if (!empty($item->is_struck))
                            <s>{{ $item->quantity }}</s>
                        @else
                            {{ $item->quantity }}
                        @endif
                    </td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; text-align: right;">
                        @if (!empty($item->is_struck))
                            <s style="color: #999;">{{ $order->currency }} {{ number_format($item->price, 2) }}</s>
                        @else
                            {{ $order->currency }} {{ number_format($item->price, 2) }}
                        @endif
                    </td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; text-align: right;">
                        @if (!empty($item->is_struck))
                            <s style="color: #999;">{{ $order->currency }} {{ number_format($item->total, 2) }}</s>
                        @else
                            {{ $order->currency }} {{ number_format($item->total, 2) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="padding: 12px; text-align: right; font-weight: 700;">
                    {{ $totalLabel ?? 'Order Total' }}
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 700;">
                    {{ $order->currency }} {{ number_format($displayTotal ?? $order->total, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
