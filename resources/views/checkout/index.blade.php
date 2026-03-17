@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1 bg-gray-50/50">
    <div class="max-w-[1200px] mx-auto px-6 py-10">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-gray-400 mb-8">
            <a href="{{ route('cart.index') }}" class="hover:text-blue-black transition">Cart</a>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-blue-black font-medium">Checkout</span>
        </nav>

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <span class="font-medium">Please fix the following errors:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 ml-6">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                {{-- LEFT COLUMN: Form --}}
                <div class="lg:col-span-7 space-y-6">

                    {{-- Contact Information --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-blue-black mb-5 flex items-center gap-2">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-black text-white text-xs font-bold">1</span>
                            Contact Information
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact_person" class="block text-sm font-medium text-gray-600 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                                <input type="text" id="contact_person" name="contact_person" required
                                    value="{{ old('contact_person') }}"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition"
                                    placeholder="Ahmed Mohamed">
                            </div>
                            <div>
                                <label for="contact_number" class="block text-sm font-medium text-gray-600 mb-1.5">Phone <span class="text-red-400">*</span></label>
                                <input type="tel" id="contact_number" name="contact_number" required
                                    value="{{ old('contact_number') }}"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition"
                                    placeholder="7xxxxxx">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="email" class="block text-sm font-medium text-gray-600 mb-1.5">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="email" id="email" name="email"
                                    value="{{ old('email') }}"
                                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition"
                                    placeholder="email@example.com">
                            </div>
                        </div>
                    </div>

                    {{-- Delivery --}}
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-blue-black mb-5 flex items-center gap-2">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-black text-white text-xs font-bold">2</span>
                            Delivery
                        </h2>

                        @if($deliveryLocations->isNotEmpty())
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-600 mb-2">Delivery Location</label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    @foreach($deliveryLocations as $location)
                                        <label class="relative flex items-center justify-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-black/30 transition text-sm text-center has-[:checked]:border-blue-black has-[:checked]:bg-blue-black/5 has-[:checked]:ring-1 has-[:checked]:ring-blue-black">
                                            <input type="radio" name="delivery_location_id" value="{{ $location->id }}" class="sr-only"
                                                {{ old('delivery_location_id') == $location->id ? 'checked' : '' }}>
                                            <span>{{ $location->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-600 mb-1.5">Address <span class="text-red-400">*</span></label>
                            <textarea id="address" name="address" rows="3" required
                                class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition resize-none"
                                placeholder="House name, street, island...">{{ old('address') }}</textarea>
                        </div>

                        <div class="mt-4">
                            <label for="additional_info" class="block text-sm font-medium text-gray-600 mb-1.5">Order Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea id="additional_info" name="additional_info" rows="2"
                                class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition resize-none"
                                placeholder="Any special instructions for your order...">{{ old('additional_info') }}</textarea>
                        </div>
                    </div>

                    {{-- Payment Method (only when we have in-house items to collect payment for) --}}
                    @if($collectPaymentNow && $paymentMethods->isNotEmpty())
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-blue-black mb-5 flex items-center gap-2">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-black text-white text-xs font-bold">3</span>
                            Payment Method
                        </h2>
                        <div class="space-y-2">
                            @foreach($paymentMethods as $method)
                                <label class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-blue-black/30 transition has-[:checked]:border-blue-black has-[:checked]:bg-blue-black/5 has-[:checked]:ring-1 has-[:checked]:ring-blue-black">
                                    <input type="radio" name="payment_method" value="{{ $method['key'] }}" class="sr-only"
                                        {{ old('payment_method', $loop->first ? $method['key'] : '') === $method['key'] ? 'checked' : '' }}>
                                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 shrink-0">
                                        @if($method['type'] === 'bank_transfer')
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                                        @elseif($method['type'] === 'gateway')
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-blue-black">{{ $method['name'] }}</p>
                                        <p class="text-xs text-gray-400">
                                            @if($method['type'] === 'bank_transfer')
                                                Transfer and upload receipt after placing order
                                            @elseif($method['type'] === 'gateway')
                                                Pay securely online
                                            @else
                                                Pay via {{ $method['name'] }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 shrink-0 flex items-center justify-center peer-checked:border-blue-black">
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        {{-- Bank Account Details (shown when bank transfer is selected) --}}
                        @if($bankAccounts && count($bankAccounts) > 0)
                            <div class="mt-4 p-4 bg-amber-50/50 border border-amber-100 rounded-lg text-sm" id="bank-details">
                                <p class="font-medium text-amber-800 mb-2">Bank Transfer Details</p>
                                @foreach($bankAccounts as $account)
                                    <div class="text-amber-700 {{ !$loop->first ? 'mt-3 pt-3 border-t border-amber-100' : '' }}">
                                        <p class="font-medium">{{ $account['bank_name'] }}</p>
                                        <p>{{ $account['account_name'] }} &mdash; {{ $account['account_number'] }} ({{ $account['currency'] }})</p>
                                    </div>
                                @endforeach
                                <p class="text-xs text-amber-600 mt-3">You can upload your transfer receipt after placing the order.</p>
                            </div>
                        @endif
                    </div>
                    @endif

                    {{-- Place Order Button (mobile) --}}
                    <div class="lg:hidden">
                        <button type="submit" class="w-full py-3.5 bg-blue-black text-white rounded-full font-medium text-base hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Place Order &mdash; {{ number_format($total, 2) }} MVR
                        </button>
                    </div>
                </div>

                {{-- RIGHT COLUMN: Order Summary (sticky) --}}
                <div class="lg:col-span-5">
                    <div class="lg:sticky lg:top-32">
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-blue-black mb-5">Order Summary</h2>

                            {{-- Items --}}
                            <div class="space-y-4 mb-6">
                                @foreach($items as $item)
                                    <div class="flex gap-3">
                                        <div class="relative w-16 h-16 bg-gray-100 rounded-lg shrink-0 border border-gray-100 overflow-visible">
                                            @if($item['product']['featured_image_url'])
                                                <img src="{{ $item['product']['featured_image_url'] }}" alt="{{ $item['product']['title'] }}" class="w-full h-full object-contain p-1">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                </div>
                                            @endif
                                            <span class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-blue-black text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ $item['quantity'] }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-blue-black truncate">{{ $item['product']['title'] }}</p>
                                            @if($item['variant'])
                                                <p class="text-xs text-gray-400">{{ $item['variant']['name'] }}</p>
                                            @endif
                                        </div>
                                        <p class="text-sm font-medium text-blue-black shrink-0">{{ number_format($item['total'], 2) }}</p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Totals --}}
                            <div class="border-t border-gray-100 pt-4 space-y-2">
                                <div class="flex justify-between text-sm text-gray-500">
                                    <span>Subtotal ({{ $itemCount }} {{ $itemCount === 1 ? 'item' : 'items' }})</span>
                                    <span>{{ number_format($subtotal, 2) }}</span>
                                </div>
                                @if($discount)
                                    <div class="flex justify-between text-sm text-green-600">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                            {{ $discount['code'] }} ({{ $discount['label'] }})
                                        </span>
                                        <span>-{{ number_format($discount['amount'], 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between text-sm text-gray-500">
                                    <span>Shipping</span>
                                    <span class="text-green-600 font-medium">Calculated after</span>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 mt-4 pt-4">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-base font-semibold text-blue-black">Total</span>
                                    <div class="text-right">
                                        <span class="text-xl font-bold text-blue-black">{{ number_format($total, 2) }}</span>
                                        <span class="text-sm text-gray-500 ml-1">MVR</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Place Order Button (desktop) --}}
                            <div class="hidden lg:block mt-6">
                                <button type="submit" class="w-full py-3.5 bg-blue-black text-white rounded-full font-medium text-base hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Place Order
                                </button>
                            </div>

                            <p class="text-xs text-center text-gray-400 mt-4">
                                By placing your order, you agree to our terms and conditions.
                            </p>
                        </div>

                        {{-- Security badges --}}
                        <div class="flex items-center justify-center gap-4 mt-4 text-xs text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Secure checkout
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Protected data
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</main>
@endsection
