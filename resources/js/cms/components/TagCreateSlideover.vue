<script setup lang="ts">
import { watch, ref } from 'vue';
import TagForm from './TagForm.vue';

const emit = defineEmits<{
    (e: 'close', created: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof TagForm> | null>(null);

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
        title="Create Tag"
        description="Add a new tag to label your content."
    >
        <slot />

        <template #body>
            <TagForm
                ref="formRef"
                mode="create"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
