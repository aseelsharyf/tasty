<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useSettingsNav } from '../../composables/useSettingsNav';
import { useCmsPath } from '../../composables/useCmsPath';

const toast = useToast();
const { mainNav: settingsNav } = useSettingsNav();
const { cmsPath } = useCmsPath();

interface BankAccount {
    bank_name: string;
    account_name: string;
    account_number: string;
    currency: string;
}

interface PaymentMethod {
    key: string;
    name: string;
    type: 'gateway' | 'bank_transfer' | 'online';
    is_active: boolean;
}

interface TaxConfig {
    enabled: boolean;
    rate: number;
    label: string;
    inclusive: boolean;
}

const props = defineProps<{
    bankAccounts: BankAccount[];
    paymentMethods: PaymentMethod[];
    taxConfig: TaxConfig;
}>();

const form = useForm({
    bank_accounts: [...props.bankAccounts] as BankAccount[],
    payment_methods: [...props.paymentMethods] as PaymentMethod[],
    tax: { ...props.taxConfig } as TaxConfig,
});

function addBankAccount() {
    form.bank_accounts.push({
        bank_name: '',
        account_name: '',
        account_number: '',
        currency: 'MVR',
    });
}

function removeBankAccount(index: number) {
    form.bank_accounts.splice(index, 1);
}

function submit() {
    form.put(cmsPath('/settings/shop'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ title: 'Shop settings updated', color: 'success' });
        },
    });
}
</script>

<template>
    <Head title="Shop Settings" />

    <DashboardLayout>
        <UDashboardPanel id="shop-settings">
            <template #header>
                <UDashboardNavbar title="Shop Settings">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <UButton :loading="form.processing" @click="submit">
                            Save Changes
                        </UButton>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="w-full max-w-2xl mx-auto px-6 py-8 space-y-8">
                    <!-- Tax Configuration -->
                    <div>
                        <h3 class="text-sm font-semibold text-highlighted mb-4">Tax</h3>

                        <div class="p-4 rounded-lg border border-default bg-elevated/30 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium">Enable Tax</p>
                                    <p class="text-xs text-muted">Apply tax to all orders</p>
                                </div>
                                <USwitch v-model="form.tax.enabled" />
                            </div>

                            <div v-if="form.tax.enabled" class="grid grid-cols-2 gap-3 pt-3 border-t border-default">
                                <div>
                                    <label class="text-xs font-medium mb-1 block">Tax Label</label>
                                    <UInput v-model="form.tax.label" placeholder="e.g., GST" size="sm" />
                                </div>
                                <div>
                                    <label class="text-xs font-medium mb-1 block">Rate (%)</label>
                                    <UInput v-model.number="form.tax.rate" type="number" placeholder="0" min="0" max="100" step="0.01" size="sm" />
                                </div>
                                <div class="col-span-2 flex items-center justify-between pt-2">
                                    <div>
                                        <p class="text-sm font-medium">Tax Inclusive</p>
                                        <p class="text-xs text-muted">Prices already include tax</p>
                                    </div>
                                    <USwitch v-model="form.tax.inclusive" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="border-t border-default pt-6">
                        <h3 class="text-sm font-semibold text-highlighted mb-4">Payment Methods</h3>

                        <div class="space-y-3">
                            <div
                                v-for="method in form.payment_methods"
                                :key="method.key"
                                class="flex items-center justify-between p-3 rounded-lg bg-elevated/50 border border-default"
                            >
                                <div>
                                    <p class="text-sm font-medium">{{ method.name }}</p>
                                    <p class="text-xs text-muted capitalize">{{ method.type.replace('_', ' ') }}</p>
                                </div>
                                <USwitch v-model="method.is_active" />
                            </div>
                        </div>
                    </div>

                    <!-- Bank Accounts -->
                    <div class="border-t border-default pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-highlighted">Bank Accounts</h3>
                            <UButton
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                icon="i-lucide-plus"
                                @click="addBankAccount"
                            >
                                Add Account
                            </UButton>
                        </div>

                        <div v-if="form.bank_accounts.length === 0" class="text-sm text-muted text-center py-6 border border-dashed border-default rounded-lg">
                            No bank accounts configured.
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="(account, index) in form.bank_accounts"
                                :key="index"
                                class="p-4 rounded-lg border border-default bg-elevated/30"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-medium text-muted">Account {{ index + 1 }}</span>
                                    <UButton
                                        color="error"
                                        variant="ghost"
                                        size="xs"
                                        icon="i-lucide-trash-2"
                                        @click="removeBankAccount(index)"
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-medium mb-1 block">Bank Name</label>
                                        <UInput v-model="account.bank_name" placeholder="e.g., BML" size="sm" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium mb-1 block">Account Name</label>
                                        <UInput v-model="account.account_name" placeholder="Account holder name" size="sm" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium mb-1 block">Account Number</label>
                                        <UInput v-model="account.account_number" placeholder="7730000000000" size="sm" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium mb-1 block">Currency</label>
                                        <USelectMenu
                                            v-model="account.currency"
                                            :items="[
                                                { value: 'MVR', label: 'MVR' },
                                                { value: 'USD', label: 'USD' },
                                            ]"
                                            value-key="value"
                                            size="sm"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="flex justify-end pt-6 border-t border-default">
                        <UButton :loading="form.processing" @click="submit">
                            Save Changes
                        </UButton>
                    </div>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
