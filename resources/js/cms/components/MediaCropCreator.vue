<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick, onUnmounted } from 'vue';
import Cropper from 'cropperjs';

interface CropPreset {
    name: string;
    label: string;
    width: number;
    height: number;
}

interface CropVersion {
    id: number;
    uuid: string;
    preset_name: string;
    preset_label: string;
    label: string | null;
    display_label: string;
    crop_x: number;
    crop_y: number;
    crop_width: number;
    crop_height: number;
    output_width: number;
    output_height: number;
    url: string | null;
    thumbnail_url: string | null;
    created_at: string;
}

const props = defineProps<{
    mediaUuid: string;
    imageUrl: string;
    imageWidth: number;
    imageHeight: number;
}>();

const emit = defineEmits<{
    'crop-created': [crop: CropVersion];
    'crop-deleted': [cropId: number];
}>();

// State
const presets = ref<CropPreset[]>([]);
const existingCrops = ref<CropVersion[]>([]);
const isLoading = ref(false);
const isSaving = ref(false);

// Modal state
const isModalOpen = ref(false);
const selectedPreset = ref<CropPreset | null>(null);
const editingCrop = ref<CropVersion | null>(null);
const cropLabel = ref('');

// Cropper refs
const imageRef = ref<HTMLImageElement | null>(null);
let cropper: Cropper | null = null;

// Computed aspect ratio for selected preset
const aspectRatio = computed(() => {
    if (!selectedPreset.value) return NaN;
    return selectedPreset.value.width / selectedPreset.value.height;
});

// Group existing crops by preset
const cropsByPreset = computed(() => {
    const grouped = new Map<string, CropVersion[]>();
    for (const crop of existingCrops.value) {
        const list = grouped.get(crop.preset_name) || [];
        list.push(crop);
        grouped.set(crop.preset_name, list);
    }
    return grouped;
});

function getCsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

// Load existing crops and presets
async function loadCrops() {
    isLoading.value = true;
    try {
        const response = await fetch(`/cms/media/${props.mediaUuid}/crops`, {
            headers: {
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        presets.value = data.presets || [];
        existingCrops.value = data.crops || [];
    } catch (error) {
        console.error('Failed to load crops:', error);
    }
    isLoading.value = false;
}

// Open modal to create a new crop
function openCreateModal(preset: CropPreset) {
    selectedPreset.value = preset;
    editingCrop.value = null;
    // Auto-prefill label with preset label
    cropLabel.value = preset.label;
    isModalOpen.value = true;

    nextTick(() => {
        initCropper();
    });
}

// Open modal to edit existing crop
function openEditModal(crop: CropVersion) {
    const preset = presets.value.find(p => p.name === crop.preset_name);
    if (!preset) return;

    selectedPreset.value = preset;
    editingCrop.value = crop;
    cropLabel.value = crop.label || crop.preset_label;
    isModalOpen.value = true;

    nextTick(() => {
        initCropper();

        // Set crop box to existing coordinates after cropper is ready
        // Wait for cropper to be fully initialized
        setTimeout(() => {
            if (cropper) {
                const imageData = cropper.getImageData();
                cropper.setData({
                    x: (crop.crop_x / 100) * imageData.naturalWidth,
                    y: (crop.crop_y / 100) * imageData.naturalHeight,
                    width: (crop.crop_width / 100) * imageData.naturalWidth,
                    height: (crop.crop_height / 100) * imageData.naturalHeight,
                });
            }
        }, 100);
    });
}

// Initialize cropper when image loads
function initCropper() {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }

    if (!imageRef.value || !selectedPreset.value) return;

    cropper = new Cropper(imageRef.value, {
        viewMode: 1,
        aspectRatio: aspectRatio.value,
        autoCropArea: 0.8,
        responsive: true,
        guides: true,
        center: true,
        highlight: true,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
        ready() {
            // Cropper is ready
        },
    });
}

// Close modal
function closeModal() {
    isModalOpen.value = false;
    selectedPreset.value = null;
    editingCrop.value = null;
    cropLabel.value = '';

    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
}

// Save crop
async function saveCrop() {
    if (!cropper || !selectedPreset.value) return;

    const cropData = cropper.getData();
    const imageData = cropper.getImageData();

    // Convert to percentages
    const cropX = (cropData.x / imageData.naturalWidth) * 100;
    const cropY = (cropData.y / imageData.naturalHeight) * 100;
    const cropWidth = (cropData.width / imageData.naturalWidth) * 100;
    const cropHeight = (cropData.height / imageData.naturalHeight) * 100;

    isSaving.value = true;
    try {
        const isUpdate = editingCrop.value !== null;
        const url = isUpdate
            ? `/cms/media/${props.mediaUuid}/crops/${editingCrop.value.uuid}`
            : `/cms/media/${props.mediaUuid}/crops`;

        const response = await fetch(url, {
            method: isUpdate ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                preset_name: selectedPreset.value.name,
                label: cropLabel.value || null,
                crop_x: cropX,
                crop_y: cropY,
                crop_width: cropWidth,
                crop_height: cropHeight,
            }),
        });

        const data = await response.json();

        if (data.success && data.crop) {
            if (isUpdate) {
                // Update in list
                const index = existingCrops.value.findIndex(c => c.id === data.crop.id);
                if (index >= 0) {
                    existingCrops.value[index] = data.crop;
                }
            } else {
                // Add to list
                existingCrops.value.unshift(data.crop);
            }

            emit('crop-created', data.crop);

            // Close modal
            closeModal();
        }
    } catch (error) {
        console.error('Failed to save crop:', error);
    }
    isSaving.value = false;
}

