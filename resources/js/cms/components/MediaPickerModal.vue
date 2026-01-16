<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import BlurHashImage from './BlurHashImage.vue';

interface CropVersion {
    id: number;
    uuid: string;
    preset_name: string;
    preset_label: string;
    label: string | null;
    display_label: string;
    output_width: number;
    output_height: number;
    url: string | null;
    thumbnail_url: string | null;
}

interface MediaItem {
    id: number;
    uuid: string;
    type: 'image' | 'video_local' | 'video_embed';
    url: string | null;
    thumbnail_url: string | null;
    blurhash: string | null;
    title: string | null;
    alt_text: string | null;
    caption?: string | null;
    credit_display?: {
        name: string;
        url: string | null;
        role: string | null;
    } | null;
    is_image: boolean;
    is_video: boolean;
    has_crops?: boolean;
    crops?: CropVersion[];
    // Selected crop info (added when a crop version is selected)
    crop_version?: {
        id: number;
        uuid: string;
        preset_name: string;
        preset_label: string;
        label: string | null;
        display_label: string;
    } | null;
}

interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = withDefaults(defineProps<{
    open: boolean;
    type?: 'all' | 'images' | 'videos';
    multiple?: boolean;
    selected?: MediaItem[];
    allowUpload?: boolean;
}>(), {
    type: 'all',
    multiple: false,
    selected: () => [],
    allowUpload: true,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    'select': [items: MediaItem[]];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

// State
const search = ref('');
const filterType = ref(props.type === 'all' ? '' : props.type);
const media = ref<PaginatedResponse<MediaItem>>({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 24,
    total: 0,
});
const isLoading = ref(false);
const selectedItems = ref<Set<string>>(new Set());

// Initialize selected items from props
watch(() => props.selected, (items) => {
    selectedItems.value = new Set(items.map(i => i.uuid));
}, { immediate: true });

// Reset when opened
watch(isOpen, (open) => {
    if (open) {
        // Reset all state when modal opens
        search.value = '';
        filterType.value = props.type === 'all' ? '' : props.type;
        selectedItems.value = new Set(props.selected.map(i => i.uuid));
        allSelectedItems.value = new Map(props.selected.map(i => [i.uuid, i]));
        activeTab.value = 'browse';
        uploadFiles.value = [];
        uploadErrors.value = [];
        validationErrors.value = {};
        hasUploadedMedia.value = false;
        loadMedia();
    }
});

const debouncedSearch = useDebounceFn(() => {
    loadMedia();
}, 300);

watch(search, () => {
    debouncedSearch();
});

watch(filterType, () => {
    loadMedia();
});

async function loadMedia(page = 1) {
    isLoading.value = true;

    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (filterType.value) params.set('type', filterType.value);
    params.set('page', String(page));

    try {
        const response = await fetch(`/cms/media/picker?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
            },
        });
        media.value = await response.json();
    } catch (error) {
        console.error('Failed to load media:', error);
    }

    isLoading.value = false;
}

function toggleSelect(item: MediaItem) {
    if (props.multiple) {
        if (selectedItems.value.has(item.uuid)) {
            selectedItems.value.delete(item.uuid);
        } else {
            selectedItems.value.add(item.uuid);
        }
        selectedItems.value = new Set(selectedItems.value);
    } else {
        // Single select mode
        selectedItems.value = new Set([item.uuid]);
    }
}

function isSelected(uuid: string): boolean {
    return selectedItems.value.has(uuid);
}

const selectedCount = computed(() => selectedItems.value.size);

const typeOptions = [
    { value: '', label: 'All Media' },
    { value: 'images', label: 'Images' },
    { value: 'videos', label: 'Videos' },
];

// Upload functionality
const activeTab = ref<'browse' | 'upload'>('browse');

interface UploadFile {
    file: File;
    title: string;
    description: string;
    preview?: string;
}

const uploadFiles = ref<UploadFile[]>([]);
const isUploading = ref(false);
const uploadProgress = ref<Record<string, number>>({});
const uploadErrors = ref<string[]>([]);
const validationErrors = ref<Record<number, { title?: string; description?: string }>>({});
const fileInputRef = ref<HTMLInputElement | null>(null);
const hasUploadedMedia = ref(false); // Track if any uploads succeeded during this session

function handleDrop(e: DragEvent) {
    e.preventDefault();
    const files = Array.from(e.dataTransfer?.files || []);
    addFiles(files);
}

function handleDragOver(e: DragEvent) {
    e.preventDefault();
}

function handleFileSelect(e: Event) {
    const target = e.target as HTMLInputElement;
    const files = Array.from(target.files || []);
    addFiles(files);
    // Reset input
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function addFiles(files: File[]) {
    // Filter by type if needed
    const validFiles = files.filter(file => {
        if (props.type === 'images') {
            return file.type.startsWith('image/');
        }
        if (props.type === 'videos') {
            return file.type.startsWith('video/');
        }
        return file.type.startsWith('image/') || file.type.startsWith('video/');
    });

    // Create upload file objects with empty title/description
    const newUploadFiles: UploadFile[] = validFiles.map(file => {
        const uploadFile: UploadFile = {
            file,
            title: '',
            description: '',
        };

        // Generate preview for images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                uploadFile.preview = e.target?.result as string;
            };
            reader.readAsDataURL(file);
        }

        return uploadFile;
    });

    uploadFiles.value = [...uploadFiles.value, ...newUploadFiles];
}

function removeFile(index: number) {
    uploadFiles.value.splice(index, 1);
    // Clean up validation errors for removed file and reindex
    const newErrors: Record<number, { title?: string; description?: string }> = {};
    Object.entries(validationErrors.value).forEach(([key, value]) => {
        const keyNum = parseInt(key);
        if (keyNum < index) {
            newErrors[keyNum] = value;
        } else if (keyNum > index) {
            newErrors[keyNum - 1] = value;
        }
    });
    validationErrors.value = newErrors;
}

function getCsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

function validateUploadFiles(): boolean {
    validationErrors.value = {};
    let isValid = true;

    uploadFiles.value.forEach((uploadFile, index) => {
        const errors: { title?: string; description?: string } = {};

        if (!uploadFile.title.trim()) {
            errors.title = 'Title is required';
            isValid = false;
        }

        if (!uploadFile.description.trim()) {
            errors.description = 'Description is required';
            isValid = false;
        }

        if (Object.keys(errors).length > 0) {
            validationErrors.value[index] = errors;
        }
    });

    return isValid;
}

async function uploadAll() {
    if (uploadFiles.value.length === 0) return;

    // Validate all files have title and description
    if (!validateUploadFiles()) {
        return;
    }

    isUploading.value = true;
    uploadErrors.value = [];
    const uploadedItems: MediaItem[] = [];

    for (let i = 0; i < uploadFiles.value.length; i++) {
        const uploadFile = uploadFiles.value[i];
        const formData = new FormData();
        formData.append('file', uploadFile.file);
        formData.append('title', uploadFile.title.trim());
        formData.append('alt_text', uploadFile.description.trim());

        try {
            const response = await fetch('/cms/media', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-XSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`Failed to upload ${uploadFile.file.name}`);
            }

            const result = await response.json();
            if (result.media) {
                uploadedItems.push(result.media);
                // Auto-select uploaded item and add to allSelectedItems
                selectedItems.value.add(result.media.uuid);
                allSelectedItems.value.set(result.media.uuid, result.media);
                hasUploadedMedia.value = true; // Track that we have successful uploads
            }
            uploadProgress.value[uploadFile.file.name] = 100;
        } catch (error: any) {
            uploadErrors.value.push(error.message || `Failed to upload ${uploadFile.file.name}`);
        }
    }

    isUploading.value = false;
    uploadFiles.value = [];
    uploadProgress.value = {};
    validationErrors.value = {};

    // If uploads succeeded and no errors, auto-confirm selection for better UX
    if (uploadedItems.length > 0 && uploadErrors.value.length === 0) {
        // In single mode, just confirm immediately after upload
        if (!props.multiple) {
            confirmSelection();
            return;
        }
    }

    // Reload media to show uploaded items
    await loadMedia();

    // Switch to browse tab to show the uploaded items
    activeTab.value = 'browse';
}

function formatFileSize(bytes: number): string {
    const units = ['B', 'KB', 'MB', 'GB'];
    let unitIndex = 0;
    let size = bytes;
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

// Track all selected items including from previous pages
const allSelectedItems = ref<Map<string, MediaItem>>(new Map());

// Crop version selection state
const showCropSelector = ref(false);
const cropSelectorItem = ref<MediaItem | null>(null);

// Update all selected items when selection changes
watch(selectedItems, (newSelected) => {
    // Add newly selected items from current page
    for (const uuid of newSelected) {
        const item = media.value.data.find(m => m.uuid === uuid);
        if (item && !allSelectedItems.value.has(uuid)) {
            allSelectedItems.value.set(uuid, item);
        }
    }
    // Remove deselected items
    for (const uuid of allSelectedItems.value.keys()) {
        if (!newSelected.has(uuid)) {
            allSelectedItems.value.delete(uuid);
        }
    }
}, { deep: true });

function confirmSelection() {
    const selected = Array.from(allSelectedItems.value.values());

    // In single-select mode, check if the image has crops and show selector
    if (!props.multiple && selected.length === 1) {
        const item = selected[0];
        if (item.has_crops && item.crops && item.crops.length > 0) {
            // Show crop selector
            cropSelectorItem.value = item;
            showCropSelector.value = true;
            return;
        }
    }

    // For multi-select or items without crops, emit directly
    emit('select', selected);
    isOpen.value = false;
}

function selectWithCrop(item: MediaItem, crop: CropVersion | null) {
    // Create a copy of the item with crop info
    const selectedItem: MediaItem = {
        ...item,
        // Override URL if crop is selected
        url: crop ? crop.url : item.url,
        thumbnail_url: crop ? crop.thumbnail_url : item.thumbnail_url,
        crop_version: crop ? {
            id: crop.id,
            uuid: crop.uuid,
            preset_name: crop.preset_name,
            preset_label: crop.preset_label,
            label: crop.label,
            display_label: crop.display_label,
        } : null,
    };

    emit('select', [selectedItem]);
    showCropSelector.value = false;
    cropSelectorItem.value = null;
    isOpen.value = false;
}

function cancelCropSelection() {
    showCropSelector.value = false;
    cropSelectorItem.value = null;
}

function closeModal() {
    // If uploads succeeded during this session, refresh the page so parent sees new media
    if (hasUploadedMedia.value) {
        window.location.reload();
        return;
    }
    isOpen.value = false;
}
</script>

<template>
    <UModal v-model:open="isOpen" :ui="{ width: 'max-w-4xl' }">
        <template #content>
            <div class="flex flex-col max-h-[80vh] bg-[var(--ui-bg)] rounded-lg">
                <!-- Header -->
                <div class="flex items-center justify-between gap-4 p-4 border-b border-default">
                    <div>
                        <h2 class="text-lg font-semibold text-highlighted">
                            Select Media
                        </h2>
                        <p class="text-sm text-muted mt-0.5">
                            {{ multiple ? 'Select one or more items' : 'Select an item' }}
                        </p>
                    </div>
                    <UButton
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        @click="closeModal"
                    />
                </div>

                <!-- Tabs -->
                <div v-if="allowUpload" class="px-4 pt-4">
                    <div class="flex gap-1 border-b border-default">
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                            :class="activeTab === 'browse'
                                ? 'text-primary border-primary'
                                : 'text-muted border-transparent hover:text-highlighted'"
                            @click="activeTab = 'browse'"
                        >
                            <UIcon name="i-lucide-grid-3x3" class="size-4 mr-1.5 inline-block" />
                            Browse Library
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                            :class="activeTab === 'upload'
                                ? 'text-primary border-primary'
                                : 'text-muted border-transparent hover:text-highlighted'"
                            @click="activeTab = 'upload'"
                        >
                            <UIcon name="i-lucide-upload" class="size-4 mr-1.5 inline-block" />
                            Upload New
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-hidden p-4">
                    <!-- Browse Tab -->
                    <div v-show="activeTab === 'browse'" class="h-full flex flex-col">
                        <!-- Filters -->
                        <div class="flex items-center gap-3 mb-4">
                            <UInput
                                v-model="search"
                                placeholder="Search media..."
                                icon="i-lucide-search"
                                class="w-64"
                            />

                            <USelectMenu
                                v-if="type === 'all'"
                                v-model="filterType"
                                :items="typeOptions"
                                value-key="value"
                                placeholder="All Media"
                                class="w-40"
                            />

                            <span class="ml-auto text-sm text-muted">
                                {{ media.total }} item{{ media.total !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        <!-- Loading -->
                        <div v-if="isLoading" class="flex-1 flex items-center justify-center">
                            <UIcon name="i-lucide-loader-2" class="size-8 text-muted animate-spin" />
                        </div>

                        <!-- Media Grid -->
                        <div v-else class="flex-1 overflow-y-auto">
                            <div
                                v-if="media.data.length > 0"
                                class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3"
                            >
                                <div
                                    v-for="item in media.data"
                                    :key="item.uuid"
                                    class="relative rounded-lg border bg-muted/30 overflow-hidden cursor-pointer transition-all"
                                    :class="isSelected(item.uuid)
                                        ? 'ring-2 ring-primary border-primary'
                                        : 'border-default hover:border-primary/50'"
                                    @click="toggleSelect(item)"
                                >
                                    <div class="aspect-square">
                                        <BlurHashImage
                                            v-if="item.thumbnail_url"
                                            :src="item.thumbnail_url"
                                            :alt="item.title || 'Media'"
                                            :blurhash="item.blurhash"
                                            class="w-full h-full"
                                            img-class="object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center"
                                        >
                                            <UIcon
                                                :name="item.is_video ? 'i-lucide-video' : 'i-lucide-image'"
                                                class="size-8 text-muted"
                                            />
                                        </div>

                                        <!-- Video Overlay -->
                                        <div
                                            v-if="item.is_video"
                                            class="absolute inset-0 flex items-center justify-center bg-black/20"
                                        >
                                            <div class="rounded-full bg-black/50 p-1.5">
                                                <UIcon name="i-lucide-play" class="size-4 text-white" />
                                            </div>
                                        </div>

                                        <!-- Selection Indicator -->
                                        <div
                                            v-if="isSelected(item.uuid)"
                                            class="absolute top-2 right-2"
                                        >
                                            <div class="rounded-full bg-primary p-1">
                                                <UIcon name="i-lucide-check" class="size-3 text-white" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="flex-1 flex flex-col items-center justify-center py-12 text-muted">
                                <UIcon name="i-lucide-image-off" class="size-12 mb-4 opacity-50" />
                                <p v-if="search">No media found matching "{{ search }}"</p>
                                <p v-else>No media available.</p>
                                <UButton
                                    v-if="allowUpload"
                                    color="primary"
                                    variant="soft"
                                    icon="i-lucide-upload"
                                    class="mt-4"
                                    @click="activeTab = 'upload'"
                                >
                                    Upload Media
                                </UButton>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="media.last_page > 1" class="flex items-center justify-center pt-4 border-t border-default mt-4">
                            <UPagination
                                :page="media.current_page"
                                :total="media.total"
                                :items-per-page="media.per_page"
                                @update:page="loadMedia"
                            />
                        </div>
                    </div>

                    <!-- Upload Tab -->
                    <div v-show="activeTab === 'upload'" class="h-full flex flex-col">
                        <!-- Drop Zone -->
                        <div
                            class="relative border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer group"
                            :class="uploadFiles.length > 0
                                ? 'border-default bg-muted/5 hover:border-primary/50'
                                : 'border-primary/30 bg-primary/5 hover:border-primary hover:bg-primary/10'"
                            @drop="handleDrop"
                            @dragover="handleDragOver"
                            @click="fileInputRef?.click()"
                        >
                            <div class="flex flex-col items-center">
                                <div class="size-16 rounded-2xl bg-primary/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <UIcon name="i-lucide-cloud-upload" class="size-8 text-primary" />
                                </div>
                                <p class="text-highlighted font-semibold text-lg mb-1">
                                    {{ uploadFiles.length > 0 ? 'Add more files' : 'Drop files here' }}
                                </p>
                                <p class="text-sm text-muted mb-3">
                                    or <span class="text-primary font-medium">browse from your computer</span>
                                </p>
                                <div class="flex items-center gap-2 text-xs text-muted">
                                    <UIcon :name="type === 'videos' ? 'i-lucide-film' : 'i-lucide-image'" class="size-3.5" />
                                    <span>{{ type === 'images' ? 'Images only' : type === 'videos' ? 'Videos only' : 'Images and videos supported' }}</span>
                                </div>
                            </div>
                            <input
                                ref="fileInputRef"
                                type="file"
                                class="hidden"
                                :accept="type === 'images' ? 'image/*' : type === 'videos' ? 'video/*' : 'image/*,video/*'"
                                multiple
                                @change="handleFileSelect"
                            />
                        </div>

                        <!-- Upload Errors -->
                        <div v-if="uploadErrors.length > 0" class="mt-4 p-3 rounded-lg bg-error/10 border border-error/20">
                            <div v-for="(error, index) in uploadErrors" :key="index" class="text-sm text-error">
                                {{ error }}
                            </div>
                        </div>

                        <!-- File List -->
                        <div v-if="uploadFiles.length > 0" class="mt-6 flex-1 overflow-y-auto">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-medium text-highlighted">
                                    {{ uploadFiles.length }} file{{ uploadFiles.length !== 1 ? 's' : '' }} ready to upload
                                </h3>
                                <UButton
                                    v-if="uploadFiles.length > 1"
                                    variant="ghost"
                                    color="neutral"
                                    size="xs"
                                    @click="uploadFiles = []"
                                >
                                    Clear all
                                </UButton>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(uploadFile, index) in uploadFiles"
                                    :key="index"
                                    class="group relative rounded-xl border bg-[var(--ui-bg)] shadow-sm transition-all"
                                    :class="validationErrors[index] ? 'border-error/50' : 'border-default hover:border-primary/30'"
                                >
                                    <!-- Remove Button -->
                                    <button
                                        type="button"
                                        class="absolute -top-2 -right-2 z-10 size-6 rounded-full bg-default border border-default shadow-sm flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-error hover:border-error hover:text-white"
                                        :disabled="isUploading"
                                        @click="removeFile(index)"
                                    >
                                        <UIcon name="i-lucide-x" class="size-3.5" />
                                    </button>

                                    <div class="flex gap-4 p-4">
                                        <!-- Large Preview -->
                                        <div class="shrink-0 w-32 h-24 rounded-lg bg-muted/20 overflow-hidden border border-default">
                                            <img
                                                v-if="uploadFile.preview"
                                                :src="uploadFile.preview"
                                                :alt="uploadFile.file.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1">
                                                <UIcon
                                                    :name="uploadFile.file.type.startsWith('image/') ? 'i-lucide-image' : 'i-lucide-film'"
                                                    class="size-8 text-muted"
                                                />
                                                <span class="text-[10px] text-muted">Processing...</span>
                                            </div>
                                        </div>

                                        <!-- Form Fields -->
                                        <div class="flex-1 min-w-0 space-y-3">
                                            <!-- File name badge -->
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-muted/30 text-xs text-muted max-w-full">
                                                    <UIcon name="i-lucide-file" class="size-3 shrink-0" />
                                                    <span class="truncate">{{ uploadFile.file.name }}</span>
                                                    <span class="shrink-0 text-muted/70">{{ formatFileSize(uploadFile.file.size) }}</span>
                                                </span>
                                            </div>

                                            <!-- Title Field -->
                                            <UInput
                                                v-model="uploadFile.title"
                                                placeholder="Title (required)"
                                                :disabled="isUploading"
                                                :color="validationErrors[index]?.title ? 'error' : undefined"
                                                :ui="{ base: 'font-medium' }"
                                            />

                                            <!-- Description Field -->
                                            <UTextarea
                                                v-model="uploadFile.description"
                                                placeholder="Alt text / description (required for accessibility)"
                                                :rows="2"
                                                :disabled="isUploading"
                                                :color="validationErrors[index]?.description ? 'error' : undefined"
                                            />

                                            <!-- Validation Error -->
                                            <p v-if="validationErrors[index]" class="text-xs text-error flex items-center gap-1">
                                                <UIcon name="i-lucide-alert-circle" class="size-3" />
                                                Please fill in all required fields
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Button -->
                        <div v-if="uploadFiles.length > 0" class="mt-4 pt-4 border-t border-default">
                            <UButton
                                color="primary"
                                :loading="isUploading"
                                :disabled="uploadFiles.length === 0"
                                class="w-full justify-center"
                                size="lg"
                                @click="uploadAll"
                            >
                                <UIcon name="i-lucide-cloud-upload" class="size-5 mr-2" />
                                Upload {{ uploadFiles.length }} file{{ uploadFiles.length !== 1 ? 's' : '' }}
                            </UButton>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between gap-2 p-4 border-t border-default">
                    <span v-if="selectedCount > 0" class="text-sm text-muted">
                        {{ selectedCount }} item{{ selectedCount !== 1 ? 's' : '' }} selected
                    </span>
                    <span v-else></span>

                    <div class="flex items-center gap-2">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            @click="closeModal"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="primary"
                            :disabled="selectedCount === 0"
                            @click="confirmSelection"
                        >
                            {{ multiple ? `Select ${selectedCount} Item${selectedCount !== 1 ? 's' : ''}` : 'Select' }}
                        </UButton>
                    </div>
                </div>

                <!-- Crop Version Selector Overlay -->
                <div
                    v-if="showCropSelector && cropSelectorItem"
                    class="absolute inset-0 bg-[var(--ui-bg)] flex flex-col rounded-lg"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-4 p-4 border-b border-default">
                        <div>
                            <h2 class="text-lg font-semibold text-highlighted">
                                Select Image Version
                            </h2>
                            <p class="text-sm text-muted mt-0.5">
                                Choose the original or a crop version
                            </p>
                        </div>
                        <UButton
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            @click="cancelCropSelection"
                        />
                    </div>

                    <!-- Options -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <!-- Original Option -->
                            <button
                                type="button"
                                class="relative rounded-lg border-2 border-default bg-elevated/50 overflow-hidden hover:border-primary transition-colors text-left"
                                @click="selectWithCrop(cropSelectorItem!, null)"
                            >
                                <div class="aspect-video bg-muted/30">
                                    <BlurHashImage
                                        v-if="cropSelectorItem.thumbnail_url"
                                        :src="cropSelectorItem.thumbnail_url"
                                        :alt="cropSelectorItem.title || 'Original'"
                                        :blurhash="cropSelectorItem.blurhash"
                                        class="w-full h-full"
                                        img-class="object-cover"
                                    />
                                </div>
                                <div class="p-3">
                                    <p class="text-sm font-medium text-highlighted">Original</p>
                                    <p class="text-xs text-muted">Full size image</p>
                                </div>
                            </button>

                            <!-- Crop Options -->
                            <button
                                v-for="crop in cropSelectorItem.crops"
                                :key="crop.id"
                                type="button"
                                class="relative rounded-lg border-2 border-default bg-elevated/50 overflow-hidden hover:border-primary transition-colors text-left"
                                @click="selectWithCrop(cropSelectorItem!, crop)"
                            >
                                <div class="aspect-video bg-muted/30">
                                    <img
                                        v-if="crop.thumbnail_url"
                                        :src="crop.thumbnail_url"
                                        :alt="crop.display_label"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <UIcon name="i-lucide-crop" class="size-8 text-muted" />
                                    </div>
                                </div>
                                <div class="p-3">
                                    <p class="text-sm font-medium text-highlighted">{{ crop.display_label }}</p>
                                    <p class="text-xs text-muted">{{ crop.output_width }} x {{ crop.output_height }}</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-2 p-4 border-t border-default">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            @click="cancelCropSelection"
                        >
                            Back
                        </UButton>
                    </div>
                </div>
            </div>
        </template>
    </UModal>
</template>
