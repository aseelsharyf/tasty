<script setup lang="ts">
import { watch, ref } from 'vue';
import CategoryForm from './CategoryForm.vue';
import type { ParentOption } from '../types';

defineProps<{
    parentOptions?: ParentOption[];
}>();

const emit = defineEmits<{
    (e: 'close', created: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof CategoryForm> | null>(null);

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
        title="Create Category"
        description="Add a new category to organize your content."
    >
        <slot />

        <template #body>
            <CategoryForm
                ref="formRef"
                :parent-options="parentOptions"
                mode="create"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
