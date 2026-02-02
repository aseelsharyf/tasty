<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { useCmsPath } from '../../composables/useCmsPath';
import type { ProductSearchResult } from '../../types';

const { cmsPath } = useCmsPath();

const props = defineProps<{
    open: boolean;
    excludedProductIds?: number[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    select: [product: ProductSearchResult];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const searchQuery = ref('');
const isLoading = ref(false);
const products = ref<ProductSearchResult[]>([]);
const error = ref<string | null>(null);

// Check if a product is excluded (already assigned)
function isProductExcluded(productId: number): boolean {
    return props.excludedProductIds?.includes(productId) ?? false;
}

// Debounced search
const debouncedSearch = useDebounceFn(async () => {
    await searchProducts();
}, 300);

watch(searchQuery, () => {
    debouncedSearch();
});

// Load initial products when modal opens
watch(() => props.open, async (open) => {
    if (open) {
        searchQuery.value = '';
        await searchProducts();
    }
});

async function searchProducts() {
    isLoading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (searchQuery.value) {
            params.set('query', searchQuery.value);
        }
        params.set('limit', '20');

        const response = await fetch(cmsPath(`/layouts/homepage/search-products?${params.toString()}`));

        if (!response.ok) {
            throw new Error('Failed to fetch products');
        }

        const data = await response.json();
        products.value = data.products;
    } catch (e) {
        error.value = 'Failed to load products. Please try again.';
        console.error('Product search error:', e);
    } finally {
        isLoading.value = false;
    }
}

function selectProduct(product: ProductSearchResult) {
    emit('select', product);
}

function getAvailabilityLabel(availability: string | undefined): string {
    switch (availability) {
        case 'in_stock': return 'In Stock';
        case 'out_of_stock': return 'Out of Stock';
        case 'pre_order': return 'Pre-Order';
        case 'discontinued': return 'Discontinued';
        default: return availability || '';
    }
}

function getAvailabilityColor(availability: string | undefined): 'success' | 'error' | 'warning' | 'neutral' {
    switch (availability) {
        case 'in_stock': return 'success';
        case 'out_of_stock': return 'error';
        case 'pre_order': return 'warning';
        case 'discontinued': return 'neutral';
        default: return 'neutral';
    }
}
</script>

<template>
    <UModal v-model:open="isOpen" :ui="{ width: 'max-w-2xl' }">
        <template #content>
            <UCard :ui="{ body: 'p-0' }">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-default">
                    <div>
                        <h3 class="text-lg font-semibold text-highlighted">Select Product</h3>
                        <p class="text-sm text-muted mt-1">Search and select a product to assign to this slot.</p>
                    </div>
                    <UButton
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="isOpen = false"
                    />
                </div>

                <!-- Search -->
                <div class="p-4 border-b border-default">
                    <UInput
                        v-model="searchQuery"
                        placeholder="Search products..."
                        icon="i-lucide-search"
                        class="w-full"
                        autofocus
                    />
                </div>

                <!-- Results -->
                <div class="max-h-[400px] overflow-y-auto">
                    <!-- Loading -->
                    <div v-if="isLoading" class="p-8 text-center">
                        <UIcon name="i-lucide-loader-2" class="size-8 text-muted animate-spin" />
                        <p class="text-sm text-muted mt-2">Loading products...</p>
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="p-8 text-center">
                        <UIcon name="i-lucide-alert-circle" class="size-8 text-error" />
                        <p class="text-sm text-error mt-2">{{ error }}</p>
                        <UButton
                            color="neutral"
                            variant="soft"
                            size="sm"
                            class="mt-4"
                            @click="searchProducts"
                        >
                            Try Again
                        </UButton>
                    </div>

                    <!-- Empty -->
                    <div v-else-if="products.length === 0" class="p-8 text-center">
                        <UIcon name="i-lucide-package-search" class="size-8 text-muted" />
                        <p class="text-sm text-muted mt-2">No products found</p>
                    </div>

                    <!-- Products List -->
                    <div v-else class="divide-y divide-default">
                        <div
                            v-for="product in products"
                            :key="product.id"
                            :class="[
                                'w-full flex items-center gap-4 p-4 text-left',
                                isProductExcluded(product.id)
                                    ? 'opacity-50 cursor-not-allowed bg-muted/20'
                                    : 'hover:bg-muted/50 transition-colors cursor-pointer'
                            ]"
                            @click="!isProductExcluded(product.id) && selectProduct(product)"
                        >
                            <img
                                v-if="product.image"
                                :src="product.image"
                                :alt="product.title"
                                class="size-16 rounded-lg object-contain bg-white border border-default shrink-0"
                            />
                            <div v-else class="size-16 rounded-lg bg-muted/50 flex items-center justify-center shrink-0">
                                <UIcon name="i-lucide-package" class="size-6 text-muted" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-highlighted truncate">{{ product.title }}</h4>
                                    <UBadge v-if="isProductExcluded(product.id)" color="warning" variant="subtle" size="xs">
                                        Already assigned
                                    </UBadge>
                                </div>
                                <p v-if="product.brand" class="text-sm text-muted">
                                    {{ product.brand }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span v-if="product.price" class="text-sm font-semibold text-primary">
                                        {{ product.price }}
                                    </span>
                                    <UBadge v-if="product.category" color="neutral" variant="subtle" size="xs">
                                        {{ product.category }}
                                    </UBadge>
                                    <UBadge
                                        v-if="product.availability"
                                        :color="getAvailabilityColor(product.availability)"
                                        variant="subtle"
                                        size="xs"
                                    >
                                        {{ getAvailabilityLabel(product.availability) }}
                                    </UBadge>
                                </div>
                            </div>
                            <UIcon
                                v-if="!isProductExcluded(product.id)"
                                name="i-lucide-chevron-right"
                                class="size-5 text-muted shrink-0"
                            />
                            <UIcon
                                v-else
                                name="i-lucide-ban"
                                class="size-5 text-warning shrink-0"
                            />
                        </div>
                    </div>
                </div>
            </UCard>
        </template>
    </UModal>
</template>
