@extends('emails.orders.layout')

@section('subject', 'Your order ' . $order->order_number . ' is confirmed — Please complete your payment')

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        We have great news! Your order has been confirmed by our affiliate partner(s) and is now ready for payment.
    </p>

    <div class="highlight-box highlight-success">
        Your order is confirmed. Please complete your payment within <strong>24 hours</strong> to avoid cancellation.
    </div>

    <a href="{{ $paymentUrl }}" class="cta-button">Complete Payment via BML Gateway &rarr;</a>

    @include('emails.orders._order-summary')

    @include('emails.orders._order-meta')

    @include('emails.orders._track-order')
@endsection
