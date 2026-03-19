@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1">
    <div class="max-w-2xl mx-auto px-6 py-12 text-center">
        @if($status === \App\Enums\PaymentStatus::Failed)
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>

            <h1 class="text-h2 text-blue-black mb-3">Payment Failed</h1>
            <p class="text-gray-500 mb-6">Your payment could not be completed. Don't worry &mdash; no charges were made.</p>

            <div class="inline-block px-6 py-3 bg-gray-100 rounded-lg mb-8">
                <span class="text-xl font-mono font-bold text-blue-black">{{ $order->order_number }}</span>
            </div>

            <div class="text-left bg-red-50 border border-red-100 rounded-xl p-4 mb-8 text-sm text-red-800">
                <p class="font-medium mb-1">What happened?</p>
                <p>The payment was cancelled or declined. You can try again with the same or a different payment method.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('payment.index', $order) }}" class="inline-flex items-center px-6 py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition">
                    Try Again
                </a>
                <a href="{{ route('order.track') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 rounded-full font-medium hover:bg-gray-50 transition">
                    Track Order
                </a>
                <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Continue Shopping</a>
            </div>
        @else
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>

            <h1 class="text-h2 text-blue-black mb-3">Payment Processing</h1>
            <p class="text-gray-500 mb-6">Your payment is being verified. This usually takes a few moments.</p>

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
                        <span class="text-gray-500">Payment</span>
                        <span class="font-medium">{{ $status->label() }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-8 text-sm text-amber-800 text-left">
                <p class="font-medium mb-1">Waiting for confirmation</p>
                <p>Your payment is being processed by the bank. You'll see the updated status on your order tracking page once it's confirmed.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('order.track') }}" class="inline-flex items-center px-6 py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition">
                    Track Order
                </a>
                <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Continue Shopping</a>
            </div>
        @endif
    </div>
</main>
@endsection
