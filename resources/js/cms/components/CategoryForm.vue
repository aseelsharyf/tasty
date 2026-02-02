<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import DhivehiInput from './DhivehiInput.vue';
import MediaPickerModal from './MediaPickerModal.vue';
import type { ParentOption, Language } from '../types';
import { useCmsPath } from '../composables/useCmsPath';

interface FeaturedImage {
    id: number;
    url: string;
    thumbnail_url?: string;
    alt_text?: string;
}

interface CategoryWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    slug?: string;
    description?: string;
    description_translations?: Record<string, string>;
    parent_id?: number | null;
    featured_image_id?: number | null;
    featured_image?: FeaturedImage | null;
}

const props = withDefaults(defineProps<{
    category?: CategoryWithTranslations;
    parentOptions?: ParentOption[];
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

const isEditing = computed(() => props.mode === 'edit' && props.category?.uuid);
const activeTab = ref(props.languages[0]?.code || 'en');

// Initialize translations for all languages
function initNameTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.category?.name_translations?.[lang.code] || '';
    });
    return translations;
}

function initDescriptionTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.category?.description_translations?.[lang.code] || '';
    });
    return translations;
}

const form = useForm({
    name: initNameTranslations(),
    slug: props.category?.slug || '',
    description: initDescriptionTranslations(),
    parent_id: props.category?.parent_id ?? null,
    featured_image_id: props.category?.featured_image_id ?? null,
});

// Featured image state
const featuredImage = ref<FeaturedImage | null>(props.category?.featured_image || null);
const showMediaPicker = ref(false);

function handleMediaSelect(items: any[]) {
    if (items.length > 0) {
        const item = items[0];
        featuredImage.value = {
            id: item.id,
            url: item.url,
            thumbnail_url: item.thumbnail_url,
            alt_text: item.alt_text,
        };
        form.featured_image_id = item.id;
    }
    showMediaPicker.value = false;
}

function removeFeaturedImage() {
    featuredImage.value = null;
    form.featured_image_id = null;
}

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
    const descData = Object.fromEntries(
        Object.entries(form.description).filter(([_, v]) => v?.trim())
    );

    // Transform form data for submission
    form.transform(() => ({
        name: nameData,
        slug: form.slug,
        description: Object.keys(descData).length > 0 ? descData : null,
        parent_id: form.parent_id,
        featured_image_id: form.featured_image_id,
    }));

    if (isEditing.value && props.category?.uuid) {
        form.put(cmsPath(`/categories/${props.category.uuid}`), {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(cmsPath('/categories'), {
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
        form.description[lang.code] = '';
    });
    form.slug = '';
    form.parent_id = null;
    form.featured_image_id = null;
    featuredImage.value = null;
    form.clearErrors();
    activeTab.value = props.languages[0]?.code || 'en';
}

const parentSelectOptions = computed(() => {
    return [
        { label: 'None (Root Category)', value: null },
        ...(props.parentOptions || []).map(p => ({
            label: p.name,
            value: p.id,
        })),
    ];
});

// Update form when category changes (for edit mode)
watch(() => props.category, (newCategory) => {
    if (newCategory) {
        props.languages.forEach(lang => {
            form.name[lang.code] = newCategory.name_translations?.[lang.code] || '';
            form.description[lang.code] = newCategory.description_translations?.[lang.code] || '';
        });
        form.slug = newCategory.slug || '';
        form.parent_id = newCategory.parent_id ?? null;
        form.featured_image_id = newCategory.featured_image_id ?? null;
        featuredImage.value = newCategory.featured_image || null;
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
                placeholder="ކެޓެގަރީ ނަން ލިޔުއްވާ"
                :disabled="form.processing"
                :default-enabled="true"
                :show-toggle="false"
                class="w-full"
            />
            <UInput
                v-else
                v-model="form.name[activeTab]"
                :placeholder="languages.length > 1 ? `Enter name in ${languages.find(l => l.code === activeTab)?.name}` : 'e.g., Main Courses'"
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
                placeholder="main-courses"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Description Input (per language) -->
        <UFormField
            :label="languages.length > 1 ? `Description (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Description'"
            name="description"
            :error="form.errors[`description.${activeTab}`] || form.errors.description"
        >
            <DhivehiInput
                v-if="isDhivehi"
                v-model="form.description[activeTab]"
                type="textarea"
                placeholder="ތަފްޞީލް ލިޔުއްވާ"
                :rows="3"
                :disabled="form.processing"
                :default-enabled="true"
                :show-toggle="false"
                class="w-full"
            />
            <UTextarea
                v-else
                v-model="form.description[activeTab]"
                :placeholder="languages.length > 1 ? `Describe this category in ${languages.find(l => l.code === activeTab)?.name}` : 'Describe this category...'"
                :rows="3"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Parent Category (only on default/first language tab) -->
        <UFormField
            v-if="activeTab === languages[0]?.code && parentSelectOptions.length > 1"
            label="Parent Category"
            name="parent_id"
            :error="form.errors.parent_id"
            help="Leave empty for a root-level category"
        >
            <USelectMenu
                v-model="form.parent_id"
                :items="parentSelectOptions"
                placeholder="Select parent..."
                value-key="value"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Featured Image (only on default/first language tab) -->
        <UFormField
            v-if="activeTab === languages[0]?.code"
            label="Featured Artwork"
            name="featured_image_id"
            :error="form.errors.featured_image_id"
            help="Image displayed on the category page header"
        >
            <div class="space-y-3">
                <!-- Image Preview -->
                <div
                    v-if="featuredImage"
                    class="relative w-40 aspect-square rounded-lg overflow-hidden border border-default bg-muted/30"
                >
                    <img
                        :src="featuredImage.thumbnail_url || featuredImage.url"
                        :alt="featuredImage.alt_text || 'Featured image'"
                        class="w-full h-full object-cover"
                    />
                    <button
                        type="button"
                        class="absolute top-2 right-2 p-1.5 bg-black/60 rounded-full hover:bg-black/80 transition-colors"
                        @click="removeFeaturedImage"
                    >
                        <UIcon name="i-lucide-x" class="size-4 text-white" />
                    </button>
                </div>

                <!-- Select Button -->
                <UButton
                    type="button"
                    color="neutral"
                    variant="soft"
                    icon="i-lucide-image"
                    :disabled="form.processing"
                    @click="showMediaPicker = true"
                >
                    {{ featuredImage ? 'Change Image' : 'Select Image' }}
                </UButton>
            </div>
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
                {{ isEditing ? 'Save Changes' : 'Create Category' }}
            </UButton>
        </div>

        <!-- Media Picker Modal -->
        <MediaPickerModal
            v-model:open="showMediaPicker"
            type="images"
            default-category="media"
            @select="handleMediaSelect"
        />
    </UForm>
</template>
