<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';

const toast = useToast();
import CodeEditor from '../../components/CodeEditor.vue';
import DhivehiInput from '../../components/DhivehiInput.vue';
import type { Author, Language } from '../../types';

const props = defineProps<{
    statuses: Record<string, string>;
    layouts: Record<string, string>;
    authors: Author[];
    languages: Language[];
    currentLanguage: Language;
}>();

const { cmsPath } = useCmsPath();

const activeTab = ref<'content' | 'settings' | 'preview'>('content');

const form = useForm({
    title: '',
    slug: '',
    content: '',
    layout: 'default',
    status: 'draft',
    is_blade: true,
    author_id: null as number | null,
    meta_title: '',
    meta_description: '',
    published_at: '',
});

// Auto-generate slug from title
watch(() => form.title, (newTitle) => {
    if (newTitle) {
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
    form.post(cmsPath(`/pages/${props.currentLanguage.code}`), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                title: 'Success',
                description: 'Page created successfully',
                color: 'success',
            });
        },
    });
}

function goBack() {
    router.visit(cmsPath(`/pages/${props.currentLanguage.code}`));
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
        ...props.authors.map(a => ({
            label: a.name,
            value: a.id,
        })),
    ];
});

const isRtl = computed(() => props.currentLanguage.direction === 'rtl');
const isDhivehi = computed(() => props.currentLanguage.code === 'dv');
</script>

<template>
    <Head title="Create Page" />

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
                        <h1 class="text-lg font-semibold text-highlighted">Create Page</h1>
                        <p class="text-sm text-muted">Add a new page to your website</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <UButton
                        color="neutral"
                        variant="outline"
                        @click="goBack"
                    >
                        Cancel
                    </UButton>
                    <UButton
                        :loading="form.processing"
                        @click="onSubmit"
                    >
                        Create Page
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

                        <UFormField
                            label="Content"
                            name="content"
                            :error="form.errors.content"
                            help="Write your page content using HTML or Blade syntax"
                            class="w-full max-w-full overflow-hidden"
                        >
                            <CodeEditor
                                v-model="form.content"
                                language="blade"
                                placeholder="<!-- Enter your page content here -->"
                                height="500px"
                                :disabled="form.processing"
                            />
                        </UFormField>

                        <div class="flex items-center gap-4">
                            <UCheckbox
                                v-model="form.is_blade"
                                label="Enable Blade rendering"
                                :disabled="form.processing"
                            />
                            <span class="text-sm text-muted" v-pre>
                                When enabled, Blade directives like @if, @foreach, {{ $variable }} will be processed
                            </span>
                        </div>
                    </div>

                    <!-- Preview Tab -->
                    <div v-if="activeTab === 'preview'" class="h-full flex flex-col">
                        <div class="flex-1 flex items-center justify-center bg-elevated/30">
                            <div class="text-center space-y-4 p-8">
                                <div class="flex items-center justify-center size-16 mx-auto rounded-full bg-muted/10">
                                    <UIcon name="i-lucide-eye-off" class="size-8 text-muted" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-highlighted">Preview not available</h3>
                                    <p class="text-sm text-muted mt-1">
                                        Save the page first to preview it.
                                    </p>
                                </div>
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
                                        help="URL-friendly identifier (auto-generated from title)"
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
    </DashboardLayout>
</template>
