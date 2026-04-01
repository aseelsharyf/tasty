@extends('emails.orders.layout')

@section('subject', 'Update on your order ' . $order->order_number . ' — Some items could not be fulfilled')

@section('body')
    <p class="greeting">Hi {{ $order->contact_person }},</p>

    <p class="body-text">
        Thank you for your patience while we worked on confirming your order. Unfortunately, we have an update to share with you regarding order <strong style="color: #0a0924; font-weight: 600;">{{ $order->order_number }}</strong>.
    </p>

    @if ($rejectionType === 'full')
        <div class="highlight-box highlight-danger">
            We regret to inform you that your order could not be fulfilled. Our affiliate partner(s) were unable to confirm the availability of the items in your order at this time. As a result, your order has been cancelled and no payment has been charged.
        </div>

        <p class="body-text">
            We sincerely apologise for the inconvenience. If you would like help finding an alternative or placing a new order, please don't hesitate to get in touch with us.
        </p>

        @include('emails.orders._order-summary', ['statusLabel' => 'Cancelled'])
    @else
        <div class="highlight-box highlight-warning">
            We regret to inform you that one or more items in your order could not be confirmed by our affiliate partner(s) due to unavailability. These items have been removed from your order.
        </div>

        <p class="body-text">
            The remaining items are still available and your updated order is ready to proceed. Please review the updated summary below and let us know if you'd like to continue with the remaining items or cancel the order entirely.
        </p>

        @include('emails.orders._order-summary', ['title' => 'Updated Order Summary', 'totalLabel' => 'Updated Order Total'])

        <div class="highlight-box highlight-warning">
            To proceed with the available items, simply reply to this email or contact us. If we don't hear from you within <strong>48 hours</strong>, the order will be cancelled automatically.
        </div>
    @endif

    @include('emails.orders._track-order')

    <p class="body-text" style="margin-top: 20px;">
        We're sorry for the inconvenience and appreciate your understanding. Please don't hesitate to reach out &mdash; we're happy to help.
    </p>
@endsection
