<div style="margin: 20px 0;">
    <div style="font-size: 13px; font-weight: 600; color: #0a0924; font-weight: 600; margin-bottom: 12px;">Bank Transfer Details</div>
    <div style="background-color: #fffbeb; border-radius: 6px; padding: 16px;">
        @foreach ($bankAccounts as $account)
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; margin-bottom: 8px;">
                <tr>
                    <td style="padding: 6px 0; color: #555; width: 160px;">Bank</td>
                    <td style="padding: 6px 0; font-weight: 600;">{{ $account['bank_name'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #555;">Account Name</td>
                    <td style="padding: 6px 0; color: #0a0924; font-weight: 600;">{{ $account['account_name'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #555;">Account Number</td>
                    <td style="padding: 6px 0; color: #0a0924; font-weight: 600;">{{ $account['account_number'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #555;">Amount to Transfer</td>
                    <td style="padding: 6px 0; color: #0a0924; font-weight: 600;">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #555;">Reference</td>
                    <td style="padding: 6px 0; color: #0a0924; font-weight: 600;">{{ $order->order_number }}</td>
                </tr>
            </table>
            @if (!$loop->last)
                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 12px 0;">
            @endif
        @endforeach
    </div>
</div>
