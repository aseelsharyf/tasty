@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1">
    <div class="max-w-lg mx-auto px-6 py-16 text-center">

        {{-- Success icon --}}
        <div class="w-12 h-12 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm shadow-emerald-200">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>

        <h1 class="font-display text-[32px] leading-[1.1] tracking-[-0.02em] text-blue-black mb-2">Thank you!</h1>
        <p class="text-gray-400 mb-8">Your order has been placed successfully.</p>

        {{-- Order number --}}
        <div class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 rounded-xl mb-10">
            <span class="text-xs text-gray-400 uppercase tracking-wider">Order</span>
            <span class="text-base font-mono text-blue-black">{{ $order->order_number }}</span>
        </div>

        {{-- Order items --}}
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

            {{-- Totals --}}
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

        {{-- Delivery details --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6 text-left">
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Status</span>
                    <span class="text-blue-black">{{ $order->status->label() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Contact</span>
                    <span class="text-blue-black">{{ $order->contact_person }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Phone</span>
                    <span class="text-blue-black">{{ $order->contact_number }}</span>
                </div>
                @if($order->deliveryLocation)
                    <div class="flex justify-between">
                        <span class="text-gray-400">Delivery</span>
                        <span class="text-blue-black">{{ $order->deliveryLocation->name }}</span>
                    </div>
                @endif
                @if($order->address)
                    <div class="flex justify-between gap-8">
                        <span class="text-gray-400 shrink-0">Address</span>
                        <span class="text-blue-black text-right">{{ $order->address }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if($order->has_affiliate_products && $order->status->value === 'pending')
            <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6 text-sm text-gray-500 text-left">
                <p class="text-blue-black mb-1">Waiting for seller confirmation</p>
                <p>Your order contains items from partner sellers. We'll notify you once the order is accepted and ready for payment.</p>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-col gap-3 mt-8">
            @if($order->status->value === 'accepted' && $order->payment_status->value === 'unpaid')
                <a href="{{ route('payment.index', $order) }}" class="w-full py-3.5 bg-blue-black text-white rounded-full text-sm hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                    Proceed to Payment
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            @endif
            <a href="{{ route('order.track') }}" class="w-full py-3 bg-white border border-gray-200 text-blue-black rounded-full text-sm hover:bg-gray-50 transition flex items-center justify-center">
                Track Order
            </a>
            <a href="{{ route('products.index') }}" class="text-sm text-gray-400 hover:text-blue-black transition mt-1">Continue Shopping</a>
        </div>
    </div>
</main>
@endsection
