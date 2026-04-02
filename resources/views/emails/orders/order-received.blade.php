@extends('emails.orders.layout')

@section('subject', 'Order confirmed — ' . $order->order_number)

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        Thank you for your order! Your order has been confirmed and is ready for payment.
    </p>

    <div class="highlight-box highlight-success">
        Your order is confirmed. Please proceed with payment to complete your purchase.
    </div>

    @include('emails.orders._order-summary')

    @include('emails.orders._order-meta')

    @include('emails.orders._track-order')
@endsection
