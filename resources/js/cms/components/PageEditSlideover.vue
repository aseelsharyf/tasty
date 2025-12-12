<script setup lang="ts">
import { watch, ref } from 'vue';
import PageForm from './PageForm.vue';
import type { Page, Author } from '../types';

interface PageWithDetails extends Page {
    content?: string | null;
    meta_title?: string | null;
    meta_description?: string | null;
}

const props = defineProps<{
    page: PageWithDetails | null;
    statuses: Record<string, string>;
    layouts: Record<string, string>;
    authors?: Author[];
}>();

const emit = defineEmits<{
    (e: 'close', updated: boolean): void;
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

// Update form when page changes
watch(() => props.page, (newPage) => {
    if (newPage && formRef.value?.form) {
        formRef.value.form.title = newPage.title || '';
        formRef.value.form.slug = newPage.slug || '';
        formRef.value.form.content = newPage.content || '';
        formRef.value.form.layout = newPage.layout || 'default';
        formRef.value.form.status = newPage.status || 'draft';
        formRef.value.form.is_blade = newPage.is_blade ?? true;
        formRef.value.form.author_id = newPage.author_id ?? null;
        formRef.value.form.meta_title = newPage.meta_title || '';
        formRef.value.form.meta_description = newPage.meta_description || '';
        formRef.value.form.published_at = newPage.published_at || '';
    }
}, { immediate: true, deep: true });
</script>

<template>
    <USlideover
        v-model:open="open"
        title="Edit Page"
        description="Update page details and content."
        :ui="{ width: 'sm:max-w-2xl' }"
    >
        <template #body>
            <PageForm
                v-if="page"
                ref="formRef"
                :page="page"
                :statuses="statuses"
                :layouts="layouts"
                :authors="authors"
                mode="edit"
                @success="onSuccess"
                @cancel="onCancel"
            />
        </template>
    </USlideover>
</template>
