<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useSettingsNav } from '../../composables/useSettingsNav';

const toast = useToast();

const { mainNav: settingsNav } = useSettingsNav();

interface CropPreset {
    name: string;
    label: string;
    width: number;
    height: number;
}

interface MediaCategory {
    slug: string;
    label: string;
}

const props = defineProps<{
    cropPresets: CropPreset[];
    defaultCropPresets: CropPreset[];
    mediaCategories: MediaCategory[];
    defaultMediaCategories: MediaCategory[];
}>();

const form = useForm({
    crop_presets: [...props.cropPresets],
    media_categories: [...props.mediaCategories],
});

const editingIndex = ref<number | null>(null);
const editingPreset = ref<CropPreset | null>(null);

// Media Categories
const editingCategoryIndex = ref<number | null>(null);
const editingCategory = ref<MediaCategory | null>(null);

// Helper to generate slug from label
function slugify(text: string): string {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '') // Remove special characters
        .replace(/[\s-]+/g, '_')   // Replace spaces/hyphens with underscores
        .replace(/^_+|_+$/g, '');  // Remove leading/trailing underscores
}

// Auto-generate slug when label changes (for categories)
// Only auto-generate if slug is empty or matches what would be auto-generated from previous label
watch(
    () => editingCategory.value?.label,
    (newLabel, oldLabel) => {
        if (editingCategory.value && newLabel) {
            const currentSlug = editingCategory.value.slug;
            const oldExpectedSlug = oldLabel ? slugify(oldLabel) : '';
            // Auto-generate if slug is empty OR matches what was auto-generated from old label
            if (!currentSlug || currentSlug === oldExpectedSlug) {
                editingCategory.value.slug = slugify(newLabel);
            }
        }
    }
);

// Auto-generate name when label changes (for presets)
watch(
    () => editingPreset.value?.label,
    (newLabel, oldLabel) => {
        if (editingPreset.value && newLabel) {
            const currentName = editingPreset.value.name;
            const oldExpectedName = oldLabel ? slugify(oldLabel) : '';
            // Auto-generate if name is empty OR matches what was auto-generated from old label
            if (!currentName || currentName === oldExpectedName) {
                editingPreset.value.name = slugify(newLabel);
            }
        }
    }
);

function addCategory() {
    form.media_categories.push({
        slug: '',
        label: '',
    });
    editingCategoryIndex.value = form.media_categories.length - 1;
    editingCategory.value = { ...form.media_categories[editingCategoryIndex.value] };
}

function editCategory(index: number) {
    editingCategoryIndex.value = index;
    editingCategory.value = { ...form.media_categories[index] };
}

function saveCategory() {
    if (editingCategoryIndex.value !== null && editingCategory.value) {
        // Validate: label is required
        if (!editingCategory.value.label.trim()) {
            toast.add({
                title: 'Validation error',
                description: 'Label is required.',
                color: 'error',
            });
            return;
        }

        // Validate: slug is required
        if (!editingCategory.value.slug.trim()) {
            toast.add({
                title: 'Validation error',
                description: 'Slug is required.',
                color: 'error',
            });
            return;
        }

        // Validate: slug must be unique
        const isDuplicate = form.media_categories.some((cat, idx) =>
            idx !== editingCategoryIndex.value && cat.slug === editingCategory.value!.slug
        );
        if (isDuplicate) {
            toast.add({
                title: 'Validation error',
                description: 'A category with this slug already exists.',
                color: 'error',
            });
            return;
        }

        form.media_categories[editingCategoryIndex.value] = { ...editingCategory.value };
    }
    editingCategoryIndex.value = null;
    editingCategory.value = null;
    // Auto-save after confirming the category
    onSubmit();
}

function cancelCategoryEdit() {
    // Remove if it was a new empty category
    if (editingCategoryIndex.value !== null && !form.media_categories[editingCategoryIndex.value].slug) {
        form.media_categories.splice(editingCategoryIndex.value, 1);
    }
    editingCategoryIndex.value = null;
    editingCategory.value = null;
}

function removeCategory(index: number) {
    if (confirm('Remove this category?')) {
        form.media_categories.splice(index, 1);
    }
}

function resetCategoriesToDefaults() {
    if (confirm('Reset to default media categories? This will remove any custom categories.')) {
        form.media_categories = [...props.defaultMediaCategories];
    }
}

