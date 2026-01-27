<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

interface Tag {
    id: number;
    name: string | Record<string, string>;
    slug: string;
}

interface Language {
    id: number;
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_default: boolean;
}

interface User {
    id: number;
    name: string;
    role: string | null;
}

interface MediaCategory {
    slug: string;
    label: string;
}

interface BulkUploadFile {
    id: string;
    file: File;
    preview: string | null;
    status: 'pending' | 'uploading' | 'success' | 'error';
    progress: number;
    error: string | null;
    category: string;
    caption: string;
    tags: Array<{ value: number | string; label: string }>;
}

const props = defineProps<{
    open: boolean;
    tags: Tag[];
    languages: Language[];
    users: User[];
    creditRoles: Record<string, string>;
    mediaCategories: MediaCategory[];
    defaultCategory?: string | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

// Upload configuration
const useSignedUrls = ref(false);
const uploadConfigLoaded = ref(false);

// File uploads
const uploadFiles = ref<BulkUploadFile[]>([]);
const isDragging = ref(false);

// Selection for bulk operations
const selectedFileIds = ref<Set<string>>(new Set());

// Default values
const defaultCategory = ref(props.defaultCategory || 'media');
const defaultCaption = ref('');
const defaultTags = ref<Array<{ value: number | string; label: string }>>([]);

// Credit (shared across all)
const creditType = ref<'user' | 'external'>('external');
const creditUserId = ref<string>('');
const creditName = ref('');
const creditUrl = ref('');
const creditRole = ref('');

// Currently editing file
const editingFileId = ref<string | null>(null);
const editModalOpen = ref(false);

function openEditModal(id: string) {
    editingFileId.value = id;
    editModalOpen.value = true;
}

function closeEditModal() {
    editModalOpen.value = false;
    editingFileId.value = null;
}

// Fetch upload configuration on mount
onMounted(async () => {
    await fetchUploadConfig();
});

async function fetchUploadConfig() {
    try {
        const response = await fetch('/cms/media/upload-config', {
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });
        if (response.ok) {
            const data = await response.json();
            useSignedUrls.value = data.use_signed_urls;
        }
    } catch (error) {
        // Fall back to direct upload
        useSignedUrls.value = false;
    }
    uploadConfigLoaded.value = true;
}

// Tag options for multi-select
const tagOptions = computed(() => {
    return props.tags.map(tag => ({
        value: tag.id,
        label: typeof tag.name === 'string' ? tag.name : (tag.name?.en || tag.slug),
    }));
});

// Handle creating a new tag
function onCreateTag(item: string) {
    const newTag = {
        value: `new-${Date.now()}`,
        label: item,
    };
    defaultTags.value = [...defaultTags.value, newTag];
}

function onCreateTagForFile(item: string, fileId: string) {
    const file = uploadFiles.value.find(f => f.id === fileId);
    if (file) {
        const newTag = {
            value: `new-${Date.now()}`,
            label: item,
        };
        file.tags = [...file.tags, newTag];
    }
}

// Available tags (excluding already selected for default)
const availableDefaultTagOptions = computed(() => {
    const selectedValues = new Set(defaultTags.value.map(t => t.value));
    return tagOptions.value.filter(t => !selectedValues.has(t.value));
});

// Add a tag to default tags
function addDefaultTag(tag: { value: number | string; label: string } | null) {
    if (tag && !defaultTags.value.some(t => t.value === tag.value)) {
        defaultTags.value = [...defaultTags.value, tag];
    }
}

// Remove a tag from default tags
function removeDefaultTag(tagValue: number | string) {
    defaultTags.value = defaultTags.value.filter(t => t.value !== tagValue);
}

// Available tags for a specific file (excluding already selected)
function getAvailableTagOptionsForFile(fileId: string) {
    const file = uploadFiles.value.find(f => f.id === fileId);
    if (!file) return tagOptions.value;
    const selectedValues = new Set(file.tags.map(t => t.value));
    return tagOptions.value.filter(t => !selectedValues.has(t.value));
}

// Add a tag to a specific file
function addTagToFile(fileId: string, tag: { value: number | string; label: string } | null) {
    if (!tag) return;
    const file = uploadFiles.value.find(f => f.id === fileId);
    if (file && !file.tags.some(t => t.value === tag.value)) {
        file.tags = [...file.tags, tag];
    }
}

// Remove a tag from a specific file
function removeTagFromFile(fileId: string, tagValue: number | string) {
    const file = uploadFiles.value.find(f => f.id === fileId);
    if (file) {
        file.tags = file.tags.filter(t => t.value !== tagValue);
    }
}

// User options
const userOptions = computed(() => {
    return props.users.map(u => ({
        value: String(u.id),
        label: u.name,
    }));
});

// Auto-assign role when user is selected
watch(creditUserId, (newUserId) => {
    if (newUserId && creditType.value === 'user') {
        const selectedUser = props.users.find(u => String(u.id) === newUserId);
        if (selectedUser?.role) {
            creditRole.value = selectedUser.role;
        }
    }
});

// Credit role options
const creditRoleOptions = computed(() => {
    return Object.entries(props.creditRoles).map(([value, label]) => ({
        value,
        label,
    }));
});

// Media category options
const categoryOptions = computed(() => {
    return props.mediaCategories.map(cat => ({
        value: cat.slug,
        label: cat.label,
    }));
});

// Get the effective default category
function getDefaultCategory(): string {
    return defaultCategory.value || props.defaultCategory || 'media';
}

// Parse category from filename
function parseCategoryFromFilename(filename: string): string {
    const lowerFilename = filename.toLowerCase();
    const nameWithoutExt = lowerFilename.replace(/\.[^/.]+$/, '');

    const sortedCategories = [...props.mediaCategories].sort((a, b) => b.slug.length - a.slug.length);

    for (const category of sortedCategories) {
        const slug = category.slug.toLowerCase();
        if (slug === 'media') continue;

        const patterns = [
            new RegExp(`^${slug}[-_]`),
            new RegExp(`[-_]${slug}$`),
            new RegExp(`[-_/]${slug}[-_/]`),
            new RegExp(`^${slug}$`),
            new RegExp(`\\b${slug}\\b`),
        ];

        for (const pattern of patterns) {
            if (pattern.test(nameWithoutExt)) {
                return category.slug;
            }
        }
    }

    return getDefaultCategory();
}

// File handling
function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        addFiles(Array.from(input.files));
    }
    input.value = '';
}

