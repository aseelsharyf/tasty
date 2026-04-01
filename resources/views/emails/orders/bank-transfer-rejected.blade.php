@extends('emails.orders.layout')

@section('subject', 'Transfer receipt not approved — Please resubmit for order ' . $order->order_number)

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        We were unable to verify the bank transfer receipt you submitted for order <strong style="color: #0a0924; font-weight: 600;">{{ $order->order_number }}</strong>. This may be due to an unclear image, an incorrect transfer amount, a missing reference or unsuccessful transfer.
    </p>

    <div class="highlight-box highlight-danger">
        Your receipt has not been approved. Please check the details below and resubmit a valid receipt to proceed with your order.
    </div>

    @include('emails.orders._bank-transfer-details')

    <a href="{{ $paymentUrl }}" class="cta-button">Resubmit Transfer Receipt &rarr;</a>

    <p class="body-text">
        Please make sure the receipt clearly shows the transaction date, amount transferred, and the reference number. If you need assistance, don't hesitate to reach out to us.
    </p>

    @include('emails.orders._order-summary')

    @include('emails.orders._track-order')
@endsection