function addPreset() {
    form.crop_presets.push({
        name: '',
        label: '',
        width: 800,
        height: 600,
    });
    editingIndex.value = form.crop_presets.length - 1;
    editingPreset.value = { ...form.crop_presets[editingIndex.value] };
}

function editPreset(index: number) {
    editingIndex.value = index;
    editingPreset.value = { ...form.crop_presets[index] };
}

function savePreset() {
    if (editingIndex.value !== null && editingPreset.value) {
        // Validate: label is required
        if (!editingPreset.value.label.trim()) {
            toast.add({
                title: 'Validation error',
                description: 'Label is required.',
                color: 'error',
            });
            return;
        }

        // Validate: name (slug) is required
        if (!editingPreset.value.name.trim()) {
            toast.add({
                title: 'Validation error',
                description: 'Slug is required.',
                color: 'error',
            });
            return;
        }

        // Validate: name must be unique
        const isDuplicate = form.crop_presets.some((preset, idx) =>
            idx !== editingIndex.value && preset.name === editingPreset.value!.name
        );
        if (isDuplicate) {
            toast.add({
                title: 'Validation error',
                description: 'A preset with this slug already exists.',
                color: 'error',
            });
            return;
        }

        form.crop_presets[editingIndex.value] = { ...editingPreset.value };
    }
    editingIndex.value = null;
    editingPreset.value = null;
    // Auto-save after confirming the preset
    onSubmit();
}

function cancelEdit() {
    // Remove if it was a new empty preset
    if (editingIndex.value !== null && !form.crop_presets[editingIndex.value].name) {
        form.crop_presets.splice(editingIndex.value, 1);
    }
    editingIndex.value = null;
    editingPreset.value = null;
}

function removePreset(index: number) {
    if (confirm('Remove this crop preset?')) {
        form.crop_presets.splice(index, 1);
    }
}

function resetToDefaults() {
    if (confirm('Reset to default crop presets? This will remove any custom presets.')) {
        form.crop_presets = [...props.defaultCropPresets];
    }
}

function onSubmit() {
    form.put('/cms/settings/media', {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                title: 'Settings saved',
                description: 'Media settings have been updated successfully.',
                color: 'success',
            });
        },
        onError: (errors) => {
            toast.add({
                title: 'Error saving settings',
                description: 'Please check the form for errors and try again.',
                color: 'error',
            });
        },
    });
}

function formatDimensions(preset: CropPreset): string {
    return `${preset.width} x ${preset.height}`;
}

const aspectRatio = computed(() => {
    if (!editingPreset.value) return '';
    const gcd = (a: number, b: number): number => b === 0 ? a : gcd(b, a % b);
    const divisor = gcd(editingPreset.value.width, editingPreset.value.height);
    return `${editingPreset.value.width / divisor}:${editingPreset.value.height / divisor}`;
});
</script>

