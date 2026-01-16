<script setup lang="ts">
import { watch, ref } from 'vue';
import ProductCategoryForm from './ProductCategoryForm.vue';
import type { Language } from '../types';

defineProps<{
    languages: Language[];
}>();

const emit = defineEmits<{
    (e: 'close', created: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof ProductCategoryForm> | null>(null);

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
        title="Create Product Category"
        description="Add a new product category to organize your products."
    >
        <slot />

        <template #body>
            <ProductCategoryForm
                ref="formRef"
                :languages="languages"
                mode="create"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
