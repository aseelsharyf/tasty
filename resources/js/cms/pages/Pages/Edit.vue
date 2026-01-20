<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';

const toast = useToast();
import CodeEditor from '../../components/CodeEditor.vue';
import MarkdownEditor from '../../components/MarkdownEditor.vue';
import DhivehiInput from '../../components/DhivehiInput.vue';
import type { Page, Author, Language } from '../../types';

interface PageData extends Page {
    content?: string | null;
    meta_title?: string | null;
    meta_description?: string | null;
    editor_mode?: 'code' | 'markdown';
}

const props = defineProps<{
    page: PageData;
    statuses: Record<string, string>;
    layouts: Record<string, string>;
    authors: Author[];
    languages: Language[];
    currentLanguage: Language;
}>();

const activeTab = ref<'content' | 'settings' | 'preview'>('content');
const deleteModalOpen = ref(false);
const previewKey = ref(0);
const previewSize = ref<'desktop' | 'tablet' | 'mobile'>('desktop');
const editorMode = ref<'code' | 'markdown'>(props.page.editor_mode || 'code');

const previewSizes = {
    desktop: { width: '100%', label: 'Desktop', icon: 'i-lucide-monitor' },
    tablet: { width: '768px', label: 'Tablet', icon: 'i-lucide-tablet' },
    mobile: { width: '375px', label: 'Mobile', icon: 'i-lucide-smartphone' },
};

const form = useForm({
    title: props.page.title,
    slug: props.page.slug,
    content: props.page.content || '',
    layout: props.page.layout,
    status: props.page.status,
    is_blade: props.page.is_blade,
    author_id: props.page.author_id,
    meta_title: props.page.meta_title || '',
    meta_description: props.page.meta_description || '',
    published_at: props.page.published_at || '',
    editor_mode: props.page.editor_mode || 'code',
});

// Sync editor mode with form
watch(editorMode, (newMode) => {
    form.editor_mode = newMode;
    // When switching to markdown, disable blade rendering
    if (newMode === 'markdown') {
        form.is_blade = false;
    }
});

// Watch for form errors and show toast
watch(() => form.errors, (errors) => {
    const errorKeys = Object.keys(errors);
    if (errorKeys.length > 0) {
        const firstError = errors[errorKeys[0] as keyof typeof errors];
        toast.add({
            title: 'Validation Error',
            description: firstError || 'Please check the form for errors',
            color: 'error',
        });
    }
}, { deep: true });

function onSubmit() {
    form.put(`/cms/pages/${props.currentLanguage.code}/${props.page.uuid}`, {
        preserveScroll: true,
        onSuccess: () => {
            // Refresh preview iframe after save
            previewKey.value++;
            toast.add({
                title: 'Success',
                description: 'Page updated successfully',
                color: 'success',
            });
        },
    });
}

function goBack() {
    router.visit(`/cms/pages/${props.currentLanguage.code}`);
}

function viewPage() {
    window.open(`/${props.page.slug}`, '_blank');
}

function deletePage() {
    router.delete(`/cms/pages/${props.currentLanguage.code}/${props.page.uuid}`);
}

function refreshPreview() {
    previewKey.value++;
}

const previewUrl = computed(() => {
    return `/${props.page.slug}`;
});

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
        ...props.authors.map(a => ({
            label: a.name,
            value: a.id,
        })),
    ];
});

const isRtl = computed(() => props.currentLanguage.direction === 'rtl');
const isDhivehi = computed(() => props.currentLanguage.code === 'dv');

const statusBadgeColor = computed(() => {
    return props.page.status === 'published' ? 'success' : 'neutral';
});
</script>

