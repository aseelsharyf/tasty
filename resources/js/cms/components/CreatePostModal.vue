<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface Language {
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_default: boolean;
}

interface PostType {
    value: string;
    label: string;
    icon?: string;
}

const props = defineProps<{
    open: boolean;
    languages: Language[];
    postTypes: PostType[];
    currentLanguageCode?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const title = ref('');
const selectedLanguage = ref<string | null>(null);
const selectedPostType = ref<string | null>(null);
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

// Initialize defaults when modal opens
watch(() => props.open, (open) => {
    if (open) {
        // Reset form
        title.value = '';
        errors.value = {};
        isSubmitting.value = false;

        // Set default language (current or first default)
        const defaultLang = props.languages.find(l => l.is_default);
        selectedLanguage.value = props.currentLanguageCode || defaultLang?.code || props.languages[0]?.code || null;

        // Set default post type (first one)
        selectedPostType.value = props.postTypes[0]?.value || null;
    }
});

const languageOptions = computed(() =>
    props.languages.map(lang => ({
        label: lang.name,
        value: lang.code,
        icon: lang.direction === 'rtl' ? 'i-lucide-align-right' : 'i-lucide-align-left',
    }))
);

const postTypeOptions = computed(() =>
    props.postTypes.map(type => ({
        label: type.label,
        value: type.value,
        icon: type.icon,
    }))
);

const canSubmit = computed(() =>
    title.value.trim().length > 0 &&
    selectedLanguage.value &&
    selectedPostType.value &&
    !isSubmitting.value
);

async function createDraft() {
    if (!canSubmit.value) return;

    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await fetch('/cms/posts/quick-draft', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                title: title.value.trim(),
                language_code: selectedLanguage.value,
                post_type: selectedPostType.value,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                errors.value = Object.fromEntries(
                    Object.entries(data.errors).map(([key, messages]) => [
                        key,
                        Array.isArray(messages) ? messages[0] : messages,
                    ])
                );
            } else {
                errors.value = { general: data.message || 'Failed to create draft' };
            }
            return;
        }

        if (data.redirect) {
            isOpen.value = false;
            router.visit(data.redirect);
        }
    } catch (e) {
        console.error('Create draft error:', e);
        errors.value = { general: 'An unexpected error occurred. Please try again.' };
    } finally {
        isSubmitting.value = false;
    }
}

function getCsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}
</script>

<template>
    <UModal v-model:open="isOpen" :ui="{ width: 'max-w-md' }">
        <template #content>
            <UCard>
                <!-- Header -->
                <template #header>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-primary/10">
                                <UIcon name="i-lucide-file-plus" class="size-5 text-primary" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-highlighted">Create New Post</h3>
                                <p class="text-sm text-muted">Start with a title and we'll create a draft for you.</p>
                            </div>
                        </div>
                        <UButton
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            @click="isOpen = false"
                        />
                    </div>
                </template>

                <!-- Body -->
                <div class="space-y-4">
                    <!-- General Error -->
                    <UAlert
                        v-if="errors.general"
                        color="error"
                        variant="subtle"
                        :title="errors.general"
                        icon="i-lucide-alert-circle"
                    />

                    <!-- Title -->
                    <UFormField label="Title" :error="errors.title" required>
                        <UInput
                            v-model="title"
                            placeholder="Enter post title..."
                            autofocus
                            class="w-full"
                            @keydown.enter="createDraft"
                        />
                    </UFormField>

                    <!-- Language & Post Type Row -->
                    <div class="grid grid-cols-2 gap-4">
                        <UFormField label="Language" :error="errors.language_code" required>
                            <USelectMenu
                                v-model="selectedLanguage"
                                :items="languageOptions"
                                value-key="value"
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField label="Article Type" :error="errors.post_type" required>
                            <USelectMenu
                                v-model="selectedPostType"
                                :items="postTypeOptions"
                                value-key="value"
                                class="w-full"
                            />
                        </UFormField>
                    </div>
                </div>

                <!-- Footer -->
                <template #footer>
                    <div class="flex justify-end gap-3">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            @click="isOpen = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            :loading="isSubmitting"
                            :disabled="!canSubmit"
                            @click="createDraft"
                        >
                            Create Draft
                        </UButton>
                    </div>
                </template>
            </UCard>
        </template>
    </UModal>
</template>
