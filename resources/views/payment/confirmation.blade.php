@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-white">
    <div class="max-w-2xl mx-auto px-6 py-12 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>

        @if($order->payment_method === \App\Enums\PaymentMethod::BmlGateway)
            <h1 class="text-h2 text-blue-black mb-3">Payment Confirmed</h1>
            <p class="text-gray-500 mb-6">Your online payment has been received. We will process your order shortly.</p>
        @else
            <h1 class="text-h2 text-blue-black mb-3">Payment Submitted</h1>
            <p class="text-gray-500 mb-6">We have received your payment details. We will verify and process your order shortly.</p>
        @endif

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
                    <span class="font-medium">{{ $order->payment_status->label() }}</span>
                </div>
                @if($order->payment_method)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Method</span>
                        <span>{{ $order->payment_method->label() }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if($order->payment_method === \App\Enums\PaymentMethod::BankTransfer && !empty($bankAccounts))
            <div class="text-left bg-gray-50 rounded-xl p-6 mb-8">
                <h3 class="font-semibold text-blue-black mb-3">Bank Account Details</h3>
                <div class="space-y-4">
                    @foreach($bankAccounts as $account)
                        <div class="text-sm space-y-1">
                            <p class="font-medium">{{ $account['bank_name'] }}</p>
                            <p class="text-gray-500">{{ $account['account_name'] }} &mdash; {{ $account['account_number'] }}</p>
                            <p class="text-gray-500">Currency: {{ $account['currency'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-8 text-sm text-amber-800 text-left">
            <p class="font-medium mb-1">Keep your records</p>
            <p>We recommend saving your order number and payment details for your reference.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('order.track') }}" class="inline-flex items-center px-6 py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition">
                Track Order
            </a>
            <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Continue Shopping</a>
        </div>
    </div>
</main>
@endsection