function handleDrop(event: DragEvent) {
    isDragging.value = false;
    if (event.dataTransfer?.files) {
        addFiles(Array.from(event.dataTransfer.files));
    }
}

function addFiles(files: File[]) {
    for (const file of files) {
        if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
            continue;
        }

        const fileId = crypto.randomUUID();

        const uploadFile: BulkUploadFile = {
            id: fileId,
            file,
            preview: null,
            progress: 0,
            status: 'pending',
            error: null,
            category: parseCategoryFromFilename(file.name),
            caption: defaultCaption.value,
            tags: [...defaultTags.value],
        };

        uploadFiles.value.push(uploadFile);
        selectedFileIds.value.add(fileId);

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const idx = uploadFiles.value.findIndex(f => f.id === fileId);
                if (idx !== -1) {
                    uploadFiles.value[idx].preview = e.target?.result as string;
                }
            };
            reader.readAsDataURL(file);
        }
    }
    selectedFileIds.value = new Set(selectedFileIds.value);
}

function removeFile(id: string) {
    const index = uploadFiles.value.findIndex(f => f.id === id);
    if (index !== -1) {
        uploadFiles.value.splice(index, 1);
    }
    selectedFileIds.value.delete(id);
    selectedFileIds.value = new Set(selectedFileIds.value);
    if (editingFileId.value === id) {
        editingFileId.value = null;
        editModalOpen.value = false;
    }
}

function retryFile(id: string) {
    const file = uploadFiles.value.find(f => f.id === id);
    if (file) {
        file.status = 'pending';
        file.error = null;
        file.progress = 0;
    }
}

