<script setup lang="ts">
import { watch, ref } from 'vue';
import CategoryForm from './CategoryForm.vue';
import type { ParentOption, Language } from '../types';

interface CategoryWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    slug?: string;
    description?: string;
    description_translations?: Record<string, string>;
    parent_id?: number | null;
}

const props = defineProps<{
    category: CategoryWithTranslations | null;
    parentOptions?: ParentOption[];
    languages: Language[];
}>();

const emit = defineEmits<{
    (e: 'close', updated: boolean): void;
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

// Reset form when slideover opens with new category
watch([open, () => props.category], ([isOpen, category]) => {
    if (isOpen && formRef.value && category) {
        // Form will auto-update via its internal watch
    }
});
</script>

<template>
    <USlideover
        v-model:open="open"
        :title="`Edit Category: ${category?.name || ''}`"
        description="Edit category details and translations"
    >
        <slot />

        <template #body>
            <CategoryForm
                v-if="category"
                ref="formRef"
                :category="category"
                :parent-options="parentOptions"
                :languages="languages"
                mode="edit"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
