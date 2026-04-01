@extends('emails.orders.layout')

@section('subject', 'Payment confirmed — Your order ' . $order->order_number . ' is being processed')

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        Great news! Your payment has been successfully received via BML Payment Gateway and your order is now confirmed. We're getting everything ready for you.
    </p>

    <div class="highlight-box highlight-success">
        Your order is confirmed and being processed.
    </div>

    @include('emails.orders._order-summary')

    @include('emails.orders._order-meta')

    <p class="body-text">An invoice is attached for your reference.</p>

    @include('emails.orders._track-order')
@endsection
