<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';
import { useCmsPath } from '../../composables/useCmsPath';

const toast = useToast();

interface OrderItem {
    id: number;
    uuid: string;
    product_title: string;
    variant_name?: string;
    product_type: string;
    sku?: string;
    price: number;
    quantity: number;
    total: number;
    product_image?: string;
    product_slug?: string;
}

interface Receipt {
    id: number;
    uuid: string;
    original_filename: string;
    notes?: string;
    verified_at?: string;
    verifier?: { name: string };
    created_at: string;
    preview_url?: string;
    is_image?: boolean;
}

interface StatusHistory {
    from_status: string;
    to_status: string;
    changed_by?: { name: string };
    notes?: string;
    created_at: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface OrderDetail {
    id: number;
    uuid: string;
    order_number: string;
    status: string;
    status_label: string;
    status_color: string;
    payment_status: string;
    payment_status_label: string;
    payment_status_color: string;
    payment_method?: string;
    payment_method_label?: string;
    metadata?: { bml_transaction_id?: string; bml_transaction_url?: string };
    subtotal: number;
    total: number;
    currency: string;
    discount_code?: string;
    discount_amount?: number;
    tax_amount?: number;
    tax_rate?: number;
    contact_person: string;
    contact_number: string;
    email: string;
    delivery_location?: { id: number; name: string };
    address: string;
    additional_info?: string;
    has_affiliate_products: boolean;
    accepted_at?: string;
    paid_at?: string;
    shipped_at?: string;
    completed_at?: string;
    cancelled_at?: string;
    cancellation_reason?: string;
    created_at: string;
    items: OrderItem[];
    receipts: Receipt[];
    status_history: StatusHistory[];
}

const props = defineProps<{
    order: OrderDetail;
    isEditable: boolean;
    statusOptions: StatusOption[];
}>();

const { can } = usePermission();
const { cmsPath } = useCmsPath();

const statusUpdateModalOpen = ref(false);
const selectedStatus = ref('');
const statusNotes = ref('');

const editItemModalOpen = ref(false);
const editingItem = ref<OrderItem | null>(null);
const editItemQty = ref(1);

const removeItemModalOpen = ref(false);
const removingItem = ref<OrderItem | null>(null);


function goBack() {
    router.visit(cmsPath('/orders'));
}

function formatDate(dateString?: string): string {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

function acceptOrder() {
    router.post(cmsPath(`/orders/${props.order.uuid}/accept`), {}, {
        onSuccess: () => toast.add({ title: 'Order accepted', color: 'success' }),
    });
}

function verifyPayment() {
    router.post(cmsPath(`/orders/${props.order.uuid}/verify-payment`), {}, {
        onSuccess: () => toast.add({ title: 'Payment verified', color: 'success' }),
    });
}

function openStatusUpdate() {
    selectedStatus.value = '';
    statusNotes.value = '';
    statusUpdateModalOpen.value = true;
}

function submitStatusUpdate() {
    router.put(cmsPath(`/orders/${props.order.uuid}/status`), {
        status: selectedStatus.value,
        notes: statusNotes.value || null,
    }, {
        onSuccess: () => {
            statusUpdateModalOpen.value = false;
            toast.add({ title: 'Status updated', color: 'success' });
        },
    });
}

function downloadReceipt(receipt: Receipt) {
    window.open(cmsPath(`/receipts/${receipt.uuid}`), '_blank');
}

function openEditItem(item: OrderItem) {
    editingItem.value = item;
    editItemQty.value = item.quantity;
    editItemModalOpen.value = true;
}

function submitEditItem() {
    if (!editingItem.value) return;
    router.put(cmsPath(`/orders/${props.order.uuid}/items/${editingItem.value.uuid}`), {
        quantity: editItemQty.value,
    }, {
        onSuccess: () => {
            editItemModalOpen.value = false;
            toast.add({ title: 'Item quantity updated', color: 'success' });
        },
    });
}

function openRemoveItem(item: OrderItem) {
    removingItem.value = item;
    removeItemModalOpen.value = true;
}

function confirmRemoveItem() {
    if (!removingItem.value) return;
    router.delete(cmsPath(`/orders/${props.order.uuid}/items/${removingItem.value.uuid}`), {
        onSuccess: () => {
            removeItemModalOpen.value = false;
            toast.add({ title: 'Item removed', color: 'success' });
        },
    });
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <DashboardLayout>
        <UDashboardPanel id="order-detail" :ui="{ body: 'p-0 gap-0' }">
            <template #header>
                <UDashboardNavbar>
                    <template #leading>
                        <div class="flex items-center gap-3">
                            <UDashboardSidebarCollapse />
                            <UButton color="neutral" variant="ghost" icon="i-lucide-arrow-left" size="sm" @click="goBack" />
                            <div class="h-4 w-px bg-default" />
                            <span class="text-sm font-mono text-muted">{{ order.order_number }}</span>
                        </div>
                    </template>
                    <template #right>
                        <div class="flex items-center gap-2">
                            <UButton v-if="order.status === 'pending' && can('orders.edit')" color="primary" size="sm" @click="acceptOrder">
                                Accept Order
                            </UButton>
                            <UButton v-if="order.payment_status === 'pending' && can('orders.edit')" color="success" variant="soft" size="sm" @click="verifyPayment">
                                Verify Payment
                            </UButton>
                            <UButton v-if="can('orders.edit')" color="neutral" variant="outline" size="sm" @click="openStatusUpdate">
                                Update Status
                            </UButton>
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="w-full max-w-3xl mx-auto px-6 py-8 space-y-6">
                    <!-- Status Header -->
                    <div class="flex items-center justify-between p-4 rounded-lg bg-elevated/50 border border-default">
                        <div>
                            <p class="text-xs text-muted mb-1">Order Status</p>
                            <UBadge :color="(order.status_color as any)" variant="subtle" size="lg">{{ order.status_label }}</UBadge>
                        </div>
                        <div>
                            <p class="text-xs text-muted mb-1">Payment</p>
                            <UBadge :color="(order.payment_status_color as any)" variant="subtle" size="lg">{{ order.payment_status_label }}</UBadge>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-muted mb-1">Total</p>
                            <p class="text-lg font-bold text-highlighted">{{ Number(order.total).toFixed(2) }} {{ order.currency }}</p>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="border border-default rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-highlighted mb-3">Customer & Delivery</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-muted text-xs">Contact Person</p>
                                <p class="font-medium">{{ order.contact_person }}</p>
                            </div>
                            <div>
                                <p class="text-muted text-xs">Phone</p>
                                <p>{{ order.contact_number }}</p>
                            </div>
                            <div v-if="order.email">
                                <p class="text-muted text-xs">Email</p>
                                <p>{{ order.email }}</p>
                            </div>
                            <div v-if="order.delivery_location">
                                <p class="text-muted text-xs">Location</p>
                                <p>{{ order.delivery_location.name }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-muted text-xs">Address</p>
                                <p>{{ order.address }}</p>
                            </div>
                            <div v-if="order.additional_info" class="col-span-2">
                                <p class="text-muted text-xs">Additional Info</p>
                                <p class="text-muted">{{ order.additional_info }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="border border-default rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-highlighted mb-3">Items</h3>
                        <div class="space-y-2">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3 text-sm py-2 border-b border-default last:border-0">
                                <a v-if="item.product_slug" :href="`/products/show/${item.product_slug}`" target="_blank" class="shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-elevated border border-default overflow-hidden flex items-center justify-center">
                                        <img v-if="item.product_image" :src="item.product_image" :alt="item.product_title" class="w-full h-full object-contain p-0.5">
                                        <UIcon v-else name="i-lucide-package" class="w-4 h-4 text-muted" />
                                    </div>
                                </a>
                                <div v-else class="shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-elevated border border-default overflow-hidden flex items-center justify-center">
                                        <img v-if="item.product_image" :src="item.product_image" :alt="item.product_title" class="w-full h-full object-contain p-0.5">
                                        <UIcon v-else name="i-lucide-package" class="w-4 h-4 text-muted" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <a v-if="item.product_slug" :href="`/products/show/${item.product_slug}`" target="_blank" class="font-medium hover:underline">{{ item.product_title }}</a>
                                    <span v-else class="font-medium">{{ item.product_title }}</span>
                                    <span v-if="item.variant_name" class="text-muted"> ({{ item.variant_name }})</span>
                                    <span v-if="item.sku" class="text-xs text-muted ml-2">{{ item.sku }}</span>
                                    <UBadge v-if="item.product_type === 'affiliate'" color="info" variant="subtle" size="xs" class="ml-2">Affiliate</UBadge>
                                </div>
                                <div class="flex items-center gap-2 shrink-0 ml-4">
                                    <div class="text-right">
                                        <span class="text-muted">{{ Number(item.price).toFixed(2) }} x {{ item.quantity }}</span>
                                        <span class="font-medium ml-3">{{ Number(item.total).toFixed(2) }}</span>
                                    </div>
                                    <div v-if="isEditable && can('orders.edit')" class="flex items-center gap-0.5 ml-2">
                                        <UButton color="neutral" variant="ghost" size="xs" icon="i-lucide-pencil" @click="openEditItem(item)" />
                                        <UButton color="error" variant="ghost" size="xs" icon="i-lucide-trash-2" @click="openRemoveItem(item)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 mt-2 border-t border-default space-y-1 text-sm">
                            <div v-if="order.discount_code || (order.tax_amount && Number(order.tax_amount) > 0)" class="flex justify-between text-muted">
                                <span>Subtotal</span>
                                <span>{{ Number(order.subtotal).toFixed(2) }} {{ order.currency }}</span>
                            </div>
                            <div v-if="order.discount_code" class="flex justify-between text-green-600 dark:text-green-400">
                                <span>Discount ({{ order.discount_code }})</span>
                                <span>-{{ Number(order.discount_amount).toFixed(2) }}</span>
                            </div>
                            <div v-if="order.tax_amount && Number(order.tax_amount) > 0" class="flex justify-between text-muted">
                                <span>Tax ({{ Number(order.tax_rate).toFixed(0) }}%)</span>
                                <span>{{ Number(order.tax_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-semibold">
                                <span>Total</span>
                                <span>{{ Number(order.total).toFixed(2) }} {{ order.currency }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="border border-default rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-highlighted mb-3">Payment</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-muted text-xs">Method</p>
                                <p class="font-medium">{{ order.payment_method_label || '—' }}</p>
                            </div>
                            <div>
                                <p class="text-muted text-xs">Status</p>
                                <UBadge :color="(order.payment_status_color as any)" variant="subtle" size="sm">{{ order.payment_status_label }}</UBadge>
                            </div>
                            <div v-if="order.paid_at">
                                <p class="text-muted text-xs">Paid At</p>
                                <p>{{ formatDate(order.paid_at) }}</p>
                            </div>
                            <div v-if="order.discount_code">
                                <p class="text-muted text-xs">Discount</p>
                                <p>
                                    <span class="font-mono text-xs bg-elevated px-1.5 py-0.5 rounded">{{ order.discount_code }}</span>
                                    <span class="text-muted ml-1">-{{ Number(order.discount_amount).toFixed(2) }} {{ order.currency }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Gateway Transaction Details -->
                        <div v-if="order.payment_method === 'bml_gateway' && order.metadata?.bml_transaction_id" class="mt-4 pt-3 border-t border-default">
                            <p class="text-xs text-muted mb-2">Transaction Details</p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-muted text-xs">Transaction ID</p>
                                    <p class="font-mono text-xs">{{ order.metadata.bml_transaction_id }}</p>
                                </div>
                                <div>
                                    <p class="text-muted text-xs">Provider</p>
                                    <p>BML Connect</p>
                                </div>
                            </div>
                        </div>

                        <!-- Receipts -->
                        <div v-if="order.receipts.length > 0" class="mt-4 pt-3 border-t border-default">
                            <p class="text-xs text-muted mb-2">Receipts</p>
                            <div class="space-y-3">
                                <div v-for="receipt in order.receipts" :key="receipt.id" class="text-sm py-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ receipt.original_filename }}</span>
                                            <span class="text-xs text-muted">{{ formatDate(receipt.created_at) }}</span>
                                            <UBadge v-if="receipt.verified_at" color="success" variant="subtle" size="xs">Verified</UBadge>
                                        </div>
                                        <UButton color="neutral" variant="ghost" size="xs" icon="i-lucide-download" @click="downloadReceipt(receipt)" />
                                    </div>
                                    <p v-if="receipt.notes" class="text-xs text-muted mb-2">{{ receipt.notes }}</p>
                                    <div v-if="receipt.is_image && receipt.preview_url" class="rounded-lg border border-default overflow-hidden">
                                        <img :src="receipt.preview_url" :alt="receipt.original_filename" class="w-full" />
                                    </div>
                                    <div v-else-if="receipt.preview_url" class="rounded-lg border border-default bg-elevated p-4 flex items-center gap-2">
                                        <UIcon name="i-lucide-file-text" class="w-5 h-5 text-muted" />
                                        <span class="text-muted text-xs">PDF — click download to view</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status History -->
                    <div v-if="order.status_history.length > 0" class="border border-default rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-highlighted mb-3">Status History</h3>
                        <div class="space-y-3">
                            <div v-for="(history, idx) in order.status_history" :key="idx" class="flex items-start gap-3 text-sm">
                                <div class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></div>
                                <div>
                                    <p class="font-medium capitalize">{{ history.to_status.replace(/_/g, ' ') }}</p>
                                    <p class="text-xs text-muted">
                                        {{ formatDate(history.created_at) }}
                                        <span v-if="history.changed_by"> by {{ history.changed_by.name }}</span>
                                    </p>
                                    <p v-if="history.notes" class="text-muted mt-0.5">{{ history.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Status Update Modal -->
        <UModal v-model:open="statusUpdateModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Update Order Status</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium mb-2 block">New Status</label>
                            <USelectMenu
                                v-model="selectedStatus"
                                :items="statusOptions"
                                value-key="value"
                                placeholder="Select status..."
                                class="w-full"
                            />
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-2 block">Notes (optional)</label>
                            <UTextarea v-model="statusNotes" placeholder="Add a note..." :rows="3" class="w-full" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <UButton color="neutral" variant="outline" @click="statusUpdateModalOpen = false">Cancel</UButton>
                        <UButton :disabled="!selectedStatus" @click="submitStatusUpdate">Update Status</UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Remove Item Confirmation Modal -->
        <UModal v-model:open="removeItemModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-2">Remove Item</h3>
                    <p class="text-sm text-muted mb-6" v-if="removingItem">Are you sure you want to remove <strong>{{ removingItem.product_title }}</strong><span v-if="removingItem.variant_name"> ({{ removingItem.variant_name }})</span> from this order?</p>
                    <div class="flex justify-end gap-3">
                        <UButton color="neutral" variant="outline" @click="removeItemModalOpen = false">Cancel</UButton>
                        <UButton color="error" @click="confirmRemoveItem">Remove</UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Item Quantity Modal -->
        <UModal v-model:open="editItemModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Edit Quantity</h3>
                    <p class="text-sm text-muted mb-4" v-if="editingItem">{{ editingItem.product_title }}<span v-if="editingItem.variant_name"> ({{ editingItem.variant_name }})</span></p>
                    <div>
                        <label class="text-sm font-medium mb-2 block">Quantity</label>
                        <UInput v-model.number="editItemQty" type="number" :min="1" :max="99" class="w-full" />
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <UButton color="neutral" variant="outline" @click="editItemModalOpen = false">Cancel</UButton>
                        <UButton :disabled="editItemQty < 1" @click="submitEditItem">Save</UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
