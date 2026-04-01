@extends('emails.orders.layout')

@section('subject', 'Your order ' . $order->order_number . ' is confirmed — Please complete your bank transfer')

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        We have great news! Your order has been confirmed by our affiliate partner(s) and is now ready for payment.
    </p>

    <div class="highlight-box highlight-success">
        Your order is confirmed. Please complete your bank transfer within <strong>24 hours</strong> and upload your receipt to avoid cancellation.
    </div>

    <p class="body-text">
        Please transfer the amount to the bank account below and upload your receipt using the button provided.
    </p>

    @include('emails.orders._bank-transfer-details')

    <a href="{{ $paymentUrl }}" class="cta-button">Upload Transfer Receipt &rarr;</a>

    @include('emails.orders._order-summary')

    @include('emails.orders._order-meta')

    @include('emails.orders._track-order')
@endsection
