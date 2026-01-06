<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import type { BreadcrumbItem } from '@nuxt/ui';

interface SeoSetting {
    id: number;
    route_name: string;
    page_type: string;
    meta_title: Record<string, string> | null;
    meta_description: Record<string, string> | null;
    meta_keywords: string[] | null;
    og_title: Record<string, string> | null;
    og_description: Record<string, string> | null;
    og_image: string | null;
    og_type: string | null;
    twitter_card: string | null;
    twitter_title: Record<string, string> | null;
    twitter_description: Record<string, string> | null;
    twitter_image: string | null;
    canonical_url: string | null;
    robots: string | null;
    json_ld: Record<string, unknown> | null;
    is_active: boolean;
    updated_by: number | null;
    updated_by_user: { name: string } | null;
    created_at: string;
    updated_at: string;
}

interface SelectOption {
    value: string;
    label: string;
}

const props = defineProps<{
    settings: SeoSetting[];
    pageTypes: SelectOption[];
    robotsOptions: SelectOption[];
    ogTypes: SelectOption[];
    twitterCardTypes: SelectOption[];
}>();

const addModal = ref(false);
const editModal = ref(false);
const deleteModal = ref(false);
const settingToEdit = ref<SeoSetting | null>(null);
const settingToDelete = ref<SeoSetting | null>(null);
const activeTab = ref('meta');

const newForm = useForm({
    route_name: '',
    page_type: 'static',
    meta_title: { en: '' } as Record<string, string>,
    meta_description: { en: '' } as Record<string, string>,
    meta_keywords: '' as string,
    og_title: { en: '' } as Record<string, string>,
    og_description: { en: '' } as Record<string, string>,
    og_image: '',
    og_type: 'website',
    twitter_card: 'summary_large_image',
    twitter_title: { en: '' } as Record<string, string>,
    twitter_description: { en: '' } as Record<string, string>,
    twitter_image: '',
    canonical_url: '',
    robots: 'index,follow',
    is_active: true,
});

const editForm = useForm({
    route_name: '',
    page_type: 'static',
    meta_title: { en: '' } as Record<string, string>,
    meta_description: { en: '' } as Record<string, string>,
    meta_keywords: '' as string,
    og_title: { en: '' } as Record<string, string>,
    og_description: { en: '' } as Record<string, string>,
    og_image: '',
    og_type: 'website',
    twitter_card: 'summary_large_image',
    twitter_title: { en: '' } as Record<string, string>,
    twitter_description: { en: '' } as Record<string, string>,
    twitter_image: '',
    canonical_url: '',
    robots: 'index,follow',
    is_active: true,
});

const tabs = [
    { label: 'Meta Tags', value: 'meta', icon: 'i-lucide-file-text' },
    { label: 'Open Graph', value: 'og', icon: 'i-lucide-share-2' },
    { label: 'Twitter', value: 'twitter', icon: 'i-lucide-twitter' },
    { label: 'Advanced', value: 'advanced', icon: 'i-lucide-settings' },
];

function openAddModal() {
    newForm.reset();
    activeTab.value = 'meta';
    addModal.value = true;
}

function addSetting() {
    newForm.post('/cms/seo-settings', {
        onSuccess: () => {
            addModal.value = false;
        },
    });
}

function openEditModal(setting: SeoSetting) {
    settingToEdit.value = setting;
    activeTab.value = 'meta';
    editForm.route_name = setting.route_name;
    editForm.page_type = setting.page_type;
    editForm.meta_title = setting.meta_title || { en: '' };
    editForm.meta_description = setting.meta_description || { en: '' };
    editForm.meta_keywords = setting.meta_keywords?.join(', ') || '';
    editForm.og_title = setting.og_title || { en: '' };
    editForm.og_description = setting.og_description || { en: '' };
    editForm.og_image = setting.og_image || '';
    editForm.og_type = setting.og_type || 'website';
    editForm.twitter_card = setting.twitter_card || 'summary_large_image';
    editForm.twitter_title = setting.twitter_title || { en: '' };
    editForm.twitter_description = setting.twitter_description || { en: '' };
    editForm.twitter_image = setting.twitter_image || '';
    editForm.canonical_url = setting.canonical_url || '';
    editForm.robots = setting.robots || 'index,follow';
    editForm.is_active = setting.is_active;
    editModal.value = true;
}

