<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import type { Page, Author } from '../types';
import { useCmsPath } from '../composables/useCmsPath';

const props = withDefaults(defineProps<{
    page?: Page & {
        content?: string | null;
        meta_title?: string | null;
        meta_description?: string | null;
    };
    statuses: Record<string, string>;
    layouts: Record<string, string>;
    authors?: Author[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const { cmsPath } = useCmsPath();

const isEditing = computed(() => props.mode === 'edit' && props.page?.uuid);
const activeTab = ref<'content' | 'seo'>('content');

const form = useForm({
    title: props.page?.title || '',
    slug: props.page?.slug || '',
    content: props.page?.content || '',
    layout: props.page?.layout || 'default',
    status: props.page?.status || 'draft',
    is_blade: props.page?.is_blade ?? true,
    author_id: props.page?.author_id ?? null,
    meta_title: props.page?.meta_title || '',
    meta_description: props.page?.meta_description || '',
    published_at: props.page?.published_at || '',
});

// Auto-generate slug from title (only in create mode)
watch(() => form.title, (newTitle) => {
    if (props.mode === 'create' && newTitle) {
        const currentSlug = form.slug;
        const previousTitle = form.title.slice(0, -1) || '';
        if (!currentSlug || currentSlug === slugify(previousTitle)) {
            form.slug = slugify(newTitle);
        }
    }
});

function slugify(text: string): string {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function onSubmit() {
    if (isEditing.value && props.page?.uuid) {
        form.put(cmsPath(`/pages/${props.page.uuid}`), {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(cmsPath('/pages'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                emit('success');
            },
        });
    }
}

function onCancel() {
    reset();
    emit('cancel');
}

function reset() {
    form.title = '';
    form.slug = '';
    form.content = '';
    form.layout = 'default';
    form.status = 'draft';
    form.is_blade = true;
    form.author_id = null;
    form.meta_title = '';
    form.meta_description = '';
    form.published_at = '';
    form.clearErrors();
    activeTab.value = 'content';
}

const statusOptions = computed(() => {
    return Object.entries(props.statuses).map(([value, label]) => ({
        label,
        value,
    }));
});

const layoutOptions = computed(() => {
    return Object.entries(props.layouts).map(([value, label]) => ({
        label,
        value,
    }));
});

const authorOptions = computed(() => {
    return [
        { label: 'None', value: null },
        ...(props.authors || []).map(a => ({
            label: a.name,
            value: a.id,
        })),
    ];
});

// Update form when page changes (for edit mode)
watch(() => props.page, (newPage) => {
    if (newPage) {
        form.title = newPage.title || '';
        form.slug = newPage.slug || '';
        form.content = newPage.content || '';
        form.layout = newPage.layout || 'default';
        form.status = newPage.status || 'draft';
        form.is_blade = newPage.is_blade ?? true;
        form.author_id = newPage.author_id ?? null;
        form.meta_title = newPage.meta_title || '';
        form.meta_description = newPage.meta_description || '';
        form.published_at = newPage.published_at || '';
    }
}, { immediate: true, deep: true });

// Expose reset method for parent components
defineExpose({ reset, form });
</script>

<template>
    <UForm
        :state="form"
        class="flex flex-col h-full"
        @submit="onSubmit"
    >
        <!-- Tabs -->
        <div class="border-b border-default shrink-0">
            <nav class="flex gap-1 -mb-px">
                <button
                    type="button"
                    :class="[
                        'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
                        activeTab === 'content'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                    ]"
                    @click="activeTab = 'content'"
                >
                    Content
                </button>
                <button
                    type="button"
                    :class="[
                        'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
                        activeTab === 'seo'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                    ]"
                    @click="activeTab = 'seo'"
                >
                    SEO
                </button>
            </nav>
        </div>

        <div class="flex-1 overflow-y-auto py-4 space-y-4">
            <!-- Content Tab -->
            <template v-if="activeTab === 'content'">
                <UFormField
                    label="Title"
                    name="title"
                    :error="form.errors.title"
                    required
                >
                    <UInput
                        v-model="form.title"
                        placeholder="Page title"
                        class="w-full"
                        :disabled="form.processing"
                    />
                </UFormField>

                <UFormField
                    label="Slug"
                    name="slug"
                    :error="form.errors.slug"
                    :help="mode === 'create' ? 'URL-friendly version (auto-generated)' : 'URL-friendly version'"
                    required
                >
                    <UInput
                        v-model="form.slug"
                        placeholder="page-slug"
                        class="w-full"
                        :disabled="form.processing"
                    />
                </UFormField>

                <div class="grid grid-cols-2 gap-4">
                    <UFormField
                        label="Status"
                        name="status"
                        :error="form.errors.status"
                        required
                    >
                        <USelectMenu
                            v-model="form.status"
                            :items="statusOptions"
                            placeholder="Select status..."
                            value-key="value"
                            class="w-full"
                            :disabled="form.processing"
                        />
                    </UFormField>

                    <UFormField
                        label="Layout"
                        name="layout"
                        :error="form.errors.layout"
                        required
                    >
                        <USelectMenu
                            v-model="form.layout"
                            :items="layoutOptions"
                            placeholder="Select layout..."
                            value-key="value"
                            class="w-full"
                            :disabled="form.processing"
                        />
                    </UFormField>
                </div>

                <UFormField
                    label="Content"
                    name="content"
                    :error="form.errors.content"
                    help="Supports Blade syntax when 'Use Blade' is enabled"
                >
                    <UTextarea
                        v-model="form.content"
                        placeholder="Page content (HTML/Blade)"
                        :rows="12"
                        class="w-full font-mono text-sm"
                        :disabled="form.processing"
                    />
                </UFormField>

                <div class="flex items-center gap-4">
                    <UFormField
                        name="is_blade"
                        :error="form.errors.is_blade"
                    >
                        <UCheckbox
                            v-model="form.is_blade"
                            label="Use Blade rendering"
                            :disabled="form.processing"
                        />
                    </UFormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <UFormField
                        label="Author"
                        name="author_id"
                        :error="form.errors.author_id"
                    >
                        <USelectMenu
                            v-model="form.author_id"
                            :items="authorOptions"
                            placeholder="Select author..."
                            value-key="value"
                            class="w-full"
                            :disabled="form.processing"
                        />
                    </UFormField>

                    <UFormField
                        v-if="form.status === 'published'"
                        label="Publish Date"
                        name="published_at"
                        :error="form.errors.published_at"
                    >
                        <UInput
                            v-model="form.published_at"
                            type="datetime-local"
                            class="w-full"
                            :disabled="form.processing"
                        />
                    </UFormField>
                </div>
            </template>

            <!-- SEO Tab -->
            <template v-if="activeTab === 'seo'">
                <UFormField
                    label="Meta Title"
                    name="meta_title"
                    :error="form.errors.meta_title"
                    help="Title for search engines. Leave empty to use page title."
                >
                    <UInput
                        v-model="form.meta_title"
                        placeholder="SEO title"
                        class="w-full"
                        :disabled="form.processing"
                    />
                </UFormField>

                <UFormField
                    label="Meta Description"
                    name="meta_description"
                    :error="form.errors.meta_description"
                    help="Brief description for search results."
                >
                    <UTextarea
                        v-model="form.meta_description"
                        placeholder="SEO description"
                        :rows="3"
                        class="w-full"
                        :disabled="form.processing"
                    />
                    <div class="text-xs text-muted mt-1">
                        {{ form.meta_description?.length || 0 }} / 500 characters
                    </div>
                </UFormField>
            </template>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-default shrink-0">
            <UButton
                color="neutral"
                variant="ghost"
                :disabled="form.processing"
                @click="onCancel"
            >
                Cancel
            </UButton>
            <UButton
                type="submit"
                :loading="form.processing"
            >
                {{ isEditing ? 'Save Changes' : 'Create Page' }}
            </UButton>
        </div>
    </UForm>
</template>
