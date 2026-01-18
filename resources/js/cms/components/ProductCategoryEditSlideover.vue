<script setup lang="ts">
import { watch, ref, computed } from 'vue';
import ProductCategoryForm from './ProductCategoryForm.vue';
import type { Language } from '../types';

interface MediaItem {
    id: number;
    url: string;
    title?: string;
}

interface ProductCategoryWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    description?: string;
    description_translations?: Record<string, string>;
    slug?: string;
    parent_id?: number | null;
    featured_media_id?: number | null;
    featured_media?: MediaItem | null;
    is_active?: boolean;
    order?: number;
}

interface ParentCategory {
    id: number;
    name: string;
}

const props = defineProps<{
    category: ProductCategoryWithTranslations | null;
    languages: Language[];
    parentCategories?: ParentCategory[];
}>();

const emit = defineEmits<{
    (e: 'close', updated: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof ProductCategoryForm> | null>(null);

const categoryData = computed(() => props.category);

function onSuccess() {
    emit('close', true);
    open.value = false;
}

function onCancel() {
    emit('close', false);
    open.value = false;
}

// When category changes, update the form
watch(() => props.category, (newCategory) => {
    if (newCategory && formRef.value) {
        // Form will update via its own watcher
    }
}, { deep: true });
</script>

<template>
    <USlideover
        v-model:open="open"
        title="Edit Product Category"
        description="Update category details."
    >
        <template #body>
            <ProductCategoryForm
                v-if="categoryData"
                ref="formRef"
                :category="categoryData"
                :languages="languages"
                :parent-categories="parentCategories"
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
