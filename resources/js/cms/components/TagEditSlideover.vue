<script setup lang="ts">
import { watch, ref } from 'vue';
import TagForm from './TagForm.vue';
import type { Language } from '../types';

interface TagWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    slug?: string;
    posts_count?: number;
}

const props = defineProps<{
    tag: TagWithTranslations | null;
    languages: Language[];
}>();

const emit = defineEmits<{
    (e: 'close', updated: boolean): void;
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

// Reset form when slideover opens with new tag
watch([open, () => props.tag], ([isOpen, tag]) => {
    if (isOpen && formRef.value && tag) {
        // Form will auto-update via its internal watch
    }
});
</script>

<template>
    <USlideover
        v-model:open="open"
        :title="`Edit Tag: ${tag?.name || ''}`"
        description="Edit tag details and translations"
    >
        <slot />

        <template #body>
            <TagForm
                v-if="tag"
                ref="formRef"
                :tag="tag"
                :languages="languages"
                mode="edit"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
