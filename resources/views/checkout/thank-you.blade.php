@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-white">
    <div class="max-w-2xl mx-auto px-6 py-12 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>

        <h1 class="text-h2 text-blue-black mb-3">Order Placed!</h1>
        <p class="text-gray-500 mb-6">Thank you for your order. Your order number is:</p>

        <div class="inline-block px-6 py-3 bg-gray-100 rounded-lg mb-8">
            <span class="text-xl font-mono font-bold text-blue-black">{{ $order->order_number }}</span>
        </div>

        <div class="text-left bg-gray-50 rounded-xl p-6 mb-8">
            <h3 class="font-semibold text-blue-black mb-3">Order Details</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="font-medium">{{ $order->status->label() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total</span>
                    <span class="font-medium">{{ number_format($order->total, 2) }} {{ $order->currency }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Contact</span>
                    <span>{{ $order->contact_person }}</span>
                </div>
                @if($order->deliveryLocation)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Delivery to</span>
                        <span>{{ $order->deliveryLocation->name }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if($order->has_affiliate_products && $order->status->value === 'pending')
            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-8 text-sm text-amber-800 text-left">
                <p class="font-medium mb-1">Your order contains affiliate products</p>
                <p>These items need to be confirmed by the seller before payment. We'll notify you once the order is accepted.</p>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            @if($order->status->value === 'accepted' && $order->payment_status->value === 'unpaid')
                <a href="{{ route('payment.index', $order) }}" class="inline-flex items-center px-6 py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition">
                    Proceed to Payment
                </a>
            @endif
            <a href="{{ route('order.track') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 rounded-full font-medium hover:bg-gray-50 transition">
                Track Order
            </a>
            <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Continue Shopping</a>
        </div>
    </div>
</main>
@endsection
