@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-white">
    <div class="max-w-lg mx-auto px-6 py-12">
        <h1 class="text-h2 text-blue-black mb-2 text-center">Payment</h1>
        <p class="text-gray-500 text-center mb-8">Order {{ $order->order_number }} &mdash; {{ number_format($order->total, 2) }} {{ $order->currency }}</p>

        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

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
                @elseif($method['key'] === 'bml_gateway')
                    <form method="POST" action="{{ route('payment.gateway', $order) }}">
                        @csrf
                        <input type="hidden" name="method" value="{{ $method['key'] }}">
                        <button type="submit" class="w-full flex items-center gap-4 p-4 border border-gray-200 rounded-xl hover:border-blue-400 transition text-left">
                            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-blue-black">{{ $method['name'] }}</p>
                                <p class="text-xs text-gray-500">Pay securely with Visa or Mastercard</p>
                            </div>
                            <div class="flex items-center gap-2 ml-auto">
                                {{-- Visa --}}
                                <svg class="h-5" viewBox="0 0 780 500" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="780" height="500" rx="40" fill="#1A1F71"/><path d="M293.2 348.7l33.4-195.7h53.4l-33.4 195.7H293.2zM532.7 157.4c-10.6-4-27.2-8.3-47.9-8.3-52.8 0-90 26.6-90.2 64.6-.3 28.1 26.5 43.8 46.8 53.2 20.8 9.6 27.8 15.7 27.7 24.3-.1 13.1-16.6 19.1-31.9 19.1-21.4 0-32.7-3-50.3-10.2l-6.9-3.1-7.5 43.8c12.5 5.5 35.6 10.2 59.6 10.5 56.2 0 92.6-26.3 93-66.8.2-22.3-14-39.2-44.8-53.2-18.6-9.1-30.1-15.1-30-24.3 0-8.1 9.7-16.8 30.6-16.8 17.4-.3 30.1 3.5 39.9 7.5l4.8 2.3 7.1-42.6zM642.4 153h-41.3c-12.8 0-22.4 3.5-28 16.3l-79.4 179.4h56.1l11.2-29.4h68.6l6.5 29.4h49.6L642.4 153zm-66 125.9c4.4-11.3 21.5-54.7 21.5-54.7-.3.5 4.4-11.4 7.1-18.8l3.6 17s10.3 47.2 12.5 57.1h-44.7v-.6zM246.8 153l-52.3 133.5-5.6-27.1c-9.7-31.2-39.9-65-73.7-82l47.8 171.2h56.5l84-195.6h-56.7z" fill="#fff"/><path d="M146.9 153H60.8l-.7 4c67 16.2 111.3 55.4 129.6 102.5L171.5 170c-3.1-12.5-12.5-16.5-24.6-17z" fill="#F9A533"/></svg>
                                {{-- Mastercard --}}
                                <svg class="h-5" viewBox="0 0 780 500" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="780" height="500" rx="40" fill="#16366F"/><circle cx="330" cy="250" r="150" fill="#EB001B"/><circle cx="450" cy="250" r="150" fill="#F79E1B"/><path d="M390 130.7c37.5 29.3 61.6 75 61.6 126.3s-24.1 97-61.6 126.3c-37.5-29.3-61.6-75-61.6-126.3s24.1-97 61.6-126.3z" fill="#FF5F00"/></svg>
                            </div>
                        </button>
                    </form>
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

        {{-- Payment security & compliance info --}}
        <div class="mt-8 bg-gray-50 rounded-xl p-4 text-sm">
            <div class="flex items-start gap-3 text-gray-500">
                <svg class="w-5 h-5 shrink-0 mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <p>Your payment is processed securely via SSL encryption. We do not store your card details. All card payments are handled by Bank of Maldives (BML Connect).</p>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-center gap-4 text-xs text-gray-400">
            <span>MVR &middot; Maldives</span>
            <span class="text-gray-200">|</span>
            <a href="{{ route('page.show', 'terms-and-conditions') }}" class="hover:text-blue-black transition">Terms</a>
            <a href="{{ route('page.show', 'privacy-policy') }}" class="hover:text-blue-black transition">Privacy</a>
            <a href="{{ route('page.show', 'refund-policy') }}" class="hover:text-blue-black transition">Refunds</a>
        </div>
    </div>
</main>
@endsection
