<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';

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
        form.media_categories[editingCategoryIndex.value] = { ...editingCategory.value };
    }
    editingCategoryIndex.value = null;
    editingCategory.value = null;
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
        form.crop_presets[editingIndex.value] = { ...editingPreset.value };
    }
    editingIndex.value = null;
    editingPreset.value = null;
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
                <UDashboardNavbar title="Media Settings">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #right>
                        <UButton
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-arrow-left"
                            to="/cms/settings"
                        >
                            Back to Settings
                        </UButton>
                    </template>
                </UDashboardNavbar>
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
                                @click="resetToDefaults"
                            />
                            <UButton
                                label="Save Changes"
                                color="neutral"
                                :loading="form.processing"
                                @click="onSubmit"
                            />
                        </div>
                    </UPageCard>

                    <UPageCard variant="subtle">
                        <!-- Presets List -->
                        <div class="space-y-3">
                            <div
                                v-for="(preset, index) in form.crop_presets"
                                :key="index"
                                class="flex items-center justify-between gap-4 p-3 rounded-lg border border-default bg-default/50"
                            >
                                <template v-if="editingIndex === index && editingPreset">
                                    <!-- Edit Mode -->
                                    <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <UInput
                                            v-model="editingPreset.name"
                                            placeholder="slug_name"
                                            size="sm"
                                        >
                                            <template #leading>
                                                <span class="text-xs text-muted">Name</span>
                                            </template>
                                        </UInput>
                                        <UInput
                                            v-model="editingPreset.label"
                                            placeholder="Display Label"
                                            size="sm"
                                        >
                                            <template #leading>
                                                <span class="text-xs text-muted">Label</span>
                                            </template>
                                        </UInput>
                                        <UInput
                                            v-model.number="editingPreset.width"
                                            type="number"
                                            :min="10"
                                            :max="4000"
                                            size="sm"
                                        >
                                            <template #leading>
                                                <span class="text-xs text-muted">W</span>
                                            </template>
                                        </UInput>
                                        <UInput
                                            v-model.number="editingPreset.height"
                                            type="number"
                                            :min="10"
                                            :max="4000"
                                            size="sm"
                                        >
                                            <template #leading>
                                                <span class="text-xs text-muted">H</span>
                                            </template>
                                        </UInput>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs text-muted mr-2">{{ aspectRatio }}</span>
                                        <UButton
                                            icon="i-lucide-check"
                                            color="success"
                                            variant="ghost"
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
                                </template>

                                <template v-else>
                                    <!-- View Mode -->
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="shrink-0 rounded border border-default bg-muted/30 flex items-center justify-center"
                                            :style="{
                                                width: Math.min(preset.width / 20, 60) + 'px',
                                                height: Math.min(preset.height / 20, 40) + 'px',
                                            }"
                                        >
                                            <UIcon name="i-lucide-image" class="size-4 text-muted" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-highlighted">
                                                {{ preset.label }}
                                            </p>
                                            <p class="text-xs text-muted">
                                                {{ preset.name }} &middot; {{ formatDimensions(preset) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <UButton
                                            icon="i-lucide-pencil"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            @click="editPreset(index)"
                                        />
                                        <UButton
                                            icon="i-lucide-trash"
                                            color="error"
                                            variant="ghost"
                                            size="xs"
                                            @click="removePreset(index)"
                                        />
                                    </div>
                                </template>
                            </div>

                            <!-- Empty State -->
                            <div
                                v-if="form.crop_presets.length === 0"
                                class="text-center py-8 text-muted"
                            >
                                <UIcon name="i-lucide-crop" class="size-12 mx-auto mb-4 opacity-50" />
                                <p>No crop presets defined.</p>
                                <p class="text-sm">Add a preset to define available image sizes.</p>
                            </div>
                        </div>

                        <USeparator class="my-4" />

                        <!-- Add Button -->
                        <UButton
                            icon="i-lucide-plus"
                            color="neutral"
                            variant="soft"
                            block
                            @click="addPreset"
                        >
                            Add Crop Preset
                        </UButton>
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
                                @click="resetCategoriesToDefaults"
                            />
                        </div>
                    </UPageCard>

                    <UPageCard variant="subtle">
                        <!-- Categories List -->
                        <div class="space-y-3">
                            <div
                                v-for="(category, index) in form.media_categories"
                                :key="index"
                                class="flex items-center justify-between gap-4 p-3 rounded-lg border border-default bg-default/50"
                            >
                                <template v-if="editingCategoryIndex === index && editingCategory">
                                    <!-- Edit Mode -->
                                    <div class="flex-1 grid grid-cols-2 gap-3">
                                        <UInput
                                            v-model="editingCategory.slug"
                                            placeholder="category_slug"
                                            size="sm"
                                        >
                                            <template #leading>
                                                <span class="text-xs text-muted">Slug</span>
                                            </template>
                                        </UInput>
                                        <UInput
                                            v-model="editingCategory.label"
                                            placeholder="Display Label"
                                            size="sm"
                                        >
                                            <template #leading>
                                                <span class="text-xs text-muted">Label</span>
                                            </template>
                                        </UInput>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <UButton
                                            icon="i-lucide-check"
                                            color="success"
                                            variant="ghost"
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
                                </template>

                                <template v-else>
                                    <!-- View Mode -->
                                    <div class="flex items-center gap-4">
                                        <div class="shrink-0 rounded border border-default bg-muted/30 flex items-center justify-center size-10">
                                            <UIcon name="i-lucide-folder" class="size-4 text-muted" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-highlighted">
                                                {{ category.label }}
                                            </p>
                                            <p class="text-xs text-muted">
                                                {{ category.slug }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <UButton
                                            icon="i-lucide-pencil"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            @click="editCategory(index)"
                                        />
                                        <UButton
                                            icon="i-lucide-trash"
                                            color="error"
                                            variant="ghost"
                                            size="xs"
                                            @click="removeCategory(index)"
                                        />
                                    </div>
                                </template>
                            </div>

                            <!-- Empty State -->
                            <div
                                v-if="form.media_categories.length === 0"
                                class="text-center py-8 text-muted"
                            >
                                <UIcon name="i-lucide-folder" class="size-12 mx-auto mb-4 opacity-50" />
                                <p>No media categories defined.</p>
                                <p class="text-sm">Add a category to organize your media library.</p>
                            </div>
                        </div>

                        <USeparator class="my-4" />

                        <!-- Add Button -->
                        <UButton
                            icon="i-lucide-plus"
                            color="neutral"
                            variant="soft"
                            block
                            @click="addCategory"
                        >
                            Add Media Category
                        </UButton>
                    </UPageCard>

                    <!-- Info -->
                    <UAlert
                        color="info"
                        variant="subtle"
                        icon="i-lucide-info"
                        title="About Media Settings"
                        description="Crop presets define image variations generated during upload. Media categories help organize your library and are auto-detected from filenames (e.g., 'sponsor_logo.png' will be categorized as 'Sponsors'). Changes only affect newly uploaded media."
                    />
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
