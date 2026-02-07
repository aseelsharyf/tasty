<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useCmsPath } from '../composables/useCmsPath';

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

interface UploadFile {
    id: string;
    file: File;
    preview: string | null;
    progress: number;
    status: 'pending' | 'uploading' | 'success' | 'error';
    error: string | null;
    category: string;
    title: string;
    caption: string;
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

const { cmsPath } = useCmsPath();

// Upload mode
const uploadMode = ref<'file' | 'embed'>('file');

// Upload configuration
const useSignedUrls = ref(false);

// File uploads
const uploadFiles = ref<UploadFile[]>([]);
const isDragging = ref(false);

// Embed URL
const embedUrl = ref('');
const embedError = ref<string | null>(null);
const embedCategory = ref(props.defaultCategory || 'media');

// Default category for file uploads
const defaultFileCategory = ref(props.defaultCategory || 'media');

// Common fields
const title = ref('');
const caption = ref('');
const selectedTags = ref<Array<{ value: number | string; label: string }>>([]);
const creditType = ref<'user' | 'external'>('external');
const creditUserId = ref<string>('');
const creditName = ref('');
const creditUrl = ref('');
const creditRole = ref('');

// Tag options for multi-select
const tagOptions = computed(() => {
    return props.tags.map(tag => ({
        value: tag.id,
        label: typeof tag.name === 'string' ? tag.name : (tag.name?.en || tag.slug),
    }));
});

// Tag dropdown open state
const tagsDropdownOpen = ref(false);

// Available tags (excluding already selected)
const availableTagOptions = computed(() => {
    const selectedValues = new Set(selectedTags.value.map(t => t.value));
    return tagOptions.value.filter(t => !selectedValues.has(t.value));
});

// Handle creating a new tag
function onCreateTag(item: string) {
    const newTag = {
        value: `new-${Date.now()}`,
        label: item,
    };
    selectedTags.value = [...selectedTags.value, newTag];
    tagsDropdownOpen.value = false;
}

// Add a tag
function addTag(tag: { value: number | string; label: string } | null) {
    if (tag && !selectedTags.value.some(t => t.value === tag.value)) {
        selectedTags.value = [...selectedTags.value, tag];
    }
    tagsDropdownOpen.value = false;
}

// Remove a tag
function removeTag(tagValue: number | string) {
    selectedTags.value = selectedTags.value.filter(t => t.value !== tagValue);
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
    return defaultFileCategory.value || props.defaultCategory || 'media';
}

// Parse category from filename
function parseCategoryFromFilename(filename: string): string {
    const lowerFilename = filename.toLowerCase();

    // Remove file extension for cleaner matching
    const nameWithoutExt = lowerFilename.replace(/\.[^/.]+$/, '');

    // Check categories in order of specificity (longer slugs first to avoid partial matches)
    const sortedCategories = [...props.mediaCategories].sort((a, b) => b.slug.length - a.slug.length);

    for (const category of sortedCategories) {
        const slug = category.slug.toLowerCase();

        // Skip 'media' as it's too generic and is the default
        if (slug === 'media') continue;

        // Check for common naming patterns:
        // - prefix: "sponsors-logo.jpg", "sponsors_image.png"
        // - suffix: "logo-sponsors.jpg", "image_sponsors.png"
        // - folder-like: "sponsors/logo.jpg" (after upload, might be "sponsors-logo.jpg")
        // - standalone word boundary match
        const patterns = [
            new RegExp(`^${slug}[-_]`),           // starts with category
            new RegExp(`[-_]${slug}$`),           // ends with category
            new RegExp(`[-_/]${slug}[-_/]`),      // in the middle with separators
            new RegExp(`^${slug}$`),              // exact match (unlikely but possible)
            new RegExp(`\\b${slug}\\b`),          // word boundary match
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
        // Check if file type is acceptable
        if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
            continue;
        }

        const uploadFile: UploadFile = {
            id: crypto.randomUUID(),
            file,
            preview: null,
            progress: 0,
            status: 'pending',
            error: null,
            category: parseCategoryFromFilename(file.name),
            title: title.value,
            caption: caption.value,
        };

        uploadFiles.value.push(uploadFile);

        // Generate preview for images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const idx = uploadFiles.value.findIndex(f => f.id === uploadFile.id);
                if (idx !== -1) {
                    uploadFiles.value[idx] = { ...uploadFiles.value[idx], preview: e.target?.result as string };
                }
            };
            reader.readAsDataURL(file);
        }
    }
}

