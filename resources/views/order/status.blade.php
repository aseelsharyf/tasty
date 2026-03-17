@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-white">
    <div class="max-w-2xl mx-auto px-6 py-12">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-h3 text-blue-black">Order {{ $order->order_number }}</h1>
                <p class="text-sm text-gray-500 mt-1">Placed on {{ $order->created_at->format('M d, Y \a\t H:i') }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                style="background-color: var(--badge-bg); color: var(--badge-text);"
                x-data="{ color: '{{ $order->status->color() }}' }">
                {{ $order->status->label() }}
            </span>
        </div>

        <!-- Order Items -->
        <div class="bg-gray-50 rounded-xl p-6 mb-6">
            <h3 class="font-semibold text-blue-black mb-4">Items</h3>
            <div class="space-y-3">
                @foreach($order->items as $item)
                    <div class="flex justify-between text-sm">
                        <div>
                            <span class="font-medium">{{ $item->product_title }}</span>
                            @if($item->variant_name)
                                <span class="text-gray-500">({{ $item->variant_name }})</span>
                            @endif
                            <span class="text-gray-400">x{{ $item->quantity }}</span>
                        </div>
                        <span class="font-medium">{{ number_format($item->total, 2) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between font-semibold text-lg border-t border-gray-200 mt-4 pt-4">
                <span>Total</span>
                <span>{{ number_format($order->total, 2) }} {{ $order->currency }}</span>
            </div>
        </div>

        <!-- Delivery Info -->
        <div class="bg-gray-50 rounded-xl p-6 mb-6">
            <h3 class="font-semibold text-blue-black mb-4">Delivery</h3>
            <div class="space-y-2 text-sm">
                <p><span class="text-gray-500">Contact:</span> {{ $order->contact_person }} ({{ $order->contact_number }})</p>
                @if($order->deliveryLocation)
                    <p><span class="text-gray-500">Location:</span> {{ $order->deliveryLocation->name }}</p>
                @endif
                <p><span class="text-gray-500">Address:</span> {{ $order->address }}</p>
                <p><span class="text-gray-500">Payment:</span> {{ $order->payment_status->label() }}</p>
            </div>
        </div>

        <!-- Status History -->
        @if($order->statusHistory->isNotEmpty())
            <div class="bg-gray-50 rounded-xl p-6 mb-6">
                <h3 class="font-semibold text-blue-black mb-4">Status History</h3>
                <div class="space-y-3">
                    @foreach($order->statusHistory as $history)
                        <div class="flex items-start gap-3 text-sm">
                            <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                            <div>
                                <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $history->to_status)) }}</p>
                                <p class="text-gray-400 text-xs">{{ $history->created_at->format('M d, Y H:i') }}</p>
                                @if($history->notes)
                                    <p class="text-gray-500 mt-1">{{ $history->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex items-center justify-center gap-4">
            @if(in_array($order->status->value, ['accepted', 'payment_pending']))
                <a href="{{ route('payment.index', $order) }}" class="inline-flex items-center px-6 py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition">
                    Make Payment
                </a>
            @endif
            <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Continue Shopping</a>
        </div>
    </div>
</main>
@endsection
