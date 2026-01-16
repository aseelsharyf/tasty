<script setup lang="ts">
import { watch, ref } from 'vue';
import ProductForm from './ProductForm.vue';
import type { Language } from '../types';

interface ProductCategory {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
}

defineProps<{
    languages: Language[];
    categories: ProductCategory[];
    tags: Tag[];
}>();

const emit = defineEmits<{
    (e: 'close', created: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof ProductForm> | null>(null);

function onSuccess() {
    open.value = false;
    emit('close', true);
}

function onCancel() {
    open.value = false;
    emit('close', false);
}

// Reset form when slideover opens
watch(open, (isOpen) => {
    if (isOpen && formRef.value) {
        formRef.value.reset();
    }
});
</script>

<template>
    <USlideover
        v-model:open="open"
        title="Create Product"
        description="Add a new product to your store."
    >
        <slot />

        <template #body>
            <ProductForm
                ref="formRef"
                :languages="languages"
                :categories="categories"
                :tags="tags"
                mode="create"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
