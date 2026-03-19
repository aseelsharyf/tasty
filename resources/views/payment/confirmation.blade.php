@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1">
    <div class="max-w-lg mx-auto px-6 py-16 text-center">

        {{-- Success icon --}}
        <div class="w-12 h-12 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm shadow-emerald-200">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>

        @if($order->payment_method === \App\Enums\PaymentMethod::BmlGateway)
            <h1 class="font-display text-[32px] leading-[1.1] tracking-[-0.02em] text-blue-black mb-2">Payment Confirmed</h1>
            <p class="text-gray-400 mb-8">Your online payment has been received. We will process your order shortly.</p>
        @else
            <h1 class="font-display text-[32px] leading-[1.1] tracking-[-0.02em] text-blue-black mb-2">Payment Submitted</h1>
            <p class="text-gray-400 mb-8">We have received your payment details. We will verify and process your order shortly.</p>
        @endif

        {{-- Order number --}}
        <div class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 rounded-xl mb-10">
            <span class="text-xs text-gray-400 uppercase tracking-wider">Order</span>
            <span class="text-base font-mono text-blue-black">{{ $order->order_number }}</span>
        </div>

        {{-- Order items --}}
        @if($order->items->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 text-left">
                <div class="space-y-3">
                    @foreach($order->items as $item)
                        <div class="flex items-center gap-3 {{ !$loop->last ? 'pb-3 border-b border-gray-100' : '' }}">
                            <div class="w-12 h-12 bg-gray-50 rounded-lg shrink-0 flex items-center justify-center overflow-hidden border border-gray-100">
                                @if($item->product?->featured_image_url)
                                    <img src="{{ $item->product->featured_image_url }}" alt="{{ $item->product_title }}" class="w-full h-full object-contain p-1">
                                @else
                                    <img src="/images/product-placeholder.svg" alt="{{ $item->product_title }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-blue-black truncate">{{ $item->product_title }}</p>
                                @if($item->variant_name)
                                    <p class="text-xs text-gray-400">{{ $item->variant_name }}</p>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm text-blue-black">{{ number_format($item->total, 2) }}</p>
                                <p class="text-xs text-gray-400">x{{ $item->quantity }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 mt-4 pt-3 space-y-2 text-sm">
                    @if($order->discount_code)
                        <div class="flex justify-between text-gray-400">
                            <span>Subtotal</span>
                            <span>{{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-emerald-600">
                            <span>Discount ({{ $order->discount_code }})</span>
                            <span>-{{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    @if($order->tax_amount > 0)
                        <div class="flex justify-between text-gray-400">
                            <span>Tax</span>
                            <span>{{ number_format($order->tax_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between pt-2 border-t border-gray-100">
                        <span class="text-gray-400">Total</span>
                        <span class="font-display text-[18px] tracking-[-0.02em] text-blue-black">{{ number_format($order->total, 2) }} {{ $order->currency }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Payment & order details --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 text-left">
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Status</span>
                    <span class="text-blue-black">{{ $order->status->label() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Payment</span>
                    <span class="text-blue-black">{{ $order->payment_status->label() }}</span>
                </div>
                @if($order->payment_method)
                    <div class="flex justify-between">
                        <span class="text-gray-400">Method</span>
                        <span class="text-blue-black">{{ $order->payment_method->label() }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bank account details for bank transfer --}}
        @if($order->payment_method === \App\Enums\PaymentMethod::BankTransfer && !empty($bankAccounts))
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 text-left">
                <p class="text-sm text-blue-black mb-3">Bank Account Details</p>
                <div class="space-y-3">
                    @foreach($bankAccounts as $account)
                        <div class="text-sm {{ !$loop->last ? 'pb-3 border-b border-gray-100' : '' }}">
                            <p class="text-blue-black">{{ $account['bank_name'] }}</p>
                            <p class="text-gray-400">{{ $account['account_name'] }} &mdash; {{ $account['account_number'] }} ({{ $account['currency'] }})</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Record keeping notice --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6 text-left">
            <p class="text-xs text-gray-400">We recommend saving your order number and payment details for your reference.</p>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col gap-3 mt-8">
            <a href="{{ route('order.track') }}" class="w-full py-3 bg-white border border-gray-200 text-blue-black rounded-full text-sm hover:bg-gray-50 transition flex items-center justify-center">
                Track Order
            </a>
            <a href="{{ route('products.index') }}" class="text-sm text-gray-400 hover:text-blue-black transition mt-1">Continue Shopping</a>
        </div>
    </div>
</main>
@endsection
