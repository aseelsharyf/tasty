@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-white">
    <div class="max-w-lg mx-auto px-6 py-12">
        <h1 class="text-h2 text-blue-black mb-2 text-center">Payment</h1>
        <p class="text-gray-500 text-center mb-8">Order {{ $order->order_number }} &mdash; {{ number_format($order->total, 2) }} {{ $order->currency }}</p>

        <div class="space-y-4">
            @foreach($paymentMethods as $method)
                @if($method['key'] === 'bank_transfer')
                    <a href="#bank-transfer-form"
                        onclick="document.getElementById('bank-transfer-form').classList.toggle('hidden')"
                        class="flex items-center gap-4 p-4 border border-gray-200 rounded-xl hover:border-blue-400 transition cursor-pointer">
                        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div>
                            <p class="font-medium text-blue-black">{{ $method['name'] }}</p>
                            <p class="text-xs text-gray-500">Transfer and upload receipt</p>
                        </div>
                    </a>

                    <div id="bank-transfer-form" class="hidden border border-gray-200 rounded-xl p-6">
                        <form method="POST" action="{{ route('payment.bank-transfer', $order) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Upload Receipt *</label>
                                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required
                                    class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                                <p class="text-xs text-gray-400 mt-1">JPG, PNG or PDF, max 5MB</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" placeholder="Reference number, etc."></textarea>
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-blue-black text-white rounded-full font-medium text-sm hover:bg-opacity-90 transition">
                                Submit Receipt
                            </button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('payment.gateway', $order) }}">
                        @csrf
                        <input type="hidden" name="method" value="{{ $method['key'] }}">
                        <button type="submit" class="w-full flex items-center gap-4 p-4 border border-gray-200 rounded-xl hover:border-blue-400 transition text-left">
                            <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-blue-black">{{ $method['name'] }}</p>
                                <p class="text-xs text-gray-500">Pay online</p>
                            </div>
                        </button>
                    </form>
                @endif
            @endforeach
        </div>
    </div>
</main>
@endsection