<template>
    <Head title="Media Settings" />

    <DashboardLayout>
        <UDashboardPanel id="media-settings" :ui="{ body: 'lg:py-12' }">
            <template #header>
                <UDashboardNavbar title="Settings">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="settingsNav" highlight class="-mx-1 flex-1 overflow-x-auto" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-2xl mx-auto">
                    <UPageCard
                        title="Crop Presets"
                        description="Define the available image crop sizes. These presets determine what image variations are generated when media is uploaded."
                        variant="naked"
                        orientation="horizontal"
                        class="mb-4"
                    >
                        <div class="flex gap-2 w-fit lg:ms-auto">
                            <UButton
                                label="Reset to Defaults"
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-rotate-ccw"
                                size="sm"
                                @click="resetToDefaults"
                            />
                        </div>
                    </UPageCard>

                    <UPageCard variant="subtle">
                        <!-- Presets Table -->
                        <div class="overflow-hidden">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-default">
                                        <th class="text-left text-xs font-medium text-muted uppercase tracking-wider py-2 px-3">Label</th>
                                        <th class="text-left text-xs font-medium text-muted uppercase tracking-wider py-2 px-3">Slug</th>
                                        <th class="text-left text-xs font-medium text-muted uppercase tracking-wider py-2 px-3">Dimensions</th>
                                        <th class="text-right text-xs font-medium text-muted uppercase tracking-wider py-2 px-3 w-24">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-default">
                                    <tr
                                        v-for="(preset, index) in form.crop_presets"
                                        :key="index"
                                        class="group hover:bg-elevated/50 transition-colors"
                                    >
                                        <template v-if="editingIndex === index && editingPreset">
                                            <!-- Edit Mode -->
                                            <td class="py-2 px-3">
                                                <UInput
                                                    v-model="editingPreset.label"
                                                    placeholder="Display Label"
                                                    size="sm"
                                                    autofocus
                                                />
                                            </td>
                                            <td class="py-2 px-3">
                                                <UInput
                                                    v-model="editingPreset.name"
                                                    placeholder="slug_name"
                                                    size="sm"
                                                    class="font-mono text-sm"
                                                />
                                            </td>
                                            <td class="py-2 px-3">
                                                <div class="flex items-center gap-2">
                                                    <UInput
                                                        v-model.number="editingPreset.width"
                                                        type="number"
                                                        :min="10"
                                                        :max="4000"
                                                        size="sm"
                                                        class="w-20"
                                                    />
                                                    <span class="text-muted">×</span>
                                                    <UInput
                                                        v-model.number="editingPreset.height"
                                                        type="number"
                                                        :min="10"
                                                        :max="4000"
                                                        size="sm"
                                                        class="w-20"
                                                    />
                                                    <span class="text-xs text-muted">({{ aspectRatio }})</span>
                                                </div>
                                            </td>
                                            <td class="py-2 px-3 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <UButton
                                                        icon="i-lucide-check"
                                                        color="success"
                                                        variant="soft"
                                                        size="xs"
                                                        @click="savePreset"
                                                    />
                                                    <UButton
                                                        icon="i-lucide-x"
                                                        color="neutral"
                                                        variant="ghost"
                                                        size="xs"
                                                        @click="cancelEdit"
                                                    />
                                                </div>
                                            </td>
                                        </template>

                                        <template v-else>
                                            <!-- View Mode -->
                                            <td class="py-3 px-3">
                                                <span class="text-sm font-medium text-highlighted">{{ preset.label }}</span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <code class="text-xs text-muted bg-muted/30 px-1.5 py-0.5 rounded">{{ preset.name }}</code>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="text-sm text-muted">{{ formatDimensions(preset) }}</span>
                                            </td>
                                            <td class="py-3 px-3 text-right">
                                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <UButton
                                                        icon="i-lucide-pencil"
                                                        color="neutral"
                                                        variant="ghost"
                                                        size="xs"
                                                        @click="editPreset(index)"
                                                    />
                                                    <UButton
                                                        icon="i-lucide-trash-2"
                                                        color="error"
                                                        variant="ghost"
                                                        size="xs"
                                                        @click="removePreset(index)"
                                                    />
                                                </div>
                                            </td>
                                        </template>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Empty State -->
                            <div
                                v-if="form.crop_presets.length === 0"
                                class="text-center py-12 text-muted"
                            >
                                <UIcon name="i-lucide-crop" class="size-10 mx-auto mb-3 opacity-40" />
                                <p class="text-sm">No crop presets defined</p>
                            </div>
                        </div>

                        <!-- Add Row -->
                        <div class="border-t border-default mt-2 pt-3">
                            <button
                                type="button"
                                class="w-full flex items-center justify-center gap-2 py-2 text-sm text-muted hover:text-highlighted hover:bg-elevated/50 rounded-lg transition-colors"
                                @click="addPreset"
                            >
                                <UIcon name="i-lucide-plus" class="size-4" />
                                Add preset
                            </button>
                        </div>
                    </UPageCard>

                    <!-- Media Categories Section -->
                    <UPageCard
                        title="Media Categories"
                        description="Define categories to organize your media library. Categories can be used to filter media and are auto-detected from filenames during upload."
                        variant="naked"
                        orientation="horizontal"
                        class="mb-4"
                    >
                        <div class="flex gap-2 w-fit lg:ms-auto">
                            <UButton
                                label="Reset to Defaults"
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-rotate-ccw"
                                size="sm"
                                @click="resetCategoriesToDefaults"
                            />
                        </div>
                    </UPageCard>

                    <UPageCard variant="subtle">
                        <!-- Categories Table -->
                        <div class="overflow-hidden">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-default">
                                        <th class="text-left text-xs font-medium text-muted uppercase tracking-wider py-2 px-3">Label</th>
                                        <th class="text-left text-xs font-medium text-muted uppercase tracking-wider py-2 px-3">Slug</th>
                                        <th class="text-right text-xs font-medium text-muted uppercase tracking-wider py-2 px-3 w-24">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-default">
                                    <tr
                                        v-for="(category, index) in form.media_categories"
                                        :key="index"
                                        class="group hover:bg-elevated/50 transition-colors"
                                    >
                                        <template v-if="editingCategoryIndex === index && editingCategory">
                                            <!-- Edit Mode -->
                                            <td class="py-2 px-3">
                                                <UInput
                                                    v-model="editingCategory.label"
                                                    placeholder="Display Label"
                                                    size="sm"
                                                    autofocus
                                                />
                                            </td>
                                            <td class="py-2 px-3">
                                                <UInput
                                                    v-model="editingCategory.slug"
                                                    placeholder="auto_generated"
                                                    size="sm"
                                                    class="font-mono text-sm"
                                                />
                                            </td>
                                            <td class="py-2 px-3 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <UButton
                                                        icon="i-lucide-check"
                                                        color="success"
                                                        variant="soft"
                                                        size="xs"
                                                        @click="saveCategory"
                                                    />
                                                    <UButton
                                                        icon="i-lucide-x"
                                                        color="neutral"
                                                        variant="ghost"
                                                        size="xs"
                                                        @click="cancelCategoryEdit"
                                                    />
                                                </div>
                                            </td>
                                        </template>

                                        <template v-else>
                                            <!-- View Mode -->
                                            <td class="py-3 px-3">
                                                <span class="text-sm font-medium text-highlighted">{{ category.label }}</span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <code class="text-xs text-muted bg-muted/30 px-1.5 py-0.5 rounded">{{ category.slug }}</code>
                                            </td>
                                            <td class="py-3 px-3 text-right">
                                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <UButton
                                                        icon="i-lucide-pencil"
                                                        color="neutral"
                                                        variant="ghost"
                                                        size="xs"
                                                        @click="editCategory(index)"
                                                    />
                                                    <UButton
                                                        icon="i-lucide-trash-2"
                                                        color="error"
                                                        variant="ghost"
                                                        size="xs"
                                                        @click="removeCategory(index)"
                                                    />
                                                </div>
                                            </td>
                                        </template>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Empty State -->
                            <div
                                v-if="form.media_categories.length === 0"
                                class="text-center py-12 text-muted"
                            >
                                <UIcon name="i-lucide-folder-open" class="size-10 mx-auto mb-3 opacity-40" />
                                <p class="text-sm">No media categories defined</p>
                            </div>
                        </div>

                        <!-- Add Row -->
                        <div class="border-t border-default mt-2 pt-3">
                            <button
                                type="button"
                                class="w-full flex items-center justify-center gap-2 py-2 text-sm text-muted hover:text-highlighted hover:bg-elevated/50 rounded-lg transition-colors"
                                @click="addCategory"
                            >
                                <UIcon name="i-lucide-plus" class="size-4" />
                                Add category
                            </button>
                        </div>
                    </UPageCard>

                    <!-- Info -->
                    <UAlert
                        color="info"
                        variant="subtle"
                        icon="i-lucide-info"
                        title="About Media Settings"
                        description="Crop presets define image variations generated during upload. Media categories help organize your library and are auto-detected from filenames (e.g., 'sponsor_logo.png' will be categorized as 'Sponsors'). Changes only affect newly uploaded media."
                    />

                    <!-- Spacer for sticky bar -->
                    <div v-if="form.isDirty" class="h-20" />
                </div>
            </template>
        </UDashboardPanel>

        <!-- Sticky Save Bar -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div
                v-if="form.isDirty"
                class="fixed bottom-0 left-0 right-0 z-50 border-t border-default bg-default/95 backdrop-blur-sm shadow-lg"
            >
                <div class="flex items-center justify-between gap-4 px-6 py-4 max-w-2xl mx-auto">
                    <div class="flex items-center gap-2 text-sm text-muted">
                        <UIcon name="i-lucide-alert-circle" class="size-4 text-warning" />
                        <span>You have unsaved changes</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            :disabled="form.processing"
                            @click="form.reset()"
                        >
                            Discard
                        </UButton>
                        <UButton
                            color="primary"
                            :loading="form.processing"
                            @click="onSubmit"
                        >
                            Save Changes
                        </UButton>
                    </div>
                </div>
            </div>
        </Transition>
    </DashboardLayout>
</template>
