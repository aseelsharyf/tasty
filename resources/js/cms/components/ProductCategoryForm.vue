<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import DhivehiInput from './DhivehiInput.vue';
import MediaPickerModal from './MediaPickerModal.vue';
import type { Language } from '../types';

interface MediaItem {
    id: number;
    uuid?: string;
    url: string | null;
    thumbnail_url?: string | null;
    title?: string | null;
    is_image?: boolean;
}

interface ProductCategoryWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    description?: string;
    description_translations?: Record<string, string>;
    slug?: string;
    featured_media_id?: number | null;
    featured_media?: MediaItem | null;
    is_active?: boolean;
    order?: number;
}

const props = withDefaults(defineProps<{
    category?: ProductCategoryWithTranslations;
    languages: Language[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const isEditing = computed(() => props.mode === 'edit' && props.category?.uuid);
const activeTab = ref(props.languages[0]?.code || 'en');

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
    description: initDescriptionTranslations(),
    slug: props.category?.slug || '',
    featured_media_id: props.category?.featured_media_id || null as number | null,
    is_active: props.category?.is_active ?? true,
});

const selectedMedia = ref<MediaItem | null>(props.category?.featured_media || null);
const mediaPickerOpen = ref(false);

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

function onMediaSelect(media: MediaItem[]) {
    if (media.length > 0) {
        selectedMedia.value = media[0];
        form.featured_media_id = media[0].id;
    }
}

function removeMedia() {
    selectedMedia.value = null;
    form.featured_media_id = null;
}

function onSubmit() {
    const nameData = Object.fromEntries(
        Object.entries(form.name).filter(([_, v]) => v?.trim())
    );
    const descriptionData = Object.fromEntries(
        Object.entries(form.description).filter(([_, v]) => v?.trim())
    );

    form.transform(() => ({
        name: nameData,
        description: descriptionData,
        slug: form.slug,
        featured_media_id: form.featured_media_id,
        is_active: form.is_active,
    }));

    if (isEditing.value && props.category?.uuid) {
        form.put(`/cms/product-categories/${props.category.uuid}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post('/cms/product-categories', {
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
    props.languages.forEach(lang => {
        form.name[lang.code] = '';
        form.description[lang.code] = '';
    });
    form.slug = '';
    form.featured_media_id = null;
    form.is_active = true;
    selectedMedia.value = null;
    form.clearErrors();
    activeTab.value = props.languages[0]?.code || 'en';
}

watch(() => props.category, (newCategory) => {
    if (newCategory) {
        props.languages.forEach(lang => {
            form.name[lang.code] = newCategory.name_translations?.[lang.code] || '';
            form.description[lang.code] = newCategory.description_translations?.[lang.code] || '';
        });
        form.slug = newCategory.slug || '';
        form.featured_media_id = newCategory.featured_media_id || null;
        form.is_active = newCategory.is_active ?? true;
        selectedMedia.value = newCategory.featured_media || null;
    }
}, { immediate: true, deep: true });

function hasTranslation(langCode: string): boolean {
    return !!(form.name[langCode]?.trim());
}

const isCurrentRtl = computed(() => {
    const lang = props.languages.find(l => l.code === activeTab.value);
    return lang?.direction === 'rtl';
});

const isDhivehi = computed(() => activeTab.value === 'dv');

defineExpose({ reset, form });
</script>

<template>
    <UForm
        :state="form"
        class="space-y-4"
        @submit="onSubmit"
    >
        <!-- Language Tabs -->
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

        <!-- Name Input -->
        <UFormField
            :label="languages.length > 1 ? `Name (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Name'"
            name="name"
            :error="form.errors[`name.${activeTab}`] || form.errors.name"
            required
        >
            <DhivehiInput
                v-if="isDhivehi"
                v-model="form.name[activeTab]"
                placeholder="ކެޓަގަރީ ނަން ލިޔުއްވާ"
                :disabled="form.processing"
                :default-enabled="true"
                :show-toggle="false"
                class="w-full"
            />
            <UInput
                v-else
                v-model="form.name[activeTab]"
                :placeholder="languages.length > 1 ? `Enter category name in ${languages.find(l => l.code === activeTab)?.name}` : 'e.g., Pantry'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Description Input -->
        <UFormField
            :label="languages.length > 1 ? `Description (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Description'"
            name="description"
            :error="form.errors[`description.${activeTab}`] || form.errors.description"
        >
            <UTextarea
                v-model="form.description[activeTab]"
                :placeholder="languages.length > 1 ? `Enter description in ${languages.find(l => l.code === activeTab)?.name}` : 'Category description...'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
                rows="3"
            />
        </UFormField>

        <!-- Slug -->
        <UFormField
            v-if="activeTab === languages[0]?.code"
            label="Slug"
            name="slug"
            :error="form.errors.slug"
            :help="mode === 'create' ? 'URL-friendly version (auto-generated)' : 'URL-friendly version'"
        >
            <UInput
                v-model="form.slug"
                placeholder="pantry"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Featured Image -->
        <UFormField
            v-if="activeTab === languages[0]?.code"
            label="Featured Image"
            name="featured_media_id"
            :error="form.errors.featured_media_id"
        >
            <div v-if="selectedMedia" class="flex items-center gap-4 p-3 border border-default rounded-lg bg-elevated/50">
                <img
                    :src="selectedMedia.thumbnail_url || selectedMedia.url || ''"
                    :alt="selectedMedia.title || 'Category image'"
                    class="size-16 object-contain rounded bg-white"
                >
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-highlighted truncate">
                        {{ selectedMedia.title || 'Category Image' }}
                    </p>
                </div>
                <UButton
                    color="error"
                    variant="ghost"
                    icon="i-lucide-x"
                    size="sm"
                    @click="removeMedia"
                />
            </div>
            <UButton
                v-else
                color="neutral"
                variant="outline"
                icon="i-lucide-image"
                :disabled="form.processing"
                @click="mediaPickerOpen = true"
            >
                Select Image
            </UButton>
        </UFormField>

        <MediaPickerModal
            v-model:open="mediaPickerOpen"
            type="images"
            :multiple="false"
            @select="onMediaSelect"
        />

        <!-- Active Status -->
        <UFormField
            v-if="activeTab === languages[0]?.code"
            label="Status"
            name="is_active"
        >
            <USwitch
                v-model="form.is_active"
                :disabled="form.processing"
            >
                <template #label>
                    {{ form.is_active ? 'Active' : 'Inactive' }}
                </template>
            </USwitch>
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
    </UForm>
</template>
