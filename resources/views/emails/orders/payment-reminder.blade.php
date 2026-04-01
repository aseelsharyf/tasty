@extends('emails.orders.layout')

@section('subject', 'Action needed — Complete your payment for order ' . $order->order_number)

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        We noticed that your payment for order <strong style="color: #0a0924; font-weight: 600;">{{ $order->order_number }}</strong> has not been completed yet. Your order is on hold and will be cancelled if payment is not made soon.
    </p>

    <div class="highlight-box highlight-warning">
        Please complete your payment as soon as possible to secure your order. Orders with incomplete payments may be cancelled.
    </div>

    <a href="{{ $paymentUrl }}" class="cta-button">Complete Payment via BML Gateway &rarr;</a>

    @include('emails.orders._order-summary')

    <p class="body-text">
        If you're having trouble with the payment, feel free to reach out to us and we'll help you sort it out.
    </p>

    @include('emails.orders._track-order')
@endsection
