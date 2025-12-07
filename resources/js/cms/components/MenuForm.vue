<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import DhivehiInput from './DhivehiInput.vue';
import type { Menu, Language } from '../types';

const props = withDefaults(defineProps<{
    menu?: Menu;
    languages: Language[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const isEditing = computed(() => props.mode === 'edit' && props.menu?.uuid);
const activeTab = ref(props.languages[0]?.code || 'en');

// Initialize translations for all languages
function initNameTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.menu?.name_translations?.[lang.code] || '';
    });
    return translations;
}

function initDescriptionTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.menu?.description_translations?.[lang.code] || '';
    });
    return translations;
}

const form = useForm({
    name: initNameTranslations(),
    location: props.menu?.location || '',
    description: initDescriptionTranslations(),
    is_active: props.menu?.is_active ?? true,
});

// Auto-generate location from first language name (only in create mode)
watch(() => form.name[props.languages[0]?.code || 'en'], (newName) => {
    if (props.mode === 'create' && newName) {
        const currentLocation = form.location;
        const previousName = form.name[props.languages[0]?.code || 'en']?.slice(0, -1) || '';
        if (!currentLocation || currentLocation === locationify(previousName)) {
            form.location = locationify(newName);
        }
    }
});

function locationify(text: string): string {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_]+/g, '-')
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
        location: form.location,
        description: Object.keys(descData).length > 0 ? descData : null,
        is_active: form.is_active,
    }));

    if (isEditing.value && props.menu?.uuid) {
        form.put(`/cms/menus/${props.menu.uuid}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post('/cms/menus', {
            preserveScroll: true,
            onSuccess: () => {
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
    form.location = '';
    form.is_active = true;
    form.clearErrors();
    activeTab.value = props.languages[0]?.code || 'en';
}

// Update form when menu changes (for edit mode)
watch(() => props.menu, (newMenu) => {
    if (newMenu) {
        props.languages.forEach(lang => {
            form.name[lang.code] = newMenu.name_translations?.[lang.code] || '';
            form.description[lang.code] = newMenu.description_translations?.[lang.code] || '';
        });
        form.location = newMenu.location || '';
        form.is_active = newMenu.is_active ?? true;
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
                placeholder="މެނޫގެ ނަން ލިޔުއްވާ"
                :disabled="form.processing"
                :default-enabled="true"
                :show-toggle="false"
                class="w-full"
            />
            <UInput
                v-else
                v-model="form.name[activeTab]"
                :placeholder="languages.length > 1 ? `Enter name in ${languages.find(l => l.code === activeTab)?.name}` : 'e.g., Main Navigation'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Location (only on default/first language tab) -->
        <UFormField
            v-if="activeTab === languages[0]?.code"
            label="Location"
            name="location"
            :error="form.errors.location"
            help="Unique identifier used to display this menu (e.g., header, footer)"
            required
        >
            <UInput
                v-model="form.location"
                placeholder="header"
                class="w-full"
                :disabled="form.processing || isEditing"
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
                :placeholder="languages.length > 1 ? `Describe this menu in ${languages.find(l => l.code === activeTab)?.name}` : 'Describe this menu...'"
                :rows="3"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

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
                    <span class="text-sm">{{ form.is_active ? 'Active' : 'Inactive' }}</span>
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
                {{ isEditing ? 'Save Changes' : 'Create Menu' }}
            </UButton>
        </div>
    </UForm>
</template>
