@extends('emails.orders.layout')

@section('subject', 'Payment approved — Your order ' . $order->order_number . ' is confirmed')

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        Your bank transfer has been verified and approved. Your order is now confirmed and we're getting everything ready for you.
    </p>

    <div class="highlight-box highlight-success">
        Payment approved. Your order is confirmed and being processed.
    </div>

    @include('emails.orders._order-summary')

    @include('emails.orders._order-meta')

    <p class="body-text">An invoice is attached for your reference.</p>

    @include('emails.orders._track-order')
@endsection