function removeFile(id: string) {
    const index = uploadFiles.value.findIndex(f => f.id === id);
    if (index !== -1) {
        uploadFiles.value.splice(index, 1);
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

// Upload handling
const isUploading = ref(false);
const hasSuccessfulUploads = ref(false); // Track if any uploads succeeded during this session

// Fetch upload configuration on mount
onMounted(async () => {
    try {
        const response = await fetch(cmsPath('/media/upload-config'), {
            headers: {
                'Accept': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });
        if (response.ok) {
            const data = await response.json();
            useSignedUrls.value = data.use_signed_urls;
        }
    } catch {
        useSignedUrls.value = false;
    }
});

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
        } catch (error: any) {
            uploadFile.status = 'error';
            uploadFile.error = error.message || 'Upload failed';
        }
    }

    isUploading.value = false;

    // Check if all succeeded
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
async function uploadDirect(uploadFile: UploadFile) {
    const formData = new FormData();
    formData.append('file', uploadFile.file);
    formData.append('category', uploadFile.category);

    appendSharedFields(formData);

    const response = await fetch(cmsPath('/media'), {
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
        if (data.errors) {
            const firstError = Object.values(data.errors)[0];
            throw new Error(Array.isArray(firstError) ? firstError[0] : String(firstError));
        }
        throw new Error(data.message || data.error || 'Upload failed');
    }
}

// Signed URL upload (for S3)
async function uploadWithSignedUrl(uploadFile: UploadFile) {
    // Step 1: Get signed URL
    const signedUrlResponse = await fetch(cmsPath('/media/signed-url'), {
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
    const uploadResponse = await fetch(signed_url, {
        method: 'PUT',
        body: uploadFile.file,
        headers: {
            'Content-Type': uploadFile.file.type,
            ...headers,
        },
    });

    if (!uploadResponse.ok) {
        throw new Error('Failed to upload file to storage');
    }

    uploadFile.progress = 70;

    // Step 3: Confirm upload with backend
    const tagIds: number[] = [];
    const newTags: string[] = [];

    for (const tag of selectedTags.value) {
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

    if (title.value) {
        confirmData.title = title.value;
    }

    if (caption.value) {
        confirmData.caption = caption.value;
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

    const confirmResponse = await fetch(cmsPath('/media/confirm-upload'), {
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

// Append shared fields to FormData
function appendSharedFields(formData: FormData) {
    if (title.value) {
        formData.append('title', title.value);
    }

    if (caption.value) {
        formData.append('caption', caption.value);
    }

    for (const tag of selectedTags.value) {
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
}

async function uploadEmbed() {
    if (!embedUrl.value) {
        embedError.value = 'Please enter a video URL';
        return;
    }

    isUploading.value = true;
    embedError.value = null;

    const formData = new FormData();
    formData.append('embed_url', embedUrl.value);
    formData.append('category', embedCategory.value);

    appendSharedFields(formData);

    try {
        const response = await fetch(cmsPath('/media'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            router.reload();
            setTimeout(() => {
                isOpen.value = false;
                resetForm();
            }, 500);
        } else {
            const data = await response.json();
            embedError.value = data.error || 'Failed to add video';
        }
    } catch (error) {
        embedError.value = 'Network error';
    }

    isUploading.value = false;
}

function getCsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

function resetForm() {
    uploadFiles.value = [];
    embedUrl.value = '';
    embedError.value = null;
    defaultFileCategory.value = props.defaultCategory || 'media';
    embedCategory.value = props.defaultCategory || 'media';
    title.value = '';
    caption.value = '';
    selectedTags.value = [];
    creditType.value = 'external';
    creditUserId.value = '';
    creditName.value = '';
    creditUrl.value = '';
    creditRole.value = '';
    hasSuccessfulUploads.value = false;
}

function closeSlideover() {
    // If uploads succeeded during this session, refresh the page so the list shows new items
    if (hasSuccessfulUploads.value) {
        router.reload();
    }
    isOpen.value = false;
}

// Reset when closed
watch(isOpen, (open) => {
    if (!open) {
        resetForm();
    }
});

// Update categories when default category changes
watch(() => props.defaultCategory, (newCategory) => {
    if (newCategory && uploadFiles.value.length === 0) {
        embedCategory.value = newCategory;
        defaultFileCategory.value = newCategory;
    }
});

// When default file category changes, update all pending files that haven't been manually changed
watch(defaultFileCategory, (newCategory) => {
    for (const file of uploadFiles.value) {
        if (file.status === 'pending') {
            file.category = newCategory;
        }
    }
});

function setUploadMode(mode: 'file' | 'embed') {
    uploadMode.value = mode;
}

function setCreditType(type: 'user' | 'external') {
    creditType.value = type;
}

const pendingCount = computed(() => uploadFiles.value.filter(f => f.status === 'pending').length);
const successCount = computed(() => uploadFiles.value.filter(f => f.status === 'success').length);
const errorCount = computed(() => uploadFiles.value.filter(f => f.status === 'error').length);
</script>

<template>
    <USlideover v-model:open="isOpen" :ui="{ width: 'max-w-lg' }">
        <template #content>
            <div class="flex flex-col h-full bg-default">
                <!-- Header -->
                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-default">
                    <div>
                        <h2 class="text-lg font-semibold text-highlighted">Upload Media</h2>
                        <p class="text-sm text-muted mt-0.5">Add photos, videos, or embed external videos</p>
                    </div>
                    <UButton
                        type="button"
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        @click="closeSlideover"
                    />
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6">
                    <!-- Upload Mode Toggle -->
                    <div class="flex gap-2">
                        <UButton
                            type="button"
                            :color="uploadMode === 'file' ? 'primary' : 'neutral'"
                            :variant="uploadMode === 'file' ? 'solid' : 'outline'"
                            icon="i-lucide-upload"
                            @click.stop="setUploadMode('file')"
                        >
                            Upload Files
                        </UButton>
                        <UButton
                            type="button"
                            :color="uploadMode === 'embed' ? 'primary' : 'neutral'"
                            :variant="uploadMode === 'embed' ? 'solid' : 'outline'"
                            icon="i-lucide-link"
                            @click.stop="setUploadMode('embed')"
                        >
                            Embed Video
                        </UButton>
                    </div>

                    <!-- File Upload Mode -->
                    <template v-if="uploadMode === 'file'">
                        <!-- Drop Zone -->
                        <div
                            class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                            :class="isDragging ? 'border-primary bg-primary/5' : 'border-default'"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                        >
                            <UIcon name="i-lucide-upload-cloud" class="size-12 text-muted mx-auto mb-4" />
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
                            <p class="text-xs text-muted mt-2">
                                Images and videos up to 100MB
                            </p>
                        </div>

                        <!-- File List -->
                        <div v-if="uploadFiles.length > 0" class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ uploadFiles.length }} file{{ uploadFiles.length !== 1 ? 's' : '' }}</span>
                                <div class="flex items-center gap-2 text-xs text-muted">
                                    <span v-if="successCount > 0" class="text-success">{{ successCount }} uploaded</span>
                                    <span v-if="errorCount > 0" class="text-error">{{ errorCount }} failed</span>
                                    <span v-if="pendingCount > 0">{{ pendingCount }} pending</span>
                                </div>
                            </div>

                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <div
                                    v-for="file in uploadFiles"
                                    :key="file.id"
                                    class="flex items-center gap-3 p-2 rounded-lg bg-muted/30"
                                >
                                    <!-- Preview -->
                                    <div class="size-12 rounded bg-muted/50 shrink-0 overflow-hidden">
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
                                                class="size-6 text-muted"
                                            />
                                        </div>
                                    </div>

                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium truncate">{{ file.file.name }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-muted">{{ formatFileSize(file.file.size) }}</span>
                                            <USelectMenu
                                                v-if="file.status === 'pending'"
                                                v-model="file.category"
                                                :items="categoryOptions"
                                                value-key="value"
                                                size="xs"
                                                class="w-24"
                                            />
                                            <UBadge v-else size="xs" color="neutral" variant="subtle">
                                                {{ categoryOptions.find(c => c.value === file.category)?.label || file.category }}
                                            </UBadge>
                                        </div>
                                        <p v-if="file.error" class="text-xs text-error">{{ file.error }}</p>
                                    </div>

                                    <!-- Status -->
                                    <div class="shrink-0">
                                        <UIcon
                                            v-if="file.status === 'success'"
                                            name="i-lucide-check-circle"
                                            class="size-5 text-success"
                                        />
                                        <UIcon
                                            v-else-if="file.status === 'error'"
                                            name="i-lucide-x-circle"
                                            class="size-5 text-error"
                                        />
                                        <UIcon
                                            v-else-if="file.status === 'uploading'"
                                            name="i-lucide-loader-2"
                                            class="size-5 text-primary animate-spin"
                                        />
                                        <UButton
                                            v-else
                                            type="button"
                                            icon="i-lucide-x"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            @click.stop="removeFile(file.id)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Embed Video Mode -->
                    <template v-else>
                        <UFormField label="Video URL" :error="embedError || undefined">
                            <UInput
                                v-model="embedUrl"
                                placeholder="https://youtube.com/watch?v=... or https://vimeo.com/..."
                                icon="i-lucide-link"
                                class="w-full"
                            />
                            <template #hint>
                                <span class="text-xs text-muted">Supports YouTube and Vimeo</span>
                            </template>
                        </UFormField>

                        <UFormField label="Category">
                            <USelectMenu
                                v-model="embedCategory"
                                :items="categoryOptions"
                                value-key="value"
                                class="w-full"
                            />
                        </UFormField>
                    </template>

                    <USeparator />

                    <!-- Common Options -->
                    <div class="space-y-4">
                        <!-- Category (for file uploads - applies to all files) -->
                        <UFormField v-if="uploadMode === 'file'" label="Category" hint="Default category for uploaded files">
                            <USelectMenu
                                v-model="defaultFileCategory"
                                :items="categoryOptions"
                                value-key="value"
                                class="w-full"
                            />
                        </UFormField>

                        <!-- Title -->
                        <UFormField label="Title">
                            <UInput
                                v-model="title"
                                placeholder="Enter default title..."
                                class="w-full"
                            />
                        </UFormField>

                        <!-- Caption -->
                        <UFormField label="Caption">
                            <UTextarea
                                v-model="caption"
                                placeholder="Enter default caption..."
                                class="w-full"
                                autoresize
                                :rows="2"
                            />
                        </UFormField>

                        <!-- Tags -->
                        <UFormField label="Tags">
                            <div class="space-y-2">
                                <!-- Selected Tags as pills -->
                                <div v-if="selectedTags.length > 0" class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="tag in selectedTags"
                                        :key="tag.value"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded text-sm font-medium"
                                    >
                                        {{ tag.label }}
                                        <button
                                            type="button"
                                            class="hover:text-primary/70 transition-colors"
                                            @click="removeTag(tag.value)"
                                        >
                                            <UIcon name="i-lucide-x" class="size-3" />
                                        </button>
                                    </span>
                                </div>
                                <!-- Add Tags Dropdown -->
                                <USelectMenu
                                    v-model:open="tagsDropdownOpen"
                                    :model-value="null"
                                    :items="availableTagOptions"
                                    placeholder="Add tags..."
                                    create-item
                                    class="w-full"
                                    @update:model-value="addTag"
                                    @create="onCreateTag"
                                />
                            </div>
                        </UFormField>

                        <!-- Credit -->
                        <div class="space-y-3">
                            <label class="text-sm font-medium text-highlighted">Credit</label>

                            <div class="flex gap-2">
                                <UButton
                                    type="button"
                                    :color="creditType === 'external' ? 'primary' : 'neutral'"
                                    :variant="creditType === 'external' ? 'soft' : 'ghost'"
                                    size="sm"
                                    @click.stop="setCreditType('external')"
                                >
                                    External
                                </UButton>
                                <UButton
                                    type="button"
                                    :color="creditType === 'user' ? 'primary' : 'neutral'"
                                    :variant="creditType === 'user' ? 'soft' : 'ghost'"
                                    size="sm"
                                    @click.stop="setCreditType('user')"
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

                <!-- Footer -->
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-default">
                    <UButton
                        type="button"
                        color="neutral"
                        variant="ghost"
                        @click="closeSlideover"
                    >
                        Cancel
                    </UButton>
                    <UButton
                        v-if="uploadMode === 'file'"
                        type="button"
                        color="primary"
                        :loading="isUploading"
                        :disabled="uploadFiles.length === 0 || !title.trim() || isUploading"
                        @click.stop="uploadAll"
                    >
                        Upload {{ uploadFiles.length }} File{{ uploadFiles.length !== 1 ? 's' : '' }}
                    </UButton>
                    <UButton
                        v-else
                        type="button"
                        color="primary"
                        :loading="isUploading"
                        :disabled="!embedUrl || isUploading"
                        @click.stop="uploadEmbed"
                    >
                        Add Video
                    </UButton>
                </div>
            </div>
        </template>
    </USlideover>
</template>