async function retryUpload(id: string) {
    const uploadFile = uploadFiles.value.find(f => f.id === id);
    if (!uploadFile) return;

    uploadFile.status = 'uploading';
    uploadFile.progress = 0;
    uploadFile.error = null;

    try {
        if (useSignedUrls.value) {
            await uploadWithSignedUrl(uploadFile);
        } else {
            await uploadDirect(uploadFile);
        }
    } catch (error) {
        uploadFile.status = 'error';
        uploadFile.error = error instanceof Error ? error.message : 'Upload failed';
    }

    // Check if all done
    const allSuccess = uploadFiles.value.every(f => f.status === 'success');
    if (allSuccess) {
        router.reload();
        setTimeout(() => {
            isOpen.value = false;
            resetForm();
        }, 500);
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

// Selection management
function toggleSelect(id: string) {
    if (selectedFileIds.value.has(id)) {
        selectedFileIds.value.delete(id);
    } else {
        selectedFileIds.value.add(id);
    }
    selectedFileIds.value = new Set(selectedFileIds.value);
}

function selectAll() {
    if (selectedFileIds.value.size === uploadFiles.value.length) {
        selectedFileIds.value.clear();
    } else {
        selectedFileIds.value = new Set(uploadFiles.value.map(f => f.id));
    }
    selectedFileIds.value = new Set(selectedFileIds.value);
}

function selectFile(id: string) {
    // Single click just toggles selection
    toggleSelect(id);
}

// Get the currently editing file
const editingFile = computed(() => {
    if (!editingFileId.value) return null;
    return uploadFiles.value.find(f => f.id === editingFileId.value) || null;
});

// Apply defaults to selected files
function applyDefaultsToSelected() {
    for (const file of uploadFiles.value) {
        if (selectedFileIds.value.has(file.id) && file.status === 'pending') {
            file.category = defaultCategory.value;
            file.caption = defaultCaption.value;
            file.tags = [...defaultTags.value];
        }
    }
}

// Auto-apply default caption to selected files when it changes
watch(defaultCaption, (newCaption) => {
    for (const file of uploadFiles.value) {
        if (selectedFileIds.value.has(file.id) && file.status === 'pending') {
            file.caption = newCaption;
        }
    }
});

// Auto-apply default tags to selected files when they change
watch(defaultTags, (newTags) => {
    for (const file of uploadFiles.value) {
        if (selectedFileIds.value.has(file.id) && file.status === 'pending') {
            file.tags = [...newTags];
        }
    }
}, { deep: true });

// Auto-apply default category to selected files when it changes
watch(defaultCategory, (newCategory) => {
    for (const file of uploadFiles.value) {
        if (selectedFileIds.value.has(file.id) && file.status === 'pending') {
            file.category = newCategory;
        }
    }
});

// Upload handling
const isUploading = ref(false);
const hasSuccessfulUploads = ref(false);

async function uploadAll() {
    if (uploadFiles.value.length === 0) return;

    isUploading.value = true;

    for (const uploadFile of uploadFiles.value) {
        if (uploadFile.status === 'success') continue;

        uploadFile.status = 'uploading';
        uploadFile.progress = 0;

        try {
            if (useSignedUrls.value) {
                await uploadWithSignedUrl(uploadFile);
            } else {
                await uploadDirect(uploadFile);
            }
        } catch (error) {
            uploadFile.status = 'error';
            uploadFile.error = error instanceof Error ? error.message : 'Upload failed';
        }
    }

    isUploading.value = false;

    const allSuccess = uploadFiles.value.every(f => f.status === 'success');
    if (allSuccess) {
        router.reload();
        setTimeout(() => {
            isOpen.value = false;
            resetForm();
        }, 500);
    }
}

// Direct upload (for local disk)
async function uploadDirect(uploadFile: BulkUploadFile) {
    const formData = new FormData();
    formData.append('file', uploadFile.file);
    formData.append('category', uploadFile.category);

    if (uploadFile.caption) {
        formData.append('caption', uploadFile.caption);
    }

    for (const tag of uploadFile.tags) {
        if (typeof tag.value === 'number') {
            formData.append('tag_ids[]', String(tag.value));
        } else {
            formData.append('new_tags[]', tag.label);
        }
    }

    if (creditType.value === 'user' && creditUserId.value) {
        formData.append('credit_user_id', creditUserId.value);
    } else if (creditType.value === 'external' && creditName.value) {
        formData.append('credit_name', creditName.value);
        if (creditUrl.value) {
            formData.append('credit_url', creditUrl.value);
        }
    }

    if (creditRole.value) {
        formData.append('credit_role', creditRole.value);
    }

    const response = await fetch('/cms/media', {
        method: 'POST',
        body: formData,
        headers: {
            'X-XSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
    });

    if (response.ok) {
        uploadFile.status = 'success';
        uploadFile.progress = 100;
        hasSuccessfulUploads.value = true;
    } else {
        const data = await response.json();
        uploadFile.status = 'error';
        if (data.errors) {
            const firstError = Object.values(data.errors)[0];
            uploadFile.error = Array.isArray(firstError) ? firstError[0] : String(firstError);
        } else {
            uploadFile.error = data.message || data.error || 'Upload failed';
        }
    }
}

// Signed URL upload (for S3)
async function uploadWithSignedUrl(uploadFile: BulkUploadFile) {
    // Step 1: Get signed URL
    const signedUrlResponse = await fetch('/cms/media/signed-url', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            filename: uploadFile.file.name,
            content_type: uploadFile.file.type,
            size: uploadFile.file.size,
        }),
    });

    if (!signedUrlResponse.ok) {
        const errorData = await signedUrlResponse.json();
        throw new Error(errorData.error || errorData.message || 'Failed to get upload URL');
    }

    const { signed_url, path, headers } = await signedUrlResponse.json();

    uploadFile.progress = 10;

    // Step 2: Upload directly to S3
    const uploadHeaders: Record<string, string> = {
        'Content-Type': uploadFile.file.type,
        ...headers,
    };

    const uploadResponse = await fetch(signed_url, {
        method: 'PUT',
        body: uploadFile.file,
        headers: uploadHeaders,
    });

    if (!uploadResponse.ok) {
        throw new Error('Failed to upload file to storage');
    }

    uploadFile.progress = 70;

    // Step 3: Confirm upload with backend
    const tagIds: number[] = [];
    const newTags: string[] = [];

    for (const tag of uploadFile.tags) {
        if (typeof tag.value === 'number') {
            tagIds.push(tag.value);
        } else {
            newTags.push(tag.label);
        }
    }

    const confirmData: Record<string, unknown> = {
        path,
        filename: uploadFile.file.name,
        content_type: uploadFile.file.type,
        size: uploadFile.file.size,
        category: uploadFile.category,
    };

    if (uploadFile.caption) {
        confirmData.caption = uploadFile.caption;
    }

    if (tagIds.length > 0) {
        confirmData.tag_ids = tagIds;
    }

    if (newTags.length > 0) {
        confirmData.new_tags = newTags;
    }

    if (creditType.value === 'user' && creditUserId.value) {
        confirmData.credit_user_id = creditUserId.value;
    } else if (creditType.value === 'external' && creditName.value) {
        confirmData.credit_name = creditName.value;
        if (creditUrl.value) {
            confirmData.credit_url = creditUrl.value;
        }
    }

    if (creditRole.value) {
        confirmData.credit_role = creditRole.value;
    }

    const confirmResponse = await fetch('/cms/media/confirm-upload', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify(confirmData),
    });

    if (!confirmResponse.ok) {
        const errorData = await confirmResponse.json();
        throw new Error(errorData.error || errorData.message || 'Failed to confirm upload');
    }

    uploadFile.status = 'success';
    uploadFile.progress = 100;
    hasSuccessfulUploads.value = true;
}

function getCsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

function resetForm() {
    uploadFiles.value = [];
    selectedFileIds.value.clear();
    selectedFileIds.value = new Set();
    editingFileId.value = null;
    defaultCategory.value = props.defaultCategory || 'media';
    defaultCaption.value = '';
    defaultTags.value = [];
    creditType.value = 'external';
    creditUserId.value = '';
    creditName.value = '';
    creditUrl.value = '';
    creditRole.value = '';
    hasSuccessfulUploads.value = false;
}

function closeModal() {
    if (hasSuccessfulUploads.value) {
        router.reload();
    }
    isOpen.value = false;
}

// Reset when closed
watch(isOpen, (open) => {
    if (!open) {
        resetForm();
    } else {
        // Refresh upload config when opening
        fetchUploadConfig();
    }
});

// Update default category when prop changes
watch(() => props.defaultCategory, (newCategory) => {
    if (newCategory && uploadFiles.value.length === 0) {
        defaultCategory.value = newCategory;
    }
});

function setCreditType(type: 'user' | 'external') {
    creditType.value = type;
}

const pendingCount = computed(() => uploadFiles.value.filter(f => f.status === 'pending').length);
const successCount = computed(() => uploadFiles.value.filter(f => f.status === 'success').length);
const errorCount = computed(() => uploadFiles.value.filter(f => f.status === 'error').length);
const selectedCount = computed(() => selectedFileIds.value.size);

