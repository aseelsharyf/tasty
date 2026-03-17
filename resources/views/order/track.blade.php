@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-white">
    <div class="max-w-md mx-auto px-6 py-12">
        <h1 class="text-h2 text-blue-black mb-2 text-center">Track Order</h1>
        <p class="text-gray-500 text-center mb-8">Enter your order number and contact info to track your order.</p>

        @if(session('error'))
            <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('order.track.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="order_number" class="block text-sm font-medium text-gray-700 mb-1">Order Number</label>
                <input type="text" id="order_number" name="order_number" required
                    value="{{ old('order_number') }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400"
                    placeholder="TST-20260316-0001">
            </div>

            <div>
                <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">Phone or Email</label>
                <input type="text" id="contact" name="contact" required
                    value="{{ old('contact') }}"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-400"
                    placeholder="Your phone number or email">
            </div>

            <button type="submit" class="w-full py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition">
                Track Order
            </button>
        </form>
    </div>
</main>
@endsection