// Delete crop
async function deleteCrop(crop: CropVersion) {
    if (!confirm('Delete this crop version?')) return;

    try {
        const response = await fetch(`/cms/media/${props.mediaUuid}/crops/${crop.uuid}`, {
            method: 'DELETE',
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            existingCrops.value = existingCrops.value.filter(c => c.id !== crop.id);
            emit('crop-deleted', crop.id);
        }
    } catch (error) {
        console.error('Failed to delete crop:', error);
    }
}

// Count crops for a preset
function getCropCount(presetName: string): number {
    return existingCrops.value.filter(c => c.preset_name === presetName).length;
}

// Format dimensions
function formatDimensions(width: number, height: number): string {
    return `${width} × ${height}`;
}

// Cleanup on unmount
onUnmounted(() => {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
});

// Load crops when component mounts
onMounted(() => {
    loadCrops();
});

// Reload when media changes
watch(() => props.mediaUuid, () => {
    closeModal();
    loadCrops();
});

// Handle modal close
watch(isModalOpen, (open) => {
    if (!open && cropper) {
        cropper.destroy();
        cropper = null;
    }
});
</script>

<template>
    <div class="space-y-4">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-12">
            <UIcon name="i-lucide-loader-2" class="size-8 text-muted animate-spin" />
        </div>

        <template v-else>
            <!-- Preset Buttons -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-medium text-highlighted">Create New Crop</h4>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="preset in presets"
                        :key="preset.name"
                        type="button"
                        class="flex items-center justify-between p-3 text-left rounded-lg border border-default bg-elevated/50 hover:border-primary hover:bg-primary/5 transition-colors"
                        @click="openCreateModal(preset)"
                    >
                        <div>
                            <p class="text-sm font-medium text-highlighted">{{ preset.label }}</p>
                            <p class="text-xs text-muted">{{ formatDimensions(preset.width, preset.height) }}</p>
                        </div>
                        <UBadge
                            v-if="getCropCount(preset.name) > 0"
                            color="primary"
                            size="xs"
                        >
                            {{ getCropCount(preset.name) }}
                        </UBadge>
                    </button>
                </div>
            </div>

            <!-- Existing Crops List -->
            <div v-if="existingCrops.length > 0" class="pt-4 border-t border-default">
                <h4 class="text-sm font-medium text-highlighted mb-3">
                    Existing Crops ({{ existingCrops.length }})
                </h4>
                <div class="grid grid-cols-2 gap-2">
                    <div
                        v-for="crop in existingCrops"
                        :key="crop.id"
                        class="relative group rounded-lg border border-default overflow-hidden bg-elevated/50"
                    >
                        <div class="aspect-video bg-muted/30">
                            <img
                                v-if="crop.thumbnail_url"
                                :src="crop.thumbnail_url"
                                :alt="crop.display_label"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <UIcon name="i-lucide-image" class="size-6 text-muted" />
                            </div>
                        </div>
                        <div class="p-2">
                            <p class="text-xs font-medium text-highlighted truncate">{{ crop.display_label }}</p>
                            <p class="text-[10px] text-muted">{{ formatDimensions(crop.output_width, crop.output_height) }}</p>
                        </div>

                        <!-- Hover Actions -->
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                            <UButton
                                icon="i-lucide-edit"
                                color="primary"
                                variant="solid"
                                size="xs"
                                @click="openEditModal(crop)"
                            />
                            <UButton
                                icon="i-lucide-trash"
                                color="error"
                                variant="solid"
                                size="xs"
                                @click="deleteCrop(crop)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="pt-4 border-t border-default">
                <div class="text-center py-6 text-muted">
                    <UIcon name="i-lucide-crop" class="size-8 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">No crops created yet</p>
                    <p class="text-xs">Select a preset above to create your first crop</p>
                </div>
            </div>
        </template>

        <!-- Crop Modal -->
        <UModal v-model:open="isModalOpen" :ui="{ width: 'max-w-4xl' }">
            <template #content>
                <div class="flex flex-col h-full bg-default">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-default">
                        <div>
                            <h2 class="text-lg font-semibold text-highlighted">
                                {{ editingCrop ? 'Edit' : 'Create' }} {{ selectedPreset?.label }} Crop
                            </h2>
                            <p class="text-sm text-muted mt-0.5">
                                Output: {{ selectedPreset ? formatDimensions(selectedPreset.width, selectedPreset.height) : '' }}
                            </p>
                        </div>
                        <UButton
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            @click="closeModal"
                        />
                    </div>

                    <!-- Modal Body -->
                    <div class="flex-1 overflow-y-auto p-6">
                        <!-- Label Input -->
                        <div class="mb-4">
                            <UFormField label="Label" hint="Name this crop version for easy identification">
                                <UInput
                                    v-model="cropLabel"
                                    placeholder="e.g., Homepage hero, Sidebar thumbnail"
                                    class="w-full"
                                />
                            </UFormField>
                        </div>

                        <!-- Cropper Container -->
                        <div class="border border-default rounded-lg overflow-hidden bg-muted/30">
                            <div class="max-h-[60vh]">
                                <img
                                    ref="imageRef"
                                    :src="imageUrl"
                                    alt="Crop preview"
                                    class="max-w-full block"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-default">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            @click="closeModal"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="primary"
                            :loading="isSaving"
                            @click="saveCrop"
                        >
                            {{ editingCrop ? 'Update' : 'Create' }} Crop
                        </UButton>
                    </div>
                </div>
            </template>
        </UModal>
    </div>
</template>

<style>
/* Cropper.js custom styling */
.cropper-container {
    max-height: 60vh;
}

.cropper-view-box,
.cropper-face {
    border-radius: 0;
}

.cropper-view-box {
    outline: 2px solid var(--color-primary-500);
}

.cropper-line {
    background-color: var(--color-primary-500);
}

.cropper-point {
    background-color: var(--color-primary-500);
    width: 10px;
    height: 10px;
}

.cropper-dashed {
    border-color: rgba(255, 255, 255, 0.5);
}
</style>