function updateSetting() {
    if (!settingToEdit.value) return;

    editForm.put(`/cms/seo-settings/${settingToEdit.value.id}`, {
        onSuccess: () => {
            editModal.value = false;
            settingToEdit.value = null;
        },
    });
}

function openDeleteModal(setting: SeoSetting) {
    settingToDelete.value = setting;
    deleteModal.value = true;
}

function deleteSetting() {
    if (!settingToDelete.value) return;

    router.delete(`/cms/seo-settings/${settingToDelete.value.id}`, {
        onSuccess: () => {
            deleteModal.value = false;
            settingToDelete.value = null;
        },
    });
}

function toggleActive(setting: SeoSetting) {
    router.put(`/cms/seo-settings/${setting.id}`, {
        ...setting,
        is_active: !setting.is_active,
    }, {
        preserveScroll: true,
    });
}

function getPageTypeLabel(type: string): string {
    const option = props.pageTypes.find(t => t.value === type);
    return option?.label || type;
}

function getPageTypeColor(type: string): 'primary' | 'success' | 'warning' | 'info' {
    const colors: Record<string, 'primary' | 'success' | 'warning' | 'info'> = {
        static: 'primary',
        dynamic: 'success',
        archive: 'info',
    };
    return colors[type] || 'primary';
}

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Settings', to: '/cms/settings' },
    { label: 'SEO Settings' },
];

const groupedSettings = computed(() => {
    const groups: Record<string, SeoSetting[]> = {};
    for (const setting of props.settings) {
        const type = setting.page_type;
        if (!groups[type]) {
            groups[type] = [];
        }
        groups[type].push(setting);
    }
    return groups;
});
</script>

