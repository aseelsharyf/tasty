<script setup lang="ts">
import { watch, ref, computed } from 'vue';
import ProductForm from './ProductForm.vue';
import type { Language } from '../types';

interface MediaItem {
    id: number;
    url: string;
    thumbnail_url?: string | null;
    title?: string;
}

interface ProductCategory {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
}

interface ProductWithTranslations {
    id?: number;
    uuid?: string;
    title?: string;
    title_translations?: Record<string, string>;
    description?: string;
    description_translations?: Record<string, string>;
    slug?: string;
    product_category_id?: number | null;
    featured_tag_id?: number | null;
    featured_media_id?: number | null;
    featured_media?: MediaItem | null;
    price?: number | null;
    currency?: string;
    compare_at_price?: number | null;
    affiliate_url?: string;
    affiliate_source?: string | null;
    is_active?: boolean;
    sku?: string | null;
    tag_ids?: number[];
}

const props = defineProps<{
    product: ProductWithTranslations | null;
    languages: Language[];
    categories: ProductCategory[];
    tags: Tag[];
}>();

const emit = defineEmits<{
    (e: 'close', updated: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof ProductForm> | null>(null);

const productData = computed(() => props.product);

function onSuccess() {
    emit('close', true);
    open.value = false;
}

function onCancel() {
    emit('close', false);
    open.value = false;
}

// When product changes, update the form
watch(() => props.product, (newProduct) => {
    if (newProduct && formRef.value) {
        // Form will update via its own watcher
    }
}, { deep: true });
</script>

<template>
    <USlideover
        v-model:open="open"
        title="Edit Product"
        description="Update product details."
    >
        <template #body>
            <ProductForm
                v-if="productData"
                ref="formRef"
                :product="productData"
                :languages="languages"
                :categories="categories"
                :tags="tags"
                mode="edit"
                @success="onSuccess"
                @cancel="onCancel"
            />
            <div v-else class="flex items-center justify-center py-12">
                <UIcon name="i-lucide-loader-2" class="size-8 animate-spin text-muted" />
            </div>
        </template>
    </USlideover>
</template>
