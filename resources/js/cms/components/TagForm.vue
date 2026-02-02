<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import DhivehiInput from './DhivehiInput.vue';
import type { Language } from '../types';
import { useCmsPath } from '../composables/useCmsPath';

interface TagWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    slug?: string;
}

const props = withDefaults(defineProps<{
    tag?: TagWithTranslations;
    languages: Language[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const { cmsPath } = useCmsPath();

const isEditing = computed(() => props.mode === 'edit' && props.tag?.uuid);
const activeTab = ref(props.languages[0]?.code || 'en');

// Initialize translations for all languages
function initNameTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.tag?.name_translations?.[lang.code] || '';
    });
    return translations;
}

const form = useForm({
    name: initNameTranslations(),
    slug: props.tag?.slug || '',
});

// Auto-generate slug from first language name (only in create mode)
watch(() => form.name[props.languages[0]?.code || 'en'], (newName) => {
    if (props.mode === 'create' && newName) {
        const currentSlug = form.slug;
        const previousName = form.name[props.languages[0]?.code || 'en']?.slice(0, -1) || '';
        if (!currentSlug || currentSlug === slugify(previousName)) {
            form.slug = slugify(newName);
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
    // Filter out empty translations
    const nameData = Object.fromEntries(
        Object.entries(form.name).filter(([_, v]) => v?.trim())
    );

    // Transform form data for submission
    form.transform(() => ({
        name: nameData,
        slug: form.slug,
    }));

    if (isEditing.value && props.tag?.uuid) {
        form.put(cmsPath(`/tags/${props.tag.uuid}`), {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(cmsPath('/tags'), {
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
    // Reset all fields
    props.languages.forEach(lang => {
        form.name[lang.code] = '';
    });
    form.slug = '';
    form.clearErrors();
    activeTab.value = props.languages[0]?.code || 'en';
}

// Update form when tag changes (for edit mode)
watch(() => props.tag, (newTag) => {
    if (newTag) {
        props.languages.forEach(lang => {
            form.name[lang.code] = newTag.name_translations?.[lang.code] || '';
        });
        form.slug = newTag.slug || '';
    }
}, { immediate: true, deep: true });

// Get translation status for tabs
function hasTranslation(langCode: string): boolean {
    return !!(form.name[langCode]?.trim());
}

// Check if current language is RTL
const isCurrentRtl = computed(() => {
    const lang = props.languages.find(l => l.code === activeTab.value);
    return lang?.direction === 'rtl';
});

// Check if current language is Dhivehi (for special keyboard)
const isDhivehi = computed(() => activeTab.value === 'dv');

// Expose reset method for parent components
defineExpose({ reset, form });
</script>

<template>
    <UForm
        :state="form"
        class="space-y-4"
        @submit="onSubmit"
    >
        <!-- Language Tabs (only show if more than one language) -->
        <div v-if="languages.length > 1" class="border-b border-default">
            <nav class="flex gap-1 -mb-px">
                <button
                    v-for="lang in languages"
                    :key="lang.code"
                    type="button"
                    :class="[
                        'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
                        activeTab === lang.code
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                    ]"
                    @click="activeTab = lang.code"
                >
                    <span class="flex items-center gap-2">
                        {{ lang.native_name }}
                        <span
                            v-if="hasTranslation(lang.code)"
                            class="size-2 rounded-full bg-success"
                            title="Translation available"
                        />
                        <span
                            v-else
                            class="size-2 rounded-full bg-muted/30"
                            title="No translation"
                        />
                    </span>
                </button>
            </nav>
        </div>

        <!-- Name Input (per language) -->
        <UFormField
            :label="languages.length > 1 ? `Name (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Name'"
            name="name"
            :error="form.errors[`name.${activeTab}`] || form.errors.name"
            required
        >
            <DhivehiInput
                v-if="isDhivehi"
                v-model="form.name[activeTab]"
                placeholder="ޓެގް ނަން ލިޔުއްވާ"
                :disabled="form.processing"
                :default-enabled="true"
                :show-toggle="false"
                class="w-full"
            />
            <UInput
                v-else
                v-model="form.name[activeTab]"
                :placeholder="languages.length > 1 ? `Enter tag name in ${languages.find(l => l.code === activeTab)?.name}` : 'e.g., Quick Meals'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Slug (only on default/first language tab) -->
        <UFormField
            v-if="activeTab === languages[0]?.code"
            label="Slug"
            name="slug"
            :error="form.errors.slug"
            :help="mode === 'create' ? 'URL-friendly version (auto-generated)' : 'URL-friendly version'"
        >
            <UInput
                v-model="form.slug"
                placeholder="quick-meals"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <div class="flex justify-end gap-2 pt-6">
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
                {{ isEditing ? 'Save Changes' : 'Create Tag' }}
            </UButton>
        </div>
    </UForm>
</template>
