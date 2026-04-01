@extends('emails.orders.layout')

@section('subject', "We've received your order " . $order->order_number . ' — Confirmation in progress')

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        Thank you for your order! We've received it and we're currently working on getting it confirmed.
    </p>

    <div class="highlight-box highlight-info">
        Your order includes one or more products supplied by our affiliate partners. We need to confirm availability with them before we can finalise your order. We will notify you by email as soon as we have their confirmation &mdash; this usually takes up to <strong>1 business day</strong>.
    </div>

    <p class="body-text">
        Once your order is confirmed, you will receive a separate email with a payment link to complete your purchase via BML Payment Gateway. No payment is required from you at this stage.
    </p>

    @include('emails.orders._order-summary')

    @include('emails.orders._order-meta')

    @include('emails.orders._track-order')
@endsection
