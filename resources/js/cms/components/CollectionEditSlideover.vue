<script setup lang="ts">
import { watch, ref } from 'vue';
import CollectionForm from './CollectionForm.vue';
import type { Language, Collection } from '../types';

const props = defineProps<{
    collection: Collection | null;
    languages: Language[];
}>();

const emit = defineEmits<{
    (e: 'close', updated: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof CollectionForm> | null>(null);

function onSuccess() {
    open.value = false;
    emit('close', true);
}

function onCancel() {
    open.value = false;
    emit('close', false);
}

// Reset form when slideover opens with new collection
watch([open, () => props.collection], ([isOpen, collection]) => {
    if (isOpen && formRef.value && collection) {
        // Form will auto-update via its internal watch
    }
});
</script>

<template>
    <USlideover
        v-model:open="open"
        :title="`Edit Collection: ${collection?.name || ''}`"
        description="Edit collection details and manage posts"
    >
        <slot />

        <template #body>
            <CollectionForm
                v-if="collection"
                ref="formRef"
                :collection="collection"
                :languages="languages"
                mode="edit"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
