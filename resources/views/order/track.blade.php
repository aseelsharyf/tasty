@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1">
    <div class="max-w-sm mx-auto px-6 py-16 text-center">

        <div class="w-12 h-12 bg-gray-50 border border-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        </div>

        <h1 class="font-display text-[28px] leading-[1.1] tracking-[-0.02em] text-blue-black mb-2">Track Order</h1>
        <p class="text-gray-400 text-sm mb-8">Enter your order number and contact info to find your order.</p>

        @if(session('error'))
            <div class="mb-6 p-3 rounded-xl bg-white border border-red-200 text-red-600 text-sm text-left">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('order.track.submit') }}" class="space-y-4 text-left">
            @csrf

            <div>
                <label for="order_number" class="block text-sm font-medium text-gray-600 mb-1.5">Order Number</label>
                <input type="text" id="order_number" name="order_number" required
                    value="{{ old('order_number') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition"
                    placeholder="TST-20260316-0001">
            </div>

            <div>
                <label for="contact" class="block text-sm font-medium text-gray-600 mb-1.5">Phone or Email</label>
                <input type="text" id="contact" name="contact" required
                    value="{{ old('contact') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition"
                    placeholder="Your phone number or email">
            </div>

            <button type="submit" class="w-full py-3 bg-blue-black text-white rounded-full text-sm hover:bg-opacity-90 transition mt-2">
                Track Order
            </button>
        </form>

        <a href="{{ route('products.index') }}" class="inline-block text-sm text-gray-400 hover:text-blue-black transition mt-6">Continue Shopping</a>
    </div>
</main>
@endsection
