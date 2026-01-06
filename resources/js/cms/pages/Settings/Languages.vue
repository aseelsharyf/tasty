<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import type { BreadcrumbItem } from '@nuxt/ui';

interface Language {
    id: number;
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_active: boolean;
    is_default: boolean;
    order: number;
    posts_count: number;
}

const props = defineProps<{
    languages: Language[];
}>();

const addLanguageModal = ref(false);
const editLanguageModal = ref(false);
const deleteLanguageModal = ref(false);
const languageToEdit = ref<Language | null>(null);
const languageToDelete = ref<Language | null>(null);

const newLanguageForm = useForm({
    code: '',
    name: '',
    native_name: '',
    direction: 'ltr' as 'ltr' | 'rtl',
    is_active: true,
    is_default: false,
});

const editLanguageForm = useForm({
    code: '',
    name: '',
    native_name: '',
    direction: 'ltr' as 'ltr' | 'rtl',
    is_active: true,
    is_default: false,
});

const directionOptions = [
    { label: 'Left to Right (LTR)', value: 'ltr' },
    { label: 'Right to Left (RTL)', value: 'rtl' },
];

function openAddModal() {
    newLanguageForm.reset();
    addLanguageModal.value = true;
}

function addLanguage() {
    newLanguageForm.post('/cms/settings/languages', {
        onSuccess: () => {
            addLanguageModal.value = false;
        },
    });
}

function openEditModal(language: Language) {
    languageToEdit.value = language;
    editLanguageForm.code = language.code;
    editLanguageForm.name = language.name;
    editLanguageForm.native_name = language.native_name;
    editLanguageForm.direction = language.direction;
    editLanguageForm.is_active = language.is_active;
    editLanguageForm.is_default = language.is_default;
    editLanguageModal.value = true;
}

function updateLanguage() {
    if (!languageToEdit.value) return;

    editLanguageForm.put(`/cms/settings/languages/${languageToEdit.value.code}`, {
        onSuccess: () => {
            editLanguageModal.value = false;
            languageToEdit.value = null;
        },
    });
}

function openDeleteModal(language: Language) {
    languageToDelete.value = language;
    deleteLanguageModal.value = true;
}

function deleteLanguage() {
    if (!languageToDelete.value) return;

    router.delete(`/cms/settings/languages/${languageToDelete.value.code}`, {
        onSuccess: () => {
            deleteLanguageModal.value = false;
            languageToDelete.value = null;
        },
    });
}

function setAsDefault(language: Language) {
    router.put(`/cms/settings/languages/${language.code}`, {
        ...language,
        is_default: true,
    });
}

function toggleActive(language: Language) {
    router.put(`/cms/settings/languages/${language.code}`, {
        ...language,
        is_active: !language.is_active,
    });
}

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Settings', to: '/cms/settings' },
    { label: 'Languages' },
];

const activeLanguages = computed(() => props.languages.filter(l => l.is_active));
const inactiveLanguages = computed(() => props.languages.filter(l => !l.is_active));
</script>

