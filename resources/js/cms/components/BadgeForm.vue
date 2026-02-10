<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import DhivehiInput from './DhivehiInput.vue';
import type { Language } from '../types';
import { useCmsPath } from '../composables/useCmsPath';

interface BadgeWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    slug?: string;
    icon?: string | null;
    color?: string;
    description?: string;
    description_translations?: Record<string, string>;
    is_active?: boolean;
    order?: number;
}

const props = withDefaults(defineProps<{
    badge?: BadgeWithTranslations;
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

const isEditing = computed(() => props.mode === 'edit' && props.badge?.uuid);
const activeTab = ref(props.languages[0]?.code || 'en');

const colorOptions = [
    { label: 'Primary', value: 'primary' },
    { label: 'Success', value: 'success' },
    { label: 'Warning', value: 'warning' },
    { label: 'Error', value: 'error' },
    { label: 'Info', value: 'info' },
    { label: 'Neutral', value: 'neutral' },
];

function initTranslations(field: 'name' | 'description'): Record<string, string> {
    const translations: Record<string, string> = {};
    const source = field === 'name' ? props.badge?.name_translations : props.badge?.description_translations;
    props.languages.forEach(lang => {
        translations[lang.code] = source?.[lang.code] || '';
    });
    return translations;
}

const form = useForm({
    name: initTranslations('name'),
    slug: props.badge?.slug || '',
    icon: props.badge?.icon || '',
    color: props.badge?.color || 'primary',
    description: initTranslations('description'),
    is_active: props.badge?.is_active ?? true,
    order: props.badge?.order ?? 0,
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
    const nameData = Object.fromEntries(
        Object.entries(form.name).filter(([_, v]) => v?.trim())
    );
    const descriptionData = Object.fromEntries(
        Object.entries(form.description).filter(([_, v]) => v?.trim())
    );

    form.transform(() => ({
        name: nameData,
        slug: form.slug,
        icon: form.icon || null,
        color: form.color,
        description: Object.keys(descriptionData).length > 0 ? descriptionData : null,
        is_active: form.is_active,
        order: form.order,
    }));

    if (isEditing.value && props.badge?.uuid) {
        form.put(cmsPath(`/settings/badges/${props.badge.uuid}`), {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(cmsPath('/settings/badges'), {
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
    form.icon = '';
    form.color = 'primary';
    form.is_active = true;
    form.order = 0;
    form.clearErrors();
    activeTab.value = props.languages[0]?.code || 'en';
}

// Update form when badge changes (for edit mode)
watch(() => props.badge, (newBadge) => {
    if (newBadge) {
        props.languages.forEach(lang => {
            form.name[lang.code] = newBadge.name_translations?.[lang.code] || '';
            form.description[lang.code] = newBadge.description_translations?.[lang.code] || '';
        });
        form.slug = newBadge.slug || '';
        form.icon = newBadge.icon || '';
        form.color = newBadge.color || 'primary';
        form.is_active = newBadge.is_active ?? true;
        form.order = newBadge.order ?? 0;
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
                placeholder="ބެޖް ނަން ލިޔުއްވާ"
                :disabled="form.processing"
                :default-enabled="true"
                :show-toggle="false"
                class="w-full"
            />
            <UInput
                v-else
                v-model="form.name[activeTab]"
                :placeholder="languages.length > 1 ? `Enter badge name in ${languages.find(l => l.code === activeTab)?.name}` : 'e.g., Verified'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Description (per language) -->
        <UFormField
            :label="languages.length > 1 ? `Description (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Description'"
            name="description"
            :error="form.errors[`description.${activeTab}`] || form.errors.description"
        >
            <UTextarea
                v-model="form.description[activeTab]"
                :placeholder="languages.length > 1 ? `Enter description in ${languages.find(l => l.code === activeTab)?.name}` : 'Brief description of this badge'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
                :rows="2"
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
                placeholder="verified"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Non-translatable fields (only show on first language tab) -->
        <template v-if="activeTab === languages[0]?.code">
            <!-- Icon -->
            <UFormField
                label="Icon"
                name="icon"
                :error="form.errors.icon"
                help="Lucide icon class (e.g., i-lucide-badge-check)"
            >
                <UInput
                    v-model="form.icon"
                    placeholder="i-lucide-badge-check"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Color -->
            <UFormField
                label="Color"
                name="color"
                :error="form.errors.color"
                required
            >
                <USelectMenu
                    v-model="form.color"
                    :items="colorOptions"
                    value-key="value"
                    placeholder="Select color..."
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Order -->
            <UFormField
                label="Order"
                name="order"
                :error="form.errors.order"
                help="Lower numbers appear first."
            >
                <UInput
                    v-model.number="form.order"
                    type="number"
                    min="0"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Active Toggle -->
            <UFormField
                label="Active"
                name="is_active"
                :error="form.errors.is_active"
            >
                <USwitch
                    v-model="form.is_active"
                    :disabled="form.processing"
                />
            </UFormField>
        </template>

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
                {{ isEditing ? 'Save Changes' : 'Create Badge' }}
            </UButton>
        </div>
    </UForm>
</template>
