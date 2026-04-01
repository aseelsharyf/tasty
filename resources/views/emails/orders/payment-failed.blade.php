@extends('emails.orders.layout')

@section('subject', 'Payment unsuccessful — Please retry for order ' . $order->order_number)

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        Unfortunately, your payment via BML Payment Gateway for order <strong style="color: #0a0924; font-weight: 600;">{{ $order->order_number }}</strong> was unsuccessful. This can sometimes happen due to insufficient funds, a card issue, or a temporary problem with the payment gateway.
    </p>

    <div class="highlight-box highlight-danger">
        Your order is currently on hold. Please retry the payment to keep your order active.
    </div>

    <a href="{{ $paymentUrl }}" class="cta-button">Retry Payment &rarr;</a>

    <p class="body-text">
        If the problem persists, please contact your bank or try a different card. You can also switch to the bank transfer option if you prefer.
    </p>

    @include('emails.orders._order-summary')

    @include('emails.orders._track-order')
@endsection
