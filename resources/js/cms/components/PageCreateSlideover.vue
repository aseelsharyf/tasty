<script setup lang="ts">
import { watch, ref } from 'vue';
import PageForm from './PageForm.vue';
import type { Author } from '../types';

defineProps<{
    statuses: Record<string, string>;
    layouts: Record<string, string>;
    authors?: Author[];
}>();

const emit = defineEmits<{
    (e: 'close', created: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof PageForm> | null>(null);

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
        title="Create Page"
        description="Add a new page to your website."
        :ui="{ width: 'sm:max-w-2xl' }"
    >
        <slot />

        <template #body>
            <PageForm
                ref="formRef"
                :statuses="statuses"
                :layouts="layouts"
                :authors="authors"
                mode="create"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
