@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-white">
    <div class="max-w-2xl mx-auto px-6 py-12 text-center">
        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>

        <h1 class="text-h2 text-blue-black mb-3">Payment Submitted</h1>
        <p class="text-gray-500 mb-8">We have received your payment details for order <strong>{{ $order->order_number }}</strong>. We will verify and process your order shortly.</p>

        @if($order->payment_method === 'bank_transfer' && !empty($bankAccounts))
            <div class="text-left bg-gray-50 rounded-xl p-6 mb-8">
                <h3 class="font-semibold text-blue-black mb-3">Bank Account Details</h3>
                <div class="space-y-4">
                    @foreach($bankAccounts as $account)
                        <div class="text-sm space-y-1">
                            <p class="font-medium">{{ $account['bank_name'] }}</p>
                            <p class="text-gray-500">Account: {{ $account['account_name'] }}</p>
                            <p class="text-gray-500">Number: {{ $account['account_number'] }}</p>
                            <p class="text-gray-500">Currency: {{ $account['currency'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('order.track') }}" class="inline-flex items-center px-6 py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition">
                Track Order
            </a>
            <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Continue Shopping</a>
        </div>
    </div>
</main>
@endsection