// Check if all pending files have caption and tags
const canUpload = computed(() => {
    if (uploadFiles.value.length === 0) return false;
    const pendingFiles = uploadFiles.value.filter(f => f.status === 'pending');
    if (pendingFiles.length === 0) return false;
    return pendingFiles.every(f => f.caption.trim() !== '' && f.tags.length > 0);
});

// Get files missing required fields
const filesMissingFields = computed(() => {
    return uploadFiles.value.filter(f =>
        f.status === 'pending' && (f.caption.trim() === '' || f.tags.length === 0)
    ).length;
});
</script>

<template>
    <UModal v-model:open="isOpen" :ui="{ width: 'max-w-6xl' }" fullscreen>
        <template #content>
            <div class="flex flex-col h-full bg-default">
                <!-- Header -->
                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-default shrink-0">
                    <div class="flex items-center gap-3">
                        <UButton
                            type="button"
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            @click="closeModal"
                        />
                        <div>
                            <h2 class="text-lg font-semibold text-highlighted">Bulk Upload</h2>
                            <p class="text-sm text-muted mt-0.5">Upload multiple files with shared metadata</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span v-if="filesMissingFields > 0" class="text-sm text-warning">
                            {{ filesMissingFields }} file{{ filesMissingFields > 1 ? 's' : '' }} missing caption or tags
                        </span>
                        <UButton
                            type="button"
                            color="primary"
                            icon="i-lucide-upload"
                            :loading="isUploading"
                            :disabled="!canUpload || isUploading"
                            @click="uploadAll"
                        >
                            Upload All
                        </UButton>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="flex flex-1 min-h-0">
                    <!-- Left Panel: Files -->
                    <div class="flex-1 flex flex-col border-r border-default overflow-hidden">
                        <!-- Drop Zone -->
                        <div
                            class="m-4 border-2 border-dashed rounded-lg p-6 text-center transition-colors shrink-0"
                            :class="isDragging ? 'border-primary bg-primary/5' : 'border-default'"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                        >
                            <UIcon name="i-lucide-upload-cloud" class="size-10 text-muted mx-auto mb-3" />
                            <p class="text-sm text-muted mb-2">
                                Drag and drop files here, or
                            </p>
                            <label>
                                <UButton
                                    type="button"
                                    color="primary"
                                    variant="soft"
                                    as="span"
                                    class="cursor-pointer"
                                >
                                    Browse Files
                                </UButton>
                                <input
                                    type="file"
                                    class="sr-only"
                                    accept="image/*,video/*"
                                    multiple
                                    @change="handleFileSelect"
                                />
                            </label>
                        </div>

                        <!-- File List Header -->
                        <div v-if="uploadFiles.length > 0" class="flex items-center justify-between px-4 py-2 border-b border-default shrink-0">
                            <div class="flex items-center gap-3">
                                <UCheckbox
                                    :model-value="selectedCount === uploadFiles.length && uploadFiles.length > 0"
                                    :indeterminate="selectedCount > 0 && selectedCount < uploadFiles.length"
                                    @update:model-value="selectAll"
                                />
                                <span class="text-sm font-medium">
                                    Files ({{ uploadFiles.length }})
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-muted">
                                <span v-if="successCount > 0" class="text-success">{{ successCount }} uploaded</span>
                                <span v-if="errorCount > 0" class="text-error">{{ errorCount }} failed</span>
                                <span v-if="pendingCount > 0">{{ pendingCount }} pending</span>
                            </div>
                        </div>

                        <!-- File Grid -->
                        <div class="flex-1 overflow-y-auto p-4">
                            <div v-if="uploadFiles.length > 0" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                                <div
                                    v-for="file in uploadFiles"
                                    :key="file.id"
                                    class="group relative rounded-lg border overflow-hidden cursor-pointer transition-all"
                                    :class="[
                                        selectedFileIds.has(file.id) ? 'ring-2 ring-primary border-primary bg-primary/5' : 'border-default hover:border-primary/50'
                                    ]"
                                    @click="selectFile(file.id)"
                                >
                                    <!-- Thumbnail -->
                                    <div class="aspect-square relative bg-muted/30">
                                        <img
                                            v-if="file.preview"
                                            :src="file.preview"
                                            class="w-full h-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center"
                                        >
                                            <UIcon
                                                :name="file.file.type.startsWith('video/') ? 'i-lucide-video' : 'i-lucide-image'"
                                                class="size-8 text-muted"
                                            />
                                        </div>

                                        <!-- Status Overlay -->
                                        <div
                                            v-if="file.status === 'uploading'"
                                            class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center gap-2"
                                        >
                                            <UIcon name="i-lucide-loader-2" class="size-6 text-white animate-spin" />
                                            <span v-if="file.progress > 0" class="text-xs text-white">{{ file.progress }}%</span>
                                        </div>
                                        <div
                                            v-else-if="file.status === 'success'"
                                            class="absolute inset-0 bg-success/20 flex items-center justify-center"
                                        >
                                            <UIcon name="i-lucide-check-circle" class="size-8 text-success" />
                                        </div>
                                        <UTooltip v-else-if="file.status === 'error'" :text="file.error || 'Upload failed'">
                                            <div class="absolute inset-0 bg-error/20 flex items-center justify-center">
                                                <UIcon name="i-lucide-x-circle" class="size-8 text-error" />
                                            </div>
                                        </UTooltip>

                                        <!-- Selection Checkbox -->
                                        <div class="absolute top-1 left-1">
                                            <UCheckbox
                                                :model-value="selectedFileIds.has(file.id)"
                                                @click.stop
                                                @update:model-value="toggleSelect(file.id)"
                                            />
                                        </div>

                                        <!-- Action Buttons (top right) - Pending -->
                                        <div v-if="file.status === 'pending'" class="absolute top-1 right-1 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <!-- Edit Button -->
                                            <UButton
                                                type="button"
                                                icon="i-lucide-pencil"
                                                color="neutral"
                                                variant="solid"
                                                size="xs"
                                                @click.stop="openEditModal(file.id)"
                                            />

                                            <!-- Remove Button -->
                                            <UButton
                                                type="button"
                                                icon="i-lucide-x"
                                                color="error"
                                                variant="solid"
                                                size="xs"
                                                @click.stop="removeFile(file.id)"
                                            />
                                        </div>

                                        <!-- Action Buttons (top right) - Error -->
                                        <div v-else-if="file.status === 'error'" class="absolute top-1 right-1 flex gap-1">
                                            <!-- Retry Button -->
                                            <UButton
                                                type="button"
                                                icon="i-lucide-refresh-cw"
                                                color="primary"
                                                variant="solid"
                                                size="xs"
                                                @click.stop="retryUpload(file.id)"
                                            />

                                            <!-- Remove Button -->
                                            <UButton
                                                type="button"
                                                icon="i-lucide-x"
                                                color="error"
                                                variant="solid"
                                                size="xs"
                                                @click.stop="removeFile(file.id)"
                                            />
                                        </div>
                                    </div>

                                    <!-- Info -->
                                    <div class="p-2">
                                        <p class="text-xs font-medium truncate">{{ file.file.name }}</p>
                                        <div class="flex items-center gap-1 mt-1 flex-wrap">
                                            <UBadge size="xs" color="neutral" variant="subtle">
                                                {{ categoryOptions.find(c => c.value === file.category)?.label || file.category }}
                                            </UBadge>
                                            <UBadge v-if="file.caption" size="xs" color="success" variant="subtle">
                                                <UIcon name="i-lucide-text" class="size-2.5" />
                                            </UBadge>
                                            <UBadge v-if="file.tags.length > 0" size="xs" color="info" variant="subtle">
                                                {{ file.tags.length }} tag{{ file.tags.length > 1 ? 's' : '' }}
                                            </UBadge>
                                        </div>
                                        <!-- Error Message -->
                                        <p v-if="file.status === 'error' && file.error" class="text-xs text-error mt-1 line-clamp-2">
                                            {{ file.error }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="flex flex-col items-center justify-center h-full text-muted">
                                <UIcon name="i-lucide-images" class="size-16 opacity-30 mb-4" />
                                <p class="text-sm">Drop files above or click Browse to get started</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel: Default Settings -->
                    <div class="w-80 flex flex-col overflow-hidden shrink-0">
                        <div class="flex-1 overflow-y-auto p-4 space-y-6">
                            <div>
                                <h3 class="text-sm font-semibold text-highlighted mb-4">Default Settings</h3>
                                <p class="text-xs text-muted mb-4">
                                    Changes here auto-apply to selected files. Hover over a file and click the pencil icon to edit individually.
                                </p>
                            </div>

                            <!-- Default Category -->
                            <UFormField label="Category">
                                <USelectMenu
                                    v-model="defaultCategory"
                                    :items="categoryOptions"
                                    value-key="value"
                                    class="w-full"
                                />
                            </UFormField>

                            <!-- Default Caption -->
                            <UFormField label="Caption">
                                <UInput
                                    v-model="defaultCaption"
                                    placeholder="Enter default caption..."
                                    class="w-full"
                                />
                            </UFormField>

                            <!-- Default Tags -->
                            <UFormField label="Tags">
                                <div class="space-y-2">
                                    <!-- Selected Tags as pills -->
                                    <div v-if="defaultTags.length > 0" class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="tag in defaultTags"
                                            :key="tag.value"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded text-sm font-medium"
                                        >
                                            {{ tag.label }}
                                            <button
                                                type="button"
                                                class="hover:text-primary/70 transition-colors"
                                                @click="removeDefaultTag(tag.value)"
                                            >
                                                <UIcon name="i-lucide-x" class="size-3" />
                                            </button>
                                        </span>
                                    </div>
                                    <!-- Add Tags Dropdown -->
                                    <USelectMenu
                                        :model-value="null"
                                        :items="availableDefaultTagOptions"
                                        placeholder="Add tags..."
                                        create-item
                                        class="w-full"
                                        @update:model-value="addDefaultTag"
                                        @create="onCreateTag"
                                    />
                                </div>
                            </UFormField>

                            <!-- Apply to Selected Button -->
                            <UButton
                                type="button"
                                color="neutral"
                                variant="outline"
                                icon="i-lucide-copy-check"
                                :disabled="selectedCount === 0"
                                class="w-full"
                                @click="applyDefaultsToSelected"
                            >
                                Apply to Selected ({{ selectedCount }})
                            </UButton>

                            <USeparator />

                            <!-- Credit Section -->
                            <div class="space-y-3">
                                <label class="text-sm font-medium text-highlighted">Credit (optional)</label>

                                <div class="flex gap-2">
                                    <UButton
                                        type="button"
                                        :color="creditType === 'external' ? 'primary' : 'neutral'"
                                        :variant="creditType === 'external' ? 'soft' : 'ghost'"
                                        size="sm"
                                        @click="setCreditType('external')"
                                    >
                                        External
                                    </UButton>
                                    <UButton
                                        type="button"
                                        :color="creditType === 'user' ? 'primary' : 'neutral'"
                                        :variant="creditType === 'user' ? 'soft' : 'ghost'"
                                        size="sm"
                                        @click="setCreditType('user')"
                                    >
                                        CMS User
                                    </UButton>
                                </div>

                                <template v-if="creditType === 'user'">
                                    <USelectMenu
                                        v-model="creditUserId"
                                        :items="userOptions"
                                        value-key="value"
                                        placeholder="Select user..."
                                        searchable
                                        class="w-full"
                                    />
                                </template>

                                <template v-else>
                                    <UInput
                                        v-model="creditName"
                                        placeholder="Photographer/Creator name"
                                        class="w-full"
                                    />
                                    <UInput
                                        v-model="creditUrl"
                                        placeholder="Website URL (optional)"
                                        icon="i-lucide-link"
                                        class="w-full"
                                    />
                                </template>

                                <USelectMenu
                                    v-model="creditRole"
                                    :items="creditRoleOptions"
                                    value-key="value"
                                    placeholder="Role (optional)"
                                    class="w-full"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </UModal>

    <!-- Edit File Modal (stacked) -->
    <UModal v-model:open="editModalOpen" :ui="{ width: 'max-w-lg' }">
        <template #content>
            <div v-if="editingFile" class="p-6">
                <!-- Header -->
                <div class="flex items-start gap-4 mb-6">
                    <!-- Preview -->
                    <div class="w-24 h-24 rounded-lg overflow-hidden bg-muted/30 shrink-0">
                        <img
                            v-if="editingFile.preview"
                            :src="editingFile.preview"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <UIcon
                                :name="editingFile.file.type.startsWith('video/') ? 'i-lucide-video' : 'i-lucide-image'"
                                class="size-8 text-muted"
                            />
                        </div>
                    </div>

                    <!-- File Info -->
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-highlighted truncate">
                            {{ editingFile.file.name }}
                        </h3>
                        <p class="text-sm text-muted mt-1">
                            {{ formatFileSize(editingFile.file.size) }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <UButton
                        type="button"
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        @click="closeEditModal"
                    />
                </div>

                <!-- Form Fields -->
                <div class="space-y-4">
                    <UFormField label="Category">
                        <USelectMenu
                            v-model="editingFile.category"
                            :items="categoryOptions"
                            value-key="value"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Caption">
                        <UInput
                            v-model="editingFile.caption"
                            placeholder="Enter caption..."
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Tags">
                        <div class="space-y-2">
                            <!-- Selected Tags as pills -->
                            <div v-if="editingFile.tags.length > 0" class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="tag in editingFile.tags"
                                    :key="tag.value"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded text-sm font-medium"
                                >
                                    {{ tag.label }}
                                    <button
                                        type="button"
                                        class="hover:text-primary/70 transition-colors"
                                        @click="removeTagFromFile(editingFile!.id, tag.value)"
                                    >
                                        <UIcon name="i-lucide-x" class="size-3" />
                                    </button>
                                </span>
                            </div>
                            <!-- Add Tags Dropdown -->
                            <USelectMenu
                                :model-value="null"
                                :items="getAvailableTagOptionsForFile(editingFile.id)"
                                placeholder="Add tags..."
                                create-item
                                class="w-full"
                                @update:model-value="(tag) => addTagToFile(editingFile!.id, tag)"
                                @create="(item: string) => onCreateTagForFile(item, editingFile!.id)"
                            />
                        </div>
                    </UFormField>

                    <p v-if="editingFile.error" class="text-sm text-error">{{ editingFile.error }}</p>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-default">
                    <UButton
                        type="button"
                        color="neutral"
                        variant="outline"
                        @click="closeEditModal"
                    >
                        Done
                    </UButton>
                </div>
            </div>
        </template>
    </UModal>
</template>