<template>
    <Head title="Languages Settings" />

    <DashboardLayout>
        <UDashboardPanel id="settings-languages" :ui="{ body: 'lg:py-12' }">
            <template #header>
                <UDashboardNavbar>
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #title>
                        <UBreadcrumb :items="breadcrumbs" />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-3xl mx-auto">
                    <UPageCard
                        title="Languages"
                        description="Manage the languages available for content creation."
                        variant="naked"
                        orientation="horizontal"
                        class="mb-4"
                    >
                        <div class="flex gap-2 lg:ms-auto">
                            <UButton @click="openAddModal">
                                <UIcon name="i-lucide-plus" class="size-4 mr-1" />
                                Add Language
                            </UButton>
                        </div>
                    </UPageCard>

                    <!-- Languages List -->
                    <div class="space-y-4">
                        <div
                            v-for="language in languages"
                            :key="language.id"
                            class="border border-default rounded-xl overflow-hidden"
                        >
                            <div class="p-4 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center justify-center size-12 rounded-lg bg-elevated">
                                        <span
                                            class="text-lg font-bold"
                                            :class="language.direction === 'rtl' ? 'font-dhivehi' : ''"
                                        >
                                            {{ language.code.toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-semibold text-highlighted">{{ language.name }}</h3>
                                            <span
                                                class="text-muted"
                                                :class="language.direction === 'rtl' ? 'font-dhivehi' : ''"
                                            >
                                                ({{ language.native_name }})
                                            </span>
                                            <UBadge
                                                v-if="language.is_default"
                                                color="primary"
                                                variant="subtle"
                                                size="xs"
                                            >
                                                Default
                                            </UBadge>
                                            <UBadge
                                                v-if="!language.is_active"
                                                color="neutral"
                                                variant="subtle"
                                                size="xs"
                                            >
                                                Inactive
                                            </UBadge>
                                        </div>
                                        <div class="flex items-center gap-3 text-sm text-muted mt-1">
                                            <span class="flex items-center gap-1">
                                                <UIcon
                                                    :name="language.direction === 'rtl' ? 'i-lucide-arrow-right-left' : 'i-lucide-arrow-left-right'"
                                                    class="size-3.5"
                                                />
                                                {{ language.direction.toUpperCase() }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <UIcon name="i-lucide-file-text" class="size-3.5" />
                                                {{ language.posts_count }} {{ language.posts_count === 1 ? 'post' : 'posts' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <UButton
                                        v-if="!language.is_default && language.is_active"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        @click="setAsDefault(language)"
                                    >
                                        Set as Default
                                    </UButton>
                                    <UButton
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        @click="toggleActive(language)"
                                        :disabled="language.is_default && language.is_active"
                                    >
                                        {{ language.is_active ? 'Deactivate' : 'Activate' }}
                                    </UButton>
                                    <UButton
                                        icon="i-lucide-pencil"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        @click="openEditModal(language)"
                                    />
                                    <UButton
                                        icon="i-lucide-trash"
                                        color="error"
                                        variant="ghost"
                                        size="sm"
                                        :disabled="language.is_default || language.posts_count > 0"
                                        @click="openDeleteModal(language)"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="languages.length === 0"
                            class="text-center py-12 border-2 border-dashed border-default rounded-xl"
                        >
                            <UIcon name="i-lucide-languages" class="size-12 text-muted mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-highlighted mb-1">No languages configured</h3>
                            <p class="text-muted mb-4">Add your first language to start creating content.</p>
                            <UButton @click="openAddModal">Add Language</UButton>
                        </div>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Add Language Modal -->
        <UModal v-model:open="addLanguageModal">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Add Language</h3>

                    <UForm :state="newLanguageForm" class="space-y-4" @submit="addLanguage">
                        <div class="grid grid-cols-2 gap-4">
                            <UFormField label="Code" name="code" :error="newLanguageForm.errors.code" required>
                                <UInput
                                    v-model="newLanguageForm.code"
                                    placeholder="e.g., en, dv, ar"
                                    maxlength="10"
                                    class="w-full"
                                    :disabled="newLanguageForm.processing"
                                />
                            </UFormField>

                            <UFormField label="Direction" name="direction" :error="newLanguageForm.errors.direction" required>
                                <USelectMenu
                                    v-model="newLanguageForm.direction"
                                    :items="directionOptions"
                                    value-key="value"
                                    class="w-full"
                                    :disabled="newLanguageForm.processing"
                                />
                            </UFormField>
                        </div>

                        <UFormField label="Name" name="name" :error="newLanguageForm.errors.name" required>
                            <UInput
                                v-model="newLanguageForm.name"
                                placeholder="e.g., English, Dhivehi"
                                class="w-full"
                                :disabled="newLanguageForm.processing"
                            />
                        </UFormField>

                        <UFormField label="Native Name" name="native_name" :error="newLanguageForm.errors.native_name" required>
                            <UInput
                                v-model="newLanguageForm.native_name"
                                placeholder="e.g., English, ދިވެހި"
                                :dir="newLanguageForm.direction"
                                :class="['w-full', newLanguageForm.direction === 'rtl' ? 'font-dhivehi text-right' : '']"
                                :disabled="newLanguageForm.processing"
                            />
                        </UFormField>

                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <UCheckbox v-model="newLanguageForm.is_active" :disabled="newLanguageForm.processing" />
                                <span class="text-sm">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <UCheckbox v-model="newLanguageForm.is_default" :disabled="newLanguageForm.processing" />
                                <span class="text-sm">Set as default</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-6">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                :disabled="newLanguageForm.processing"
                                @click="addLanguageModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :loading="newLanguageForm.processing"
                            >
                                Create
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Language Modal -->
        <UModal v-model:open="editLanguageModal">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Edit Language</h3>

                    <UForm :state="editLanguageForm" class="space-y-4" @submit="updateLanguage">
                        <div class="grid grid-cols-2 gap-4">
                            <UFormField label="Code" name="code" :error="editLanguageForm.errors.code" required>
                                <UInput
                                    v-model="editLanguageForm.code"
                                    placeholder="e.g., en, dv, ar"
                                    maxlength="10"
                                    class="w-full"
                                    :disabled="editLanguageForm.processing"
                                />
                            </UFormField>

                            <UFormField label="Direction" name="direction" :error="editLanguageForm.errors.direction" required>
                                <USelectMenu
                                    v-model="editLanguageForm.direction"
                                    :items="directionOptions"
                                    value-key="value"
                                    class="w-full"
                                    :disabled="editLanguageForm.processing"
                                />
                            </UFormField>
                        </div>

                        <UFormField label="Name" name="name" :error="editLanguageForm.errors.name" required>
                            <UInput
                                v-model="editLanguageForm.name"
                                placeholder="e.g., English, Dhivehi"
                                class="w-full"
                                :disabled="editLanguageForm.processing"
                            />
                        </UFormField>

                        <UFormField label="Native Name" name="native_name" :error="editLanguageForm.errors.native_name" required>
                            <UInput
                                v-model="editLanguageForm.native_name"
                                placeholder="e.g., English, ދිވެހި"
                                :dir="editLanguageForm.direction"
                                :class="['w-full', editLanguageForm.direction === 'rtl' ? 'font-dhivehi text-right' : '']"
                                :disabled="editLanguageForm.processing"
                            />
                        </UFormField>

                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <UCheckbox
                                    v-model="editLanguageForm.is_active"
                                    :disabled="editLanguageForm.processing || languageToEdit?.is_default"
                                />
                                <span class="text-sm">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <UCheckbox v-model="editLanguageForm.is_default" :disabled="editLanguageForm.processing" />
                                <span class="text-sm">Set as default</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-6">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                :disabled="editLanguageForm.processing"
                                @click="editLanguageModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :loading="editLanguageForm.processing"
                            >
                                Save
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>

        <!-- Delete Language Modal -->
        <UModal v-model:open="deleteLanguageModal">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Delete Language</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong>{{ languageToDelete?.name }}</strong>?
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-6">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            @click="deleteLanguageModal = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="error"
                            @click="deleteLanguage"
                        >
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
