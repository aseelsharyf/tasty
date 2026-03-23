@extends('layouts.app')

@section('content')
<div class="h-[96px] md:h-[112px]"></div>
<main class="flex-1">
    <div class="max-w-[1200px] mx-auto px-6 py-10"
        x-data="cartPage({{ Js::from($items) }}, {{ $total }}, {{ $itemCount }}, {{ Js::from($discount) }})"
    >

        {{-- Toast --}}
        <div x-show="toast" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            :class="toastType === 'error' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-green-50 border-green-100 text-green-700'"
            class="mb-6 p-4 rounded-xl border text-sm flex items-center gap-2">
            <template x-if="toastType !== 'error'">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </template>
            <template x-if="toastType === 'error'">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
            </template>
            <span x-text="toast"></span>
        </div>

        {{-- Empty State --}}
        <div x-show="items.length === 0" x-cloak>
            <div class="text-center py-24">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                </div>
                <h2 class="font-display text-[24px] leading-[1.1] tracking-[-0.02em] text-blue-black mb-2">Your cart is empty</h2>
                <p class="text-gray-500 mb-6">Looks like you haven't added anything yet.</p>
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-black text-white rounded-full font-medium hover:bg-opacity-90 transition">
                    Browse Products
                </a>
            </div>
        </div>

        {{-- Cart Content --}}
        <div x-show="items.length > 0" class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                {{-- LEFT: Cart Items --}}
                <div class="lg:col-span-8">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="font-display text-[28px] leading-[1.1] tracking-[-0.02em] text-blue-black">Cart <span class="text-gray-400 text-[20px]" x-text="'(' + itemCount + ')'"></span></h1>
                        <a href="{{ route('products.index') }}" class="text-sm text-gray-500 hover:text-blue-black transition flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Continue Shopping
                        </a>
                    </div>

                    {{-- Table Header (desktop) --}}
                    <div class="hidden md:grid grid-cols-12 gap-4 px-4 pb-3 text-xs font-medium text-gray-400 uppercase tracking-wider">
                        <div class="col-span-6">Product</div>
                        <div class="col-span-2 text-center">Price</div>
                        <div class="col-span-2 text-center">Quantity</div>
                        <div class="col-span-2 text-right">Total</div>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, idx) in items" :key="item.id">
                            <div class="bg-white rounded-xl border border-gray-200 p-4 md:p-5">
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    {{-- Product --}}
                                    <div class="col-span-12 md:col-span-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-20 h-20 bg-gray-50 rounded-lg overflow-hidden shrink-0 border border-gray-100 flex items-center justify-center">
                                                <img x-show="item.product.featured_image_url" :src="item.product.featured_image_url" :alt="item.product.title" class="w-full h-full object-contain p-2">
                                                <svg x-show="!item.product.featured_image_url" class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <h3 class="font-medium text-blue-black text-base leading-snug" x-text="item.product.title"></h3>
                                                <p x-show="item.variant" class="text-xs text-gray-400 mt-0.5" x-text="item.variant ? item.variant.name : ''"></p>
                                                {{-- Mobile price --}}
                                                <p class="text-sm text-blue-black mt-1 md:hidden" x-text="parseFloat(item.price).toFixed(2) + ' ' + (item.product.currency || 'MVR')"></p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Price (desktop) --}}
                                    <div class="hidden md:flex md:col-span-2 items-center justify-center">
                                        <span class="text-sm text-gray-600" x-text="parseFloat(item.price).toFixed(2)"></span>
                                    </div>

                                    {{-- Quantity --}}
                                    <div class="col-span-6 md:col-span-2 flex items-center justify-start md:justify-center">
                                        <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                            <button @click="updateQty(idx, items[idx].quantity - 1)"
                                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-black hover:bg-gray-50 transition">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                            </button>
                                            <span class="w-10 h-8 flex items-center justify-center text-sm font-medium text-blue-black bg-gray-50/50 border-x border-gray-200" x-text="item.quantity"></span>
                                            <button @click="updateQty(idx, items[idx].quantity + 1)"
                                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-black hover:bg-gray-50 transition">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Total + Remove --}}
                                    <div class="col-span-6 md:col-span-2 flex items-center justify-end">
                                        <div class="text-right">
                                            <p class="text-sm text-blue-black" x-text="parseFloat(item.total).toFixed(2)"></p>
                                            <button @click="removeItem(item.id)" class="mt-1 text-xs text-gray-400 hover:text-red-500 transition flex items-center gap-1 ml-auto">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Clear Cart --}}
                    <div class="mt-4 flex justify-start">
                        <button @click="clearCart()" class="text-sm text-gray-400 hover:text-red-500 transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Clear Cart
                        </button>
                    </div>
                </div>

                {{-- RIGHT: Order Summary --}}
                <div class="lg:col-span-4">
                    <div class="lg:sticky lg:top-32">
                        <div class="bg-white rounded-xl border border-gray-200 p-6">
                            <h2 class="font-display text-[22px] leading-[1.1] tracking-[-0.02em] text-blue-black mb-5">Order Summary</h2>

                            {{-- Discount Code --}}
                            <div class="mb-5">
                                <div x-show="!discount" class="flex gap-2">
                                    <input type="text" x-model="discountInput" placeholder="Discount code"
                                        @keydown.enter.prevent="applyDiscount()"
                                        class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-blue-black/10 focus:border-blue-black transition">
                                    <button @click="applyDiscount()" :disabled="discountLoading || !discountInput.trim()"
                                        class="px-4 py-2 text-sm font-medium text-blue-black border border-gray-200 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                                        <span x-show="!discountLoading">Apply</span>
                                        <svg x-show="discountLoading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </button>
                                </div>
                                <p x-show="discountError" x-text="discountError" x-cloak class="text-xs text-red-500 mt-1"></p>

                                <div x-show="discount" x-cloak class="flex items-center justify-between p-3 bg-green-50 border border-green-100 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                        <div>
                                            <span class="text-sm font-medium text-green-800" x-text="discount ? discount.code : ''"></span>
                                            <span class="text-xs text-green-600 ml-1" x-text="discount ? '(' + discount.label + ')' : ''"></span>
                                        </div>
                                    </div>
                                    <button @click="removeDiscount()" class="text-green-600 hover:text-red-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between text-gray-500">
                                    <span x-text="'Subtotal (' + itemCount + ' ' + (itemCount === 1 ? 'item' : 'items') + ')'"></span>
                                    <span class="text-blue-black" x-text="parseFloat(subtotal).toFixed(2)"></span>
                                </div>
                                <div x-show="discount" x-cloak class="flex justify-between text-green-600">
                                    <span>Discount</span>
                                    <span x-text="'-' + parseFloat(discount ? discount.amount : 0).toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Shipping</span>
                                    <span class="text-gray-400">Calculated at checkout</span>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 mt-5 pt-5">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-base text-blue-black">Total</span>
                                    <div class="text-right">
                                        <span class="font-display text-[24px] tracking-[-0.02em] text-blue-black" x-text="parseFloat(total).toFixed(2)"></span>
                                        <span class="text-sm text-gray-400 ml-1">MVR</span>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('checkout.index') }}"
                                class="mt-6 w-full py-3.5 bg-blue-black text-white rounded-full font-medium text-base hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                                Proceed to Checkout
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>

                            <div class="flex items-center justify-center gap-4 mt-5 text-xs text-gray-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Secure checkout
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
function cartPage(initialItems, initialTotal, initialCount, initialDiscount) {
    var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-XSRF-TOKEN': window.getCsrfToken() };

    return {
        items: initialItems,
        subtotal: initialTotal,
        total: initialDiscount ? Math.max(0, initialTotal - initialDiscount.amount) : initialTotal,
        itemCount: initialCount,
        discount: initialDiscount,
        discountInput: '',
        discountLoading: false,
        discountError: '',
        toast: '',
        toastType: 'success',
        toastTimer: null,

        showToast(message, type) {
            this.toast = message;
            this.toastType = type || 'success';
            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toast = ''; }, 3000);
        },

        recalc() {
            this.subtotal = this.items.reduce(function(sum, i) { return sum + i.total; }, 0);
            this.itemCount = this.items.reduce(function(sum, i) { return sum + i.quantity; }, 0);
            var discountAmt = this.discount ? this.discount.amount : 0;
            this.total = Math.max(0, this.subtotal - discountAmt);
            if (window.updateCartBadge) window.updateCartBadge(this.itemCount);
        },

        async updateQty(idx, newQty) {
            if (newQty < 0 || newQty > 99) return;
            var item = this.items[idx];
            if (!item) return;
            if (newQty === 0) return this.removeItem(item.id);

            var oldQty = item.quantity;
            var oldTotal = item.total;

            // Replace the item in the array to trigger Alpine reactivity
            this.items[idx] = Object.assign({}, item, { quantity: newQty, total: item.price * newQty });
            this.recalc();

            try {
                var r = await fetch('{{ route("cart.update") }}', {
                    method: 'PUT',
                    headers: headers,
                    body: JSON.stringify({ cart_item_id: item.id, quantity: newQty })
                });
                var data = await r.json();
                if (!data.success) {
                    this.items[idx] = Object.assign({}, this.items[idx], { quantity: oldQty, total: oldTotal });
                    this.recalc();
                    this.showToast(data.message || 'Could not update', 'error');
                }
            } catch (e) {
                this.items[idx] = Object.assign({}, this.items[idx], { quantity: oldQty, total: oldTotal });
                this.recalc();
                this.showToast('Something went wrong', 'error');
            }
        },

        async removeItem(cartItemId) {
            var removed = this.items.find(function(i) { return i.id === cartItemId; });
            this.items = this.items.filter(function(i) { return i.id !== cartItemId; });
            this.recalc();

            try {
                var r = await fetch('{{ route("cart.remove") }}', {
                    method: 'DELETE',
                    headers: headers,
                    body: JSON.stringify({ cart_item_id: cartItemId })
                });
                var data = await r.json();
                if (data.success) {
                    this.showToast('Item removed');
                } else {
                    if (removed) this.items.push(removed);
                    this.recalc();
                    this.showToast(data.message || 'Could not remove', 'error');
                }
            } catch (e) {
                if (removed) this.items.push(removed);
                this.recalc();
                this.showToast('Something went wrong', 'error');
            }
        },

        async clearCart() {
            var backup = this.items.slice();
            this.items = [];
            this.recalc();

            try {
                var r = await fetch('{{ route("cart.clear") }}', {
                    method: 'DELETE',
                    headers: headers
                });
                if (r.ok) {
                    this.showToast('Cart cleared');
                } else {
                    this.items = backup;
                    this.recalc();
                    this.showToast('Could not clear cart', 'error');
                }
            } catch (e) {
                this.items = backup;
                this.recalc();
                this.showToast('Something went wrong', 'error');
            }
        },

        async applyDiscount() {
            var code = this.discountInput.trim();
            if (!code) return;
            this.discountLoading = true;
            this.discountError = '';

            try {
                var r = await fetch('{{ route("cart.discount.apply") }}', {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({ code: code })
                });
                var data = await r.json();
                if (data.success) {
                    this.discount = data.discount;
                    this.discountInput = '';
                    this.total = data.discount.total;
                    this.showToast('Discount applied!');
                } else {
                    this.discountError = data.message || 'Invalid code';
                }
            } catch (e) {
                this.discountError = 'Something went wrong';
            } finally {
                this.discountLoading = false;
            }
        },

        async removeDiscount() {
            try {
                var r = await fetch('{{ route("cart.discount.remove") }}', {
                    method: 'DELETE',
                    headers: headers
                });
                var data = await r.json();
                if (data.success) {
                    this.discount = null;
                    this.recalc();
                    this.showToast('Discount removed');
                }
            } catch (e) {
                this.showToast('Something went wrong', 'error');
            }
        }
    };
}
</script>
@endpush
