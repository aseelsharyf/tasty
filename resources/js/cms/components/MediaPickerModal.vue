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
    // Video embed fields
    embed_url?: string | null;
    embed_provider?: 'youtube' | 'vimeo' | null;
    embed_video_id?: string | null;
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

interface Tag {
    id: number;
    name: string | Record<string, string>;
    slug: string;
}

interface MediaCategory {
    slug: string;
    label: string;
}

const props = withDefaults(defineProps<{
    open: boolean;
    type?: 'all' | 'images' | 'videos';
    multiple?: boolean;
    selected?: MediaItem[];
    allowUpload?: boolean;
    defaultCategory?: string;
    mediaCategories?: MediaCategory[];
}>(), {
    type: 'all',
    multiple: false,
    selected: () => [],
    allowUpload: true,
    defaultCategory: 'media',
    mediaCategories: () => [],
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
const filterCategory = ref(props.defaultCategory || '');
const uploadCategory = ref(props.defaultCategory || 'media');
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
        filterCategory.value = props.defaultCategory || '';
        uploadCategory.value = props.defaultCategory || 'media';
        selectedItems.value = new Set(props.selected.map(i => i.uuid));
        allSelectedItems.value = new Map(props.selected.map(i => [i.uuid, i]));
        activeTab.value = 'browse';
        uploadFiles.value = [];
        uploadFileKey.value = 0;
        uploadErrors.value = [];
        validationErrors.value = {};
        hasUploadedMedia.value = false;
        loadMedia();
        loadTags(); // Load tags for upload form
        loadCategories(); // Load categories for filter
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

watch(filterCategory, () => {
    loadMedia();
});

async function loadMedia(page = 1) {
    isLoading.value = true;

    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (filterType.value) params.set('type', filterType.value);
    if (filterCategory.value) params.set('category', filterCategory.value);
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
    caption: string;
    tag_ids: number[];
    preview?: string;
}

const uploadFiles = ref<UploadFile[]>([]);
const uploadFileKey = ref(0); // Counter to force re-render when file changes
const isUploading = ref(false);
const uploadProgress = ref<Record<string, number>>({});
const uploadErrors = ref<string[]>([]);
const validationErrors = ref<Record<number, { title?: string; caption?: string; tags?: string }>>({});
const fileInputRef = ref<HTMLInputElement | null>(null);
const hasUploadedMedia = ref(false); // Track if any uploads succeeded during this session

// Tags for upload
const availableTags = ref<Tag[]>([]);
const isLoadingTags = ref(false);

async function loadTags() {
    if (availableTags.value.length > 0) return; // Already loaded
    isLoadingTags.value = true;
    try {
        const response = await fetch('/cms/media/tags', {
            headers: { 'Accept': 'application/json' },
        });
        if (response.ok) {
            availableTags.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load tags:', error);
    }
    isLoadingTags.value = false;
}

// Categories for filter
const availableCategories = ref<MediaCategory[]>([]);

async function loadCategories() {
    // Use props if provided, otherwise fetch from API
    if (props.mediaCategories && props.mediaCategories.length > 0) {
        availableCategories.value = props.mediaCategories;
        return;
    }
    try {
        const response = await fetch('/cms/media/categories', {
            headers: { 'Accept': 'application/json' },
        });
        if (response.ok) {
            availableCategories.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load categories:', error);
    }
}

// Category options for filter select
const categoryOptions = computed(() => {
    const options = [{ value: '', label: 'All Categories' }];
    for (const cat of availableCategories.value) {
        options.push({ value: cat.slug, label: cat.label });
    }
    return options;
});

// Tag options for select menu
const tagOptions = computed(() => {
    return availableTags.value.map(tag => ({
        value: tag.id,
        label: typeof tag.name === 'string' ? tag.name : (tag.name?.en || tag.slug),
    }));
});

// Create a new tag
async function createTag(name: string): Promise<Tag | null> {
    try {
        const response = await fetch('/cms/tags', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                name: { en: name },
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to create tag');
        }

        const newTag = await response.json();
        // Add to available tags
        availableTags.value.push({
            id: newTag.id,
            name: newTag.name,
            slug: newTag.slug,
        });
        return newTag;
    } catch (error) {
        console.error('Failed to create tag:', error);
        return null;
    }
}

// Handle create tag from USelectMenu
async function onCreateTag(name: string, uploadFileIndex: number) {
    const newTag = await createTag(name);
    if (newTag && uploadFiles.value[uploadFileIndex]) {
        uploadFiles.value[uploadFileIndex].tag_ids.push(newTag.id);
    }
}

// Pill-style tag input state
const tagInputRefs = ref<HTMLInputElement[]>([]);
const tagSearchQuery = ref('');
const showTagSuggestions = ref(false);
const activeTagFileIndex = ref<number | null>(null);
const tagSuggestions = ref<{ id: number; name: string; slug: string }[]>([]);
const isSearchingTags = ref(false);
let tagSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

// Get tag label by ID
function getTagLabel(tagId: number): string {
    const tag = availableTags.value.find(t => t.id === tagId);
    if (!tag) return String(tagId);
    return typeof tag.name === 'string' ? tag.name : (tag.name?.en || tag.slug);
}

// Remove tag from file
function removeTagFromFile(fileIndex: number, tagId: number) {
    const file = uploadFiles.value[fileIndex];
    if (file) {
        const idx = file.tag_ids.indexOf(tagId);
        if (idx > -1) {
            file.tag_ids.splice(idx, 1);
        }
    }
}

// Add tag to file
function addTagToFile(fileIndex: number, tagId: number) {
    const file = uploadFiles.value[fileIndex];
    if (file && !file.tag_ids.includes(tagId)) {
        file.tag_ids.push(tagId);
    }
    tagSearchQuery.value = '';
    tagSuggestions.value = [];
    showTagSuggestions.value = false;
}

// Search tags via API
async function searchTagsApi(query: string) {
    if (!query.trim()) {
        tagSuggestions.value = [];
        return;
    }

    const fileIndex = activeTagFileIndex.value;
    const excludeIds = fileIndex !== null ? uploadFiles.value[fileIndex]?.tag_ids || [] : [];

    isSearchingTags.value = true;
    try {
        const params = new URLSearchParams({
            q: query,
            limit: '10',
        });
        excludeIds.forEach(id => params.append('exclude[]', String(id)));

        const response = await fetch(`/cms/tags/search?${params.toString()}`, {
            headers: { 'Accept': 'application/json' },
        });
        if (response.ok) {
            tagSuggestions.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to search tags:', error);
        tagSuggestions.value = [];
    } finally {
        isSearchingTags.value = false;
    }
}

// Debounced tag search
function debouncedSearchTags() {
    if (tagSearchDebounceTimer) {
        clearTimeout(tagSearchDebounceTimer);
    }
    tagSearchDebounceTimer = setTimeout(() => {
        searchTagsApi(tagSearchQuery.value);
    }, 200);
}

// Select first suggestion or create tag on Enter
function selectFirstSuggestion(fileIndex: number) {
    if (tagSuggestions.value.length > 0) {
        addTagToFile(fileIndex, tagSuggestions.value[0].id);
    } else if (tagSearchQuery.value.trim()) {
        createAndAddTag(fileIndex);
    }
}

// Create new tag and add to file
async function createAndAddTag(fileIndex: number) {
    const name = tagSearchQuery.value.trim();
    if (!name) return;

    const newTag = await createTag(name);
    if (newTag) {
        addTagToFile(fileIndex, newTag.id);
    }
}

// Handle backspace to remove last tag
function onTagBackspace(fileIndex: number) {
    if (tagSearchQuery.value === '') {
        const file = uploadFiles.value[fileIndex];
        if (file && file.tag_ids.length > 0) {
            file.tag_ids.pop();
        }
    }
}

// Handle blur on tag input
function onTagInputBlur() {
    // Delay hiding suggestions to allow click events to fire
    setTimeout(() => {
        showTagSuggestions.value = false;
        activeTagFileIndex.value = null;
    }, 200);
}

// Check if upload form is ready (just need a file selected)
const canUpload = computed(() => {
    return uploadFiles.value.length > 0;
});

function handleDrop(e: DragEvent) {
    e.preventDefault();
    const files = Array.from(e.dataTransfer?.files || []);
    addFiles(files);
}

function handleDragOver(e: DragEvent) {
    e.preventDefault();
}

function triggerFileInput() {
    fileInputRef.value?.click();
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

async function addFiles(files: File[]) {
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

    // For single file mode (non-multiple picker), only take the first file
    const filesToAdd = props.multiple ? validFiles : validFiles.slice(0, 1);

    // Create upload file objects with empty title/description/tags
    const newUploadFiles: UploadFile[] = [];

    for (const file of filesToAdd) {
        const uploadFile: UploadFile = {
            file,
            title: '',
            caption: '',
            tag_ids: [],
            preview: undefined,
        };

        // Generate preview for images using a promise to ensure it's set
        if (file.type.startsWith('image/')) {
            const preview = await new Promise<string>((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    resolve(e.target?.result as string);
                };
                reader.readAsDataURL(file);
            });
            uploadFile.preview = preview;
        }

        newUploadFiles.push(uploadFile);
    }

    // In single mode, replace existing file; in multiple mode, append
    if (props.multiple) {
        uploadFiles.value = [...uploadFiles.value, ...newUploadFiles];
    } else {
        // Clear previous errors and validation when replacing file
        uploadErrors.value = [];
        validationErrors.value = {};
        uploadFiles.value = newUploadFiles;
        // Increment key to force Vue to re-render the preview
        uploadFileKey.value++;
    }
}

function removeFile(index: number) {
    uploadFiles.value.splice(index, 1);
    // Clean up validation errors for removed file and reindex
    const newErrors: Record<number, { title?: string; caption?: string; tags?: string }> = {};
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
    // Caption and tags are now optional, so always valid
    validationErrors.value = {};
    return true;
}

async function uploadAll() {
    // Only upload the first file (single file mode)
    const uploadFile = uploadFiles.value[0];
    if (!uploadFile) return;

    isUploading.value = true;
    uploadErrors.value = [];

    const formData = new FormData();
    formData.append('file', uploadFile.file);
    formData.append('title', uploadFile.title.trim());
    formData.append('caption', uploadFile.caption.trim());
    // Include category from upload form
    formData.append('category', uploadCategory.value || props.defaultCategory || 'media');
    // Send tag_ids as array
    uploadFile.tag_ids.forEach(tagId => {
        formData.append('tag_ids[]', String(tagId));
    });

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
            // Try to get error message from response
            let errorMessage = `Failed to upload ${uploadFile.file.name}`;
            try {
                const errorData = await response.json();
                if (errorData.message) {
                    errorMessage = `${uploadFile.file.name}: ${errorData.message}`;
                } else if (errorData.errors) {
                    const firstError = Object.values(errorData.errors)[0];
                    if (Array.isArray(firstError) && firstError[0]) {
                        errorMessage = `${uploadFile.file.name}: ${firstError[0]}`;
                    }
                }
            } catch {
                // Ignore JSON parse errors
            }
            throw new Error(errorMessage);
        }

        const result = await response.json();
        if (result.media) {
            hasUploadedMedia.value = true;
            // Clear upload state
            uploadFiles.value = [];
            uploadProgress.value = {};
            validationErrors.value = {};
            // Emit the uploaded item and close modal
            emit('select', [result.media]);
            isOpen.value = false;
        }
    } catch (error: any) {
        uploadErrors.value.push(error.message || `Failed to upload ${uploadFile.file.name}`);
    } finally {
        isUploading.value = false;
    }
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
                                class="w-48"
                            />

                            <select
                                v-if="categoryOptions.length > 1"
                                v-model="filterCategory"
                                class="px-3 py-1.5 text-sm rounded-md border border-default bg-default text-highlighted focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option
                                    v-for="option in categoryOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <select
                                v-if="type === 'all'"
                                v-model="filterType"
                                class="px-3 py-1.5 text-sm rounded-md border border-default bg-default text-highlighted focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

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
                        <!-- Hidden file input - key forces new input when file changes -->
                        <input
                            :key="'file-input-' + uploadFileKey"
                            ref="fileInputRef"
                            type="file"
                            class="hidden"
                            :accept="type === 'images' ? 'image/*' : type === 'videos' ? 'video/*' : 'image/*,video/*'"
                            @change="handleFileSelect"
                        />

                        <!-- No file selected - show drop zone -->
                        <div
                            v-if="uploadFiles.length === 0"
                            class="flex-1 relative border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer group border-primary/30 bg-primary/5 hover:border-primary hover:bg-primary/10"
                            @drop="handleDrop"
                            @dragover="handleDragOver"
                            @click="triggerFileInput"
                        >
                            <div class="flex flex-col items-center justify-center h-full">
                                <div class="size-16 rounded-2xl bg-primary/10 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <UIcon name="i-lucide-cloud-upload" class="size-8 text-primary" />
                                </div>
                                <p class="text-highlighted font-semibold text-lg mb-1">
                                    Drop file here
                                </p>
                                <p class="text-sm text-muted mb-3">
                                    or <span class="text-primary font-medium">browse from your computer</span>
                                </p>
                                <div class="flex items-center gap-2 text-xs text-muted">
                                    <UIcon :name="type === 'videos' ? 'i-lucide-film' : 'i-lucide-image'" class="size-3.5" />
                                    <span>{{ type === 'images' ? 'Images only' : type === 'videos' ? 'Videos only' : 'Images and videos supported' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- File selected - show preview and form (single file only) -->
                        <div v-else-if="uploadFiles.length > 0" :key="uploadFileKey" class="flex-1 flex flex-col min-h-0">
                            <!-- Large Preview - clickable to change -->
                            <div
                                class="relative rounded-xl bg-muted/10 border-2 border-dashed border-default overflow-hidden mb-4 cursor-pointer group hover:border-primary/50 transition-colors"
                                @click="triggerFileInput"
                                @drop="handleDrop"
                                @dragover="handleDragOver"
                            >
                                <div class="aspect-video max-h-56 flex items-center justify-center bg-muted/5">
                                    <img
                                        v-if="uploadFiles[0].preview"
                                        :key="uploadFiles[0].preview"
                                        :src="uploadFiles[0].preview"
                                        :alt="uploadFiles[0].file.name"
                                        class="max-w-full max-h-full object-contain"
                                    />
                                    <div v-else class="flex flex-col items-center justify-center gap-2 py-8">
                                        <UIcon
                                            :name="uploadFiles[0].file.type.startsWith('image/') ? 'i-lucide-image' : 'i-lucide-film'"
                                            class="size-12 text-muted"
                                        />
                                        <span class="text-sm text-muted">Processing preview...</span>
                                    </div>
                                </div>
                                <!-- Change overlay on hover -->
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <div class="flex items-center gap-2 text-white font-medium">
                                        <UIcon name="i-lucide-replace" class="size-5" />
                                        <span>Click to change</span>
                                    </div>
                                </div>
                                <!-- File info badge -->
                                <div class="absolute bottom-2 left-2 px-2 py-1 rounded-md bg-black/60 text-white text-xs flex items-center gap-1.5">
                                    <UIcon name="i-lucide-file" class="size-3" />
                                    <span class="truncate max-w-[200px]">{{ uploadFiles[0].file.name }}</span>
                                    <span class="text-white/70">{{ formatFileSize(uploadFiles[0].file.size) }}</span>
                                </div>
                            </div>

                            <!-- Scrollable Form Fields -->
                            <div class="flex-1 overflow-y-auto min-h-0 space-y-4">
                                <!-- Category Field -->
                                <UFormField label="Category">
                                    <select
                                        v-model="uploadCategory"
                                        class="w-full px-3 py-1.5 text-sm rounded-md border border-default bg-default text-highlighted focus:outline-none focus:ring-2 focus:ring-primary"
                                        :disabled="isUploading"
                                    >
                                        <option
                                            v-for="option in categoryOptions.filter(o => o.value)"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </UFormField>

                                <!-- Caption Field -->
                                <UFormField label="Caption">
                                    <UTextarea
                                        v-model="uploadFiles[0].caption"
                                        placeholder="Describe this image..."
                                        :rows="2"
                                        :disabled="isUploading"
                                        class="w-full"
                                    />
                                </UFormField>

                                <!-- Tags Field - Pill Input Style -->
                                <UFormField label="Tags">
                                    <div class="relative">
                                        <div class="flex items-center gap-2 p-2 border border-muted rounded-lg bg-default focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all">
                                            <UIcon name="i-lucide-tag" class="size-4 text-muted shrink-0" />
                                            <div class="flex-1 flex flex-wrap items-center gap-1.5">
                                                <!-- Selected Tags as inline pills -->
                                                <span
                                                    v-for="tagId in uploadFiles[0].tag_ids"
                                                    :key="tagId"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded text-sm font-medium"
                                                >
                                                    {{ getTagLabel(tagId) }}
                                                    <button
                                                        type="button"
                                                        class="hover:text-primary/70"
                                                        :disabled="isUploading"
                                                        @click.stop="removeTagFromFile(0, tagId)"
                                                    >
                                                        <UIcon name="i-lucide-x" class="size-3" />
                                                    </button>
                                                </span>
                                                <!-- Tag search input -->
                                                <input
                                                    ref="tagInputRefs"
                                                    v-model="tagSearchQuery"
                                                    type="text"
                                                    class="flex-1 min-w-[100px] bg-transparent border-none outline-none text-sm placeholder:text-muted"
                                                    placeholder="Type to search or create tags..."
                                                    :disabled="isUploading"
                                                    @focus="showTagSuggestions = true; activeTagFileIndex = 0"
                                                    @blur="onTagInputBlur"
                                                    @input="debouncedSearchTags"
                                                    @keydown.enter.prevent="selectFirstSuggestion(0)"
                                                    @keydown.backspace="onTagBackspace(0)"
                                                />
                                            </div>
                                        </div>

                                        <!-- Tag suggestions dropdown -->
                                        <div
                                            v-if="showTagSuggestions && activeTagFileIndex === 0 && (tagSuggestions.length > 0 || tagSearchQuery.trim())"
                                            class="absolute z-50 w-full mt-1 bg-default border border-default rounded-lg shadow-lg max-h-48 overflow-y-auto"
                                        >
                                            <!-- Loading -->
                                            <div v-if="isSearchingTags" class="px-3 py-2 text-sm text-muted flex items-center gap-2">
                                                <UIcon name="i-lucide-loader-2" class="size-3.5 animate-spin" />
                                                <span>Searching...</span>
                                            </div>

                                            <!-- Existing tags matching search -->
                                            <button
                                                v-for="tag in tagSuggestions"
                                                :key="tag.id"
                                                type="button"
                                                class="w-full px-3 py-2 text-left text-sm hover:bg-muted/50 flex items-center gap-2 transition-colors"
                                                @mousedown.prevent="addTagToFile(0, tag.id)"
                                            >
                                                <UIcon name="i-lucide-tag" class="size-3.5 text-muted" />
                                                {{ tag.name }}
                                            </button>

                                            <!-- Create new tag option -->
                                            <button
                                                v-if="tagSearchQuery.trim() && !tagSuggestions.some(t => t.name.toLowerCase() === tagSearchQuery.toLowerCase())"
                                                type="button"
                                                class="w-full px-3 py-2 text-left text-sm hover:bg-muted/50 flex items-center gap-2 text-primary transition-colors border-t border-default"
                                                @mousedown.prevent="createAndAddTag(0)"
                                            >
                                                <UIcon name="i-lucide-plus" class="size-3.5" />
                                                Create "{{ tagSearchQuery.trim() }}"
                                            </button>
                                        </div>
                                    </div>
                                </UFormField>
                            </div>

                            <!-- Fixed Bottom Section -->
                            <div class="shrink-0">
                                <!-- Upload Errors -->
                                <div v-if="uploadErrors.length > 0" class="mt-4 p-3 rounded-lg bg-error/10 border border-error/20">
                                    <div v-for="(error, errIndex) in uploadErrors" :key="errIndex" class="text-sm text-error">
                                        {{ error }}
                                    </div>
                                </div>

                                <!-- Upload Button -->
                                <div class="mt-4 pt-4 border-t border-default">
                                    <UButton
                                        color="primary"
                                        :loading="isUploading"
                                        :disabled="!canUpload"
                                        class="w-full justify-center"
                                        size="lg"
                                        @click="uploadAll"
                                    >
                                        <UIcon name="i-lucide-cloud-upload" class="size-5 mr-2" />
                                        Upload & Select
                                    </UButton>
                                </div>
                            </div>
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