<template>
    <Head title="SEO Settings" />

    <DashboardLayout>
        <UDashboardPanel id="settings-seo" :ui="{ body: 'lg:py-12' }">
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
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-4xl mx-auto">
                    <UPageCard
                        title="Page SEO Settings"
                        description="Configure SEO metadata for specific pages and routes. These settings override the default site SEO."
                        variant="naked"
                        orientation="horizontal"
                        class="mb-4"
                    >
                        <div class="flex gap-2 lg:ms-auto">
                            <UButton @click="openAddModal">
                                <UIcon name="i-lucide-plus" class="size-4 mr-1" />
                                Add Page SEO
                            </UButton>
                        </div>
                    </UPageCard>

                    <!-- Settings List by Type -->
                    <div v-for="(typeSettings, pageType) in groupedSettings" :key="pageType" class="space-y-4">
                        <h3 class="text-sm font-medium text-muted uppercase tracking-wider">
                            {{ getPageTypeLabel(pageType) }}
                        </h3>

                        <div
                            v-for="setting in typeSettings"
                            :key="setting.id"
                            class="border border-default rounded-xl overflow-hidden"
                        >
                            <div class="p-4 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center justify-center size-12 rounded-lg bg-elevated">
                                        <UIcon name="i-lucide-search" class="size-5 text-muted" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-semibold text-highlighted">{{ setting.route_name }}</h4>
                                            <UBadge
                                                :color="getPageTypeColor(setting.page_type)"
                                                variant="subtle"
                                                size="xs"
                                            >
                                                {{ getPageTypeLabel(setting.page_type) }}
                                            </UBadge>
                                            <UBadge
                                                v-if="!setting.is_active"
                                                color="neutral"
                                                variant="subtle"
                                                size="xs"
                                            >
                                                Inactive
                                            </UBadge>
                                        </div>
                                        <div class="text-sm text-muted mt-1">
                                            <span v-if="setting.meta_title?.en">{{ setting.meta_title.en }}</span>
                                            <span v-else class="italic">No title set</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <UButton
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        @click="toggleActive(setting)"
                                    >
                                        {{ setting.is_active ? 'Deactivate' : 'Activate' }}
                                    </UButton>
                                    <UButton
                                        icon="i-lucide-pencil"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        @click="openEditModal(setting)"
                                    />
                                    <UButton
                                        icon="i-lucide-trash"
                                        color="error"
                                        variant="ghost"
                                        size="sm"
                                        @click="openDeleteModal(setting)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="settings.length === 0"
                        class="text-center py-12 border-2 border-dashed border-default rounded-xl"
                    >
                        <UIcon name="i-lucide-search" class="size-12 text-muted mx-auto mb-4" />
                        <h3 class="text-lg font-medium text-highlighted mb-1">No SEO settings configured</h3>
                        <p class="text-muted mb-4">Add your first page SEO configuration to override default settings.</p>
                        <UButton @click="openAddModal">Add Page SEO</UButton>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Add SEO Setting Modal -->
        <UModal v-model:open="addModal" :ui="{ content: 'max-w-2xl' }">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Add Page SEO</h3>

                    <UForm :state="newForm" class="space-y-4" @submit="addSetting">
                        <!-- Basic Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <UFormField label="Route Name" name="route_name" :error="newForm.errors.route_name" required>
                                <UInput
                                    v-model="newForm.route_name"
                                    placeholder="e.g., homepage, about, contact"
                                    class="w-full"
                                    :disabled="newForm.processing"
                                />
                            </UFormField>

                            <UFormField label="Page Type" name="page_type" :error="newForm.errors.page_type" required>
                                <USelectMenu
                                    v-model="newForm.page_type"
                                    :items="pageTypes"
                                    value-key="value"
                                    class="w-full"
                                    :disabled="newForm.processing"
                                />
                            </UFormField>
                        </div>

                        <!-- Tabs -->
                        <UTabs :items="tabs" v-model="activeTab" class="mt-4">
                            <template #content="{ item }">
                                <div class="pt-4 space-y-4">
                                    <!-- Meta Tags Tab -->
                                    <template v-if="item.value === 'meta'">
                                        <UFormField label="Meta Title" name="meta_title" :error="newForm.errors['meta_title.en']">
                                            <UInput
                                                v-model="newForm.meta_title.en"
                                                placeholder="Page title (max 70 characters)"
                                                maxlength="70"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                            <template #hint>
                                                <span :class="(newForm.meta_title.en?.length || 0) > 60 ? 'text-warning' : ''">
                                                    {{ newForm.meta_title.en?.length || 0 }}/70
                                                </span>
                                            </template>
                                        </UFormField>

                                        <UFormField label="Meta Description" name="meta_description" :error="newForm.errors['meta_description.en']">
                                            <UTextarea
                                                v-model="newForm.meta_description.en"
                                                placeholder="Page description (max 160 characters)"
                                                :rows="3"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                            <template #hint>
                                                <span :class="(newForm.meta_description.en?.length || 0) > 150 ? 'text-warning' : ''">
                                                    {{ newForm.meta_description.en?.length || 0 }}/160
                                                </span>
                                            </template>
                                        </UFormField>

                                        <UFormField label="Meta Keywords" name="meta_keywords" :error="newForm.errors.meta_keywords">
                                            <UInput
                                                v-model="newForm.meta_keywords"
                                                placeholder="keyword1, keyword2, keyword3"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>
                                    </template>

                                    <!-- Open Graph Tab -->
                                    <template v-if="item.value === 'og'">
                                        <UFormField label="OG Title" name="og_title" :error="newForm.errors['og_title.en']">
                                            <UInput
                                                v-model="newForm.og_title.en"
                                                placeholder="Open Graph title"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="OG Description" name="og_description" :error="newForm.errors['og_description.en']">
                                            <UTextarea
                                                v-model="newForm.og_description.en"
                                                placeholder="Open Graph description"
                                                :rows="3"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="OG Image URL" name="og_image" :error="newForm.errors.og_image">
                                            <UInput
                                                v-model="newForm.og_image"
                                                placeholder="https://example.com/image.jpg"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="OG Type" name="og_type" :error="newForm.errors.og_type">
                                            <USelectMenu
                                                v-model="newForm.og_type"
                                                :items="ogTypes"
                                                value-key="value"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>
                                    </template>

                                    <!-- Twitter Tab -->
                                    <template v-if="item.value === 'twitter'">
                                        <UFormField label="Twitter Card Type" name="twitter_card" :error="newForm.errors.twitter_card">
                                            <USelectMenu
                                                v-model="newForm.twitter_card"
                                                :items="twitterCardTypes"
                                                value-key="value"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="Twitter Title" name="twitter_title" :error="newForm.errors['twitter_title.en']">
                                            <UInput
                                                v-model="newForm.twitter_title.en"
                                                placeholder="Twitter card title"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="Twitter Description" name="twitter_description" :error="newForm.errors['twitter_description.en']">
                                            <UTextarea
                                                v-model="newForm.twitter_description.en"
                                                placeholder="Twitter card description"
                                                :rows="3"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="Twitter Image URL" name="twitter_image" :error="newForm.errors.twitter_image">
                                            <UInput
                                                v-model="newForm.twitter_image"
                                                placeholder="https://example.com/twitter-image.jpg"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>
                                    </template>

                                    <!-- Advanced Tab -->
                                    <template v-if="item.value === 'advanced'">
                                        <UFormField label="Canonical URL" name="canonical_url" :error="newForm.errors.canonical_url">
                                            <UInput
                                                v-model="newForm.canonical_url"
                                                placeholder="https://example.com/page"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="Robots Directive" name="robots" :error="newForm.errors.robots">
                                            <USelectMenu
                                                v-model="newForm.robots"
                                                :items="robotsOptions"
                                                value-key="value"
                                                class="w-full"
                                                :disabled="newForm.processing"
                                            />
                                        </UFormField>

                                        <div class="flex items-center gap-2">
                                            <UCheckbox v-model="newForm.is_active" :disabled="newForm.processing" />
                                            <span class="text-sm">Active</span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </UTabs>

                        <div class="flex justify-end gap-2 pt-6 border-t border-default">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                :disabled="newForm.processing"
                                @click="addModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :loading="newForm.processing"
                            >
                                Create
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>

        <!-- Edit SEO Setting Modal -->
        <UModal v-model:open="editModal" :ui="{ content: 'max-w-2xl' }">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Edit Page SEO</h3>

                    <UForm :state="editForm" class="space-y-4" @submit="updateSetting">
                        <!-- Basic Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <UFormField label="Route Name" name="route_name" :error="editForm.errors.route_name" required>
                                <UInput
                                    v-model="editForm.route_name"
                                    placeholder="e.g., homepage, about, contact"
                                    class="w-full"
                                    :disabled="editForm.processing"
                                />
                            </UFormField>

                            <UFormField label="Page Type" name="page_type" :error="editForm.errors.page_type" required>
                                <USelectMenu
                                    v-model="editForm.page_type"
                                    :items="pageTypes"
                                    value-key="value"
                                    class="w-full"
                                    :disabled="editForm.processing"
                                />
                            </UFormField>
                        </div>

                        <!-- Tabs -->
                        <UTabs :items="tabs" v-model="activeTab" class="mt-4">
                            <template #content="{ item }">
                                <div class="pt-4 space-y-4">
                                    <!-- Meta Tags Tab -->
                                    <template v-if="item.value === 'meta'">
                                        <UFormField label="Meta Title" name="meta_title" :error="editForm.errors['meta_title.en']">
                                            <UInput
                                                v-model="editForm.meta_title.en"
                                                placeholder="Page title (max 70 characters)"
                                                maxlength="70"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                            <template #hint>
                                                <span :class="(editForm.meta_title.en?.length || 0) > 60 ? 'text-warning' : ''">
                                                    {{ editForm.meta_title.en?.length || 0 }}/70
                                                </span>
                                            </template>
                                        </UFormField>

                                        <UFormField label="Meta Description" name="meta_description" :error="editForm.errors['meta_description.en']">
                                            <UTextarea
                                                v-model="editForm.meta_description.en"
                                                placeholder="Page description (max 160 characters)"
                                                :rows="3"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                            <template #hint>
                                                <span :class="(editForm.meta_description.en?.length || 0) > 150 ? 'text-warning' : ''">
                                                    {{ editForm.meta_description.en?.length || 0 }}/160
                                                </span>
                                            </template>
                                        </UFormField>

                                        <UFormField label="Meta Keywords" name="meta_keywords" :error="editForm.errors.meta_keywords">
                                            <UInput
                                                v-model="editForm.meta_keywords"
                                                placeholder="keyword1, keyword2, keyword3"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>
                                    </template>

                                    <!-- Open Graph Tab -->
                                    <template v-if="item.value === 'og'">
                                        <UFormField label="OG Title" name="og_title" :error="editForm.errors['og_title.en']">
                                            <UInput
                                                v-model="editForm.og_title.en"
                                                placeholder="Open Graph title"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="OG Description" name="og_description" :error="editForm.errors['og_description.en']">
                                            <UTextarea
                                                v-model="editForm.og_description.en"
                                                placeholder="Open Graph description"
                                                :rows="3"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="OG Image URL" name="og_image" :error="editForm.errors.og_image">
                                            <UInput
                                                v-model="editForm.og_image"
                                                placeholder="https://example.com/image.jpg"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="OG Type" name="og_type" :error="editForm.errors.og_type">
                                            <USelectMenu
                                                v-model="editForm.og_type"
                                                :items="ogTypes"
                                                value-key="value"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>
                                    </template>

                                    <!-- Twitter Tab -->
                                    <template v-if="item.value === 'twitter'">
                                        <UFormField label="Twitter Card Type" name="twitter_card" :error="editForm.errors.twitter_card">
                                            <USelectMenu
                                                v-model="editForm.twitter_card"
                                                :items="twitterCardTypes"
                                                value-key="value"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="Twitter Title" name="twitter_title" :error="editForm.errors['twitter_title.en']">
                                            <UInput
                                                v-model="editForm.twitter_title.en"
                                                placeholder="Twitter card title"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="Twitter Description" name="twitter_description" :error="editForm.errors['twitter_description.en']">
                                            <UTextarea
                                                v-model="editForm.twitter_description.en"
                                                placeholder="Twitter card description"
                                                :rows="3"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="Twitter Image URL" name="twitter_image" :error="editForm.errors.twitter_image">
                                            <UInput
                                                v-model="editForm.twitter_image"
                                                placeholder="https://example.com/twitter-image.jpg"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>
                                    </template>

                                    <!-- Advanced Tab -->
                                    <template v-if="item.value === 'advanced'">
                                        <UFormField label="Canonical URL" name="canonical_url" :error="editForm.errors.canonical_url">
                                            <UInput
                                                v-model="editForm.canonical_url"
                                                placeholder="https://example.com/page"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>

                                        <UFormField label="Robots Directive" name="robots" :error="editForm.errors.robots">
                                            <USelectMenu
                                                v-model="editForm.robots"
                                                :items="robotsOptions"
                                                value-key="value"
                                                class="w-full"
                                                :disabled="editForm.processing"
                                            />
                                        </UFormField>

                                        <div class="flex items-center gap-2">
                                            <UCheckbox v-model="editForm.is_active" :disabled="editForm.processing" />
                                            <span class="text-sm">Active</span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </UTabs>

                        <div class="flex justify-end gap-2 pt-6 border-t border-default">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                :disabled="editForm.processing"
                                @click="editModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :loading="editForm.processing"
                            >
                                Save
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>

        <!-- Delete Confirmation Modal -->
        <UModal v-model:open="deleteModal">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Delete SEO Setting</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete the SEO setting for <strong>{{ settingToDelete?.route_name }}</strong>?
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-6">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            @click="deleteModal = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="error"
                            @click="deleteSetting"
                        >
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
