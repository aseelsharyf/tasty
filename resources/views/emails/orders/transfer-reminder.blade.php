@extends('emails.orders.layout')

@section('subject', 'Reminder — Complete your bank transfer for order ' . $order->order_number)

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        We're still waiting for your bank transfer for order <strong style="color: #0a0924; font-weight: 600;">{{ $order->order_number }}</strong>. Please transfer the amount to the account details below and upload your receipt to confirm your order.
    </p>

    <div class="highlight-box highlight-warning">
        Your order is on hold until we receive your transfer receipt. Please complete this soon to avoid cancellation.
    </div>

    @include('emails.orders._bank-transfer-details')

    <a href="{{ $paymentUrl }}" class="cta-button">Upload Transfer Receipt &rarr;</a>

    @include('emails.orders._order-summary')

    @include('emails.orders._track-order')
@endsection
