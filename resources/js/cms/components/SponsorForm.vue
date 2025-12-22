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

interface SponsorWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    url?: string;
    url_translations?: Record<string, string>;
    slug?: string;
    featured_media_id?: number | null;
    featured_media?: MediaItem | null;
    is_active?: boolean;
    order?: number;
}

const props = withDefaults(defineProps<{
    sponsor?: SponsorWithTranslations;
    languages: Language[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const isEditing = computed(() => props.mode === 'edit' && props.sponsor?.uuid);
const activeTab = ref(props.languages[0]?.code || 'en');

// Initialize translations for all languages
function initNameTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.sponsor?.name_translations?.[lang.code] || '';
    });
    return translations;
}

function initUrlTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.sponsor?.url_translations?.[lang.code] || '';
    });
    return translations;
}

const form = useForm({
    name: initNameTranslations(),
    url: initUrlTranslations(),
    slug: props.sponsor?.slug || '',
    featured_media_id: props.sponsor?.featured_media_id || null as number | null,
    is_active: props.sponsor?.is_active ?? true,
    order: props.sponsor?.order ?? 0,
});

const selectedMedia = ref<MediaItem | null>(props.sponsor?.featured_media || null);
const mediaPickerOpen = ref(false);

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
    // Filter out empty translations
    const nameData = Object.fromEntries(
        Object.entries(form.name).filter(([_, v]) => v?.trim())
    );
    const urlData = Object.fromEntries(
        Object.entries(form.url).filter(([_, v]) => v?.trim())
    );

    // Transform form data for submission
    form.transform(() => ({
        name: nameData,
        url: urlData,
        slug: form.slug,
        featured_media_id: form.featured_media_id,
        is_active: form.is_active,
        order: form.order,
    }));

    if (isEditing.value && props.sponsor?.uuid) {
        form.put(`/cms/sponsors/${props.sponsor.uuid}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post('/cms/sponsors', {
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
        form.url[lang.code] = '';
    });
    form.slug = '';
    form.featured_media_id = null;
    form.is_active = true;
    form.order = 0;
    selectedMedia.value = null;
    form.clearErrors();
    activeTab.value = props.languages[0]?.code || 'en';
}

// Update form when sponsor changes (for edit mode)
watch(() => props.sponsor, (newSponsor) => {
    if (newSponsor) {
        props.languages.forEach(lang => {
            form.name[lang.code] = newSponsor.name_translations?.[lang.code] || '';
            form.url[lang.code] = newSponsor.url_translations?.[lang.code] || '';
        });
        form.slug = newSponsor.slug || '';
        form.featured_media_id = newSponsor.featured_media_id || null;
        form.is_active = newSponsor.is_active ?? true;
        form.order = newSponsor.order ?? 0;
        selectedMedia.value = newSponsor.featured_media || null;
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
                placeholder="ސްޕޮންސަރު ނަން ލިޔުއްވާ"
                :disabled="form.processing"
                :default-enabled="true"
                :show-toggle="false"
                class="w-full"
            />
            <UInput
                v-else
                v-model="form.name[activeTab]"
                :placeholder="languages.length > 1 ? `Enter sponsor name in ${languages.find(l => l.code === activeTab)?.name}` : 'e.g., Acme Corp'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- URL Input (per language) -->
        <UFormField
            :label="languages.length > 1 ? `Website URL (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Website URL'"
            name="url"
            :error="form.errors[`url.${activeTab}`] || form.errors.url"
        >
            <UInput
                v-model="form.url[activeTab]"
                type="url"
                :placeholder="languages.length > 1 ? `Enter website URL in ${languages.find(l => l.code === activeTab)?.name}` : 'https://example.com'"
                class="w-full"
                :dir="'ltr'"
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
                placeholder="acme-corp"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Featured Image -->
        <UFormField
            v-if="activeTab === languages[0]?.code"
            label="Logo / Featured Image"
            name="featured_media_id"
            :error="form.errors.featured_media_id"
        >
            <div v-if="selectedMedia" class="flex items-center gap-4 p-3 border border-default rounded-lg bg-elevated/50">
                <img
                    :src="selectedMedia.thumbnail_url || selectedMedia.url || ''"
                    :alt="selectedMedia.title || 'Sponsor logo'"
                    class="size-16 object-contain rounded bg-white"
                >
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-highlighted truncate">
                        {{ selectedMedia.title || 'Sponsor Logo' }}
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

        <!-- Media Picker Modal -->
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

        <!-- Order -->
        <UFormField
            v-if="activeTab === languages[0]?.code"
            label="Display Order"
            name="order"
            :error="form.errors.order"
            help="Lower numbers appear first"
        >
            <UInput
                v-model.number="form.order"
                type="number"
                min="0"
                class="w-32"
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
                {{ isEditing ? 'Save Changes' : 'Create Sponsor' }}
            </UButton>
        </div>
    </UForm>
</template>
