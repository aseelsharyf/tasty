@extends('emails.orders.layout')

@section('subject', 'Transfer receipt received — Order ' . $order->order_number . ' pending approval')

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        Thank you for your order! We've received your bank transfer receipt and it is currently under review by our team. We'll send you a confirmation email as soon as it has been approved.
    </p>

    <div class="highlight-box highlight-info">
        Your receipt is being reviewed. This usually takes a short while during business hours. Please do not resubmit unless requested.
    </div>

    @include('emails.orders._order-summary')

    @include('emails.orders._order-meta')

    @include('emails.orders._track-order')
@endsection
