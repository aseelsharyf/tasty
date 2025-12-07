<script setup lang="ts">
import { watch, ref } from 'vue';
import MenuForm from './MenuForm.vue';
import type { Language } from '../types';

defineProps<{
    languages: Language[];
}>();

const emit = defineEmits<{
    (e: 'close', created: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof MenuForm> | null>(null);

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
        title="Create Menu"
        description="Create a new menu to organize your navigation."
    >
        <slot />

        <template #body>
            <MenuForm
                ref="formRef"
                :languages="languages"
                mode="create"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