<template>
    <Head :title="`Edit: ${page.title}`" />

    <DashboardLayout>
        <div class="flex flex-col h-[calc(100vh-1px)] w-full">
            <!-- Header -->
            <header class="flex items-center justify-between gap-4 px-6 py-3 border-b border-default bg-default shrink-0">
                <div class="flex items-center gap-3">
                    <UButton
                        icon="i-lucide-arrow-left"
                        color="neutral"
                        variant="ghost"
                        @click="goBack"
                    />
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-semibold text-highlighted">{{ page.title }}</h1>
                            <UBadge :color="statusBadgeColor" variant="subtle">
                                {{ statuses[page.status] }}
                            </UBadge>
                            <UBadge color="neutral" variant="outline" size="xs">
                                {{ page.language?.native_name || page.language_code }}
                            </UBadge>
                        </div>
                        <p class="text-sm text-muted">/{{ page.slug }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <UButton
                        icon="i-lucide-external-link"
                        color="neutral"
                        variant="ghost"
                        @click="viewPage"
                    >
                        View
                    </UButton>
                    <UButton
                        icon="i-lucide-trash"
                        color="error"
                        variant="ghost"
                        @click="deleteModalOpen = true"
                    />
                    <UButton
                        :loading="form.processing"
                        :disabled="!form.isDirty"
                        @click="onSubmit"
                    >
                        Save Changes
                    </UButton>
                </div>
            </header>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-h-0 w-full">
                <!-- Tabs -->
                <div class="border-b border-default px-6 shrink-0">
                    <nav class="flex gap-1 -mb-px">
                        <button
                            type="button"
                            :class="[
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
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
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'preview'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                            ]"
                            @click="activeTab = 'preview'"
                        >
                            Preview
                        </button>
                        <button
                            type="button"
                            :class="[
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'settings'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                            ]"
                            @click="activeTab = 'settings'"
                        >
                            Settings
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden w-full min-w-0">
                    <!-- Content Tab -->
                    <div v-if="activeTab === 'content'" class="p-6 space-y-6 w-full max-w-full min-w-0">
                        <div class="flex items-end gap-4">
                            <UFormField
                                label="Title"
                                name="title"
                                :error="form.errors.title"
                                required
                                class="flex-1"
                            >
                                <DhivehiInput
                                    v-if="isDhivehi"
                                    v-model="form.title"
                                    placeholder="ސޮފްޙާގެ ސުރުޚީ"
                                    size="xl"
                                    :disabled="form.processing"
                                    :default-enabled="true"
                                    :show-toggle="false"
                                    class="w-full"
                                />
                                <UInput
                                    v-else
                                    v-model="form.title"
                                    placeholder="Page title"
                                    size="xl"
                                    :dir="isRtl ? 'rtl' : 'ltr'"
                                    :disabled="form.processing"
                                    class="w-full"
                                />
                            </UFormField>

                            <div class="pb-1">
                                <UBadge color="neutral" variant="outline" size="lg">
                                    <UIcon name="i-lucide-globe" class="size-3.5 mr-1" />
                                    {{ currentLanguage.native_name }}
                                </UBadge>
                            </div>
                        </div>

                        <!-- Editor Mode Toggle -->
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-highlighted">Content</div>
                            <div class="flex items-center gap-2 p-1 rounded-lg bg-elevated border border-default">
                                <UButton
                                    icon="i-lucide-file-text"
                                    :color="editorMode === 'markdown' ? 'primary' : 'neutral'"
                                    :variant="editorMode === 'markdown' ? 'soft' : 'ghost'"
                                    size="xs"
                                    @click="editorMode = 'markdown'"
                                >
                                    Markdown
                                </UButton>
                                <UButton
                                    icon="i-lucide-code"
                                    :color="editorMode === 'code' ? 'primary' : 'neutral'"
                                    :variant="editorMode === 'code' ? 'soft' : 'ghost'"
                                    size="xs"
                                    @click="editorMode = 'code'"
                                >
                                    Code
                                </UButton>
                            </div>
                        </div>

                        <UFormField
                            name="content"
                            :error="form.errors.content"
                            :help="editorMode === 'markdown' ? 'Write your page content using Markdown syntax' : 'Write your page content using HTML or Blade syntax'"
                            class="w-full max-w-full overflow-hidden"
                        >
                            <MarkdownEditor
                                v-if="editorMode === 'markdown'"
                                v-model="form.content"
                                placeholder="Write your page content here..."
                                height="500px"
                                :disabled="form.processing"
                            />
                            <CodeEditor
                                v-else
                                v-model="form.content"
                                language="blade"
                                placeholder="<!-- Enter your page content here -->"
                                height="500px"
                                :disabled="form.processing"
                            />
                        </UFormField>

                        <div v-if="editorMode === 'code'" class="flex items-center gap-4">
                            <UCheckbox
                                v-model="form.is_blade"
                                label="Enable Blade rendering"
                                :disabled="form.processing"
                            />
                            <span class="text-sm text-muted" v-pre>
                                When enabled, Blade directives like @if, @foreach, {{ $variable }} will be processed
                            </span>
                        </div>

                        <div v-if="editorMode === 'markdown'" class="flex items-center gap-2 p-3 rounded-lg bg-info/10 border border-info/20">
                            <UIcon name="i-lucide-info" class="size-4 text-info shrink-0" />
                            <span class="text-sm text-muted">
                                Markdown content will be converted to HTML when the page is rendered. This is ideal for simple pages without complex layouts.
                            </span>
                        </div>
                    </div>

                    <!-- Preview Tab -->
                    <div v-if="activeTab === 'preview'" class="h-full flex flex-col">
                        <div class="flex items-center justify-between px-6 py-3 border-b border-default bg-elevated/50 shrink-0">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2 text-sm text-muted">
                                    <UIcon name="i-lucide-eye" class="size-4" />
                                    <span>Previewing saved version</span>
                                    <span v-if="form.isDirty" class="text-warning">(unsaved changes not shown)</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <!-- Screen Size Toggles -->
                                <div class="flex items-center gap-1 p-1 rounded-lg bg-elevated border border-default">
                                    <UButton
                                        v-for="(size, key) in previewSizes"
                                        :key="key"
                                        :icon="size.icon"
                                        :color="previewSize === key ? 'primary' : 'neutral'"
                                        :variant="previewSize === key ? 'solid' : 'ghost'"
                                        size="xs"
                                        :ui="{ rounded: 'rounded-md' }"
                                        @click="previewSize = key"
                                    />
                                </div>
                                <div class="flex items-center gap-2">
                                    <UButton
                                        icon="i-lucide-refresh-cw"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        @click="refreshPreview"
                                    >
                                        Refresh
                                    </UButton>
                                    <UButton
                                        icon="i-lucide-external-link"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        @click="viewPage"
                                    >
                                        Open in new tab
                                    </UButton>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 bg-[repeating-conic-gradient(#f3f4f6_0%_25%,#ffffff_0%_50%)] dark:bg-[repeating-conic-gradient(#1f2937_0%_25%,#111827_0%_50%)] bg-[length:20px_20px] flex justify-center overflow-auto p-4">
                            <div
                                class="bg-white shadow-xl rounded-lg overflow-hidden transition-all duration-300 h-full"
                                :style="{ width: previewSizes[previewSize].width, maxWidth: '100%' }"
                            >
                                <iframe
                                    :key="previewKey"
                                    :src="previewUrl"
                                    class="w-full h-full border-0"
                                    title="Page Preview"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div v-if="activeTab === 'settings'" class="p-6 w-full">
                        <div class="max-w-4xl mx-auto space-y-6">
                            <!-- General Settings -->
                            <UCard>
                                <template #header>
                                    <div class="flex items-center gap-2">
                                        <UIcon name="i-lucide-settings" class="size-5 text-muted" />
                                        <h3 class="font-semibold text-highlighted">General Settings</h3>
                                    </div>
                                </template>

                                <div class="space-y-4 w-full">
                                    <UFormField
                                        label="Slug"
                                        name="slug"
                                        :error="form.errors.slug"
                                        help="URL-friendly identifier"
                                        required
                                        class="w-full"
                                    >
                                        <UInput
                                            v-model="form.slug"
                                            placeholder="page-slug"
                                            :disabled="form.processing"
                                            class="w-full"
                                        >
                                            <template #leading>
                                                <span class="text-muted">/</span>
                                            </template>
                                        </UInput>
                                    </UFormField>

                                    <UFormField
                                        label="Status"
                                        name="status"
                                        :error="form.errors.status"
                                        required
                                        class="w-full"
                                    >
                                        <USelectMenu
                                            v-model="form.status"
                                            :items="statusOptions"
                                            placeholder="Select status..."
                                            value-key="value"
                                            :disabled="form.processing"
                                            class="w-full"
                                        />
                                    </UFormField>

                                    <UFormField
                                        label="Layout"
                                        name="layout"
                                        :error="form.errors.layout"
                                        required
                                        class="w-full"
                                    >
                                        <USelectMenu
                                            v-model="form.layout"
                                            :items="layoutOptions"
                                            placeholder="Select layout..."
                                            value-key="value"
                                            :disabled="form.processing"
                                            class="w-full"
                                        />
                                    </UFormField>

                                    <UFormField
                                        label="Author"
                                        name="author_id"
                                        :error="form.errors.author_id"
                                        class="w-full"
                                    >
                                        <USelectMenu
                                            v-model="form.author_id"
                                            :items="authorOptions"
                                            placeholder="Select author..."
                                            value-key="value"
                                            :disabled="form.processing"
                                            class="w-full"
                                        />
                                    </UFormField>

                                    <UFormField
                                        v-if="form.status === 'published'"
                                        label="Publish Date"
                                        name="published_at"
                                        :error="form.errors.published_at"
                                        class="w-full"
                                    >
                                        <UInput
                                            v-model="form.published_at"
                                            type="datetime-local"
                                            :disabled="form.processing"
                                            class="w-full"
                                        />
                                    </UFormField>
                                </div>
                            </UCard>

                            <!-- SEO Settings -->
                            <UCard>
                                <template #header>
                                    <div class="flex items-center gap-2">
                                        <UIcon name="i-lucide-search" class="size-5 text-muted" />
                                        <h3 class="font-semibold text-highlighted">SEO Settings</h3>
                                    </div>
                                </template>

                                <div class="space-y-4 w-full">
                                    <UFormField
                                        label="Meta Title"
                                        name="meta_title"
                                        :error="form.errors.meta_title"
                                        help="Title for search engines. Leave empty to use page title."
                                        class="w-full"
                                    >
                                        <UInput
                                            v-model="form.meta_title"
                                            placeholder="SEO title"
                                            :disabled="form.processing"
                                            class="w-full"
                                        />
                                    </UFormField>

                                    <UFormField
                                        label="Meta Description"
                                        name="meta_description"
                                        :error="form.errors.meta_description"
                                        help="Brief description for search results (recommended: 150-160 characters)"
                                        class="w-full"
                                    >
                                        <UTextarea
                                            v-model="form.meta_description"
                                            placeholder="SEO description"
                                            :rows="3"
                                            :disabled="form.processing"
                                            class="w-full"
                                        />
                                        <div class="text-xs text-muted mt-1">
                                            {{ form.meta_description?.length || 0 }} / 500 characters
                                        </div>
                                    </UFormField>

                                    <!-- Search Preview -->
                                    <div class="p-4 rounded-lg border border-default bg-elevated/50 w-full">
                                        <h4 class="text-sm font-medium text-muted mb-3">Search Preview</h4>
                                        <div class="space-y-1">
                                            <div class="text-lg text-blue-600 hover:underline cursor-pointer truncate">
                                                {{ form.meta_title || form.title || 'Page Title' }}
                                            </div>
                                            <div class="text-sm text-green-700 truncate">
                                                {{ `example.com/${form.slug || 'page-slug'}` }}
                                            </div>
                                            <div class="text-sm text-muted line-clamp-2">
                                                {{ form.meta_description || 'Add a meta description to improve your search presence...' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </UCard>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <UModal v-model:open="deleteModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Delete Page</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ page.title }}</strong>?
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <UButton
                            color="neutral"
                            variant="outline"
                            @click="deleteModalOpen = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="error"
                            @click="deletePage"
                        >
                            Delete Page
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
