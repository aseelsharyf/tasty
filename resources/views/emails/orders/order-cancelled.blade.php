@extends('emails.orders.layout')

@section('subject', 'Your order ' . $order->order_number . ' has been cancelled')

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        We regret to inform you that your order <strong style="color: #0a0924; font-weight: 600;">{{ $order->order_number }}</strong> has been cancelled. We sincerely apologise for the inconvenience this may have caused.
    </p>

    <div class="highlight-box highlight-danger">
        Your order has been cancelled and will not be processed further.
    </div>

    @include('emails.orders._order-summary', ['statusLabel' => 'Cancelled'])

    @include('emails.orders._order-meta', ['statusLabel' => 'Cancelled'])

    @if ($wasPaymentMade)
        <div style="margin: 20px 0;">
            <div style="font-size: 13px; font-weight: 600; color: #0a0924; font-weight: 600; margin-bottom: 12px;">Refund Information</div>
            <div style="background-color: #f0fdf4; border-radius: 6px; padding: 16px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px;">
                    <tr>
                        <td style="padding: 6px 0; color: #555; width: 160px;">Amount Paid</td>
                        <td style="padding: 6px 0; color: #0a0924; font-weight: 600;">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #555;">Refund Amount</td>
                        <td style="padding: 6px 0; color: #0a0924; font-weight: 600;">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #555;">Refund Method</td>
                        <td style="padding: 6px 0; color: #0a0924; font-weight: 600;">{{ $order->payment_method?->label() }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #555;">Estimated Timeline</td>
                        <td style="padding: 6px 0;">Subject to our refund policy</td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="body-text">
            A refund will be processed in accordance with our Refund Policy. Please allow the stated timeframe for the refund to reflect in your account. If you have any questions about your refund, please contact us directly.
        </p>
    @else
        <div class="highlight-box highlight-info">
            No payment was charged for this order. You have not been billed and no refund is required.
        </div>
    @endif

    <p class="body-text">
        We understand this is disappointing and we truly appreciate your understanding. If you have any questions or would like to place a new order, please feel free to reach out to us &mdash; we're happy to assist.
    </p>

    @include('emails.orders._track-order')
@endsection
