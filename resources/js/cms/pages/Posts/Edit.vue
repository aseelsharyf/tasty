<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BlockEditor, { type MediaSelectCallback } from '../../components/BlockEditor.vue';
import MediaPickerModal from '../../components/MediaPickerModal.vue';
import NotificationDropdown from '../../components/NotificationDropdown.vue';
import EditorialSlideover from '../../components/EditorialSlideover.vue';
import type { MediaBlockItem } from '../../editor-tools/MediaBlock';
import { useSidebar } from '../../composables/useSidebar';
import { useDhivehiKeyboard } from '../../composables/useDhivehiKeyboard';
import { useWorkflow, type WorkflowConfig } from '../../composables/useWorkflow';
import type { Category, Tag, PostTypeOption, Post } from '../../types';

interface MediaItem {
    id: number;
    uuid: string;
    type: 'image' | 'video_local' | 'video_embed';
    url: string | null;
    thumbnail_url: string | null;
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
}

interface LanguageInfo {
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_rtl: boolean;
}

interface WorkflowState {
    key: string;
    label: string;
    color: string;
    icon: string;
}

interface WorkflowTransition {
    from: string;
    to: string;
    roles: string[];
    label: string;
}

interface WorkflowConfig {
    name: string;
    states: WorkflowState[];
    transitions: WorkflowTransition[];
    publish_roles: string[];
}

// Sidebar control
const { hide: hideSidebar, show: showSidebar } = useSidebar();

interface PostTypeConfig {
    key: string;
    label: string;
    fields?: {
        name: string;
        label: string;
        type: 'text' | 'number' | 'textarea' | 'select' | 'toggle' | 'repeater';
        suffix?: string;
        options?: string[];
    }[];
}

interface VersionListItem {
    uuid: string;
    version_number: number;
    workflow_status: string;
    is_active: boolean;
    is_current: boolean;
    is_draft: boolean;
    created_by: { name: string } | null;
    created_at: string;
}

const props = defineProps<{
    post: Post & {
        category_id: number | null;
        tags: number[];
        language_code: string;
        featured_media?: MediaItem | null;
        featured_media_id?: number | null;
        workflow_status?: string;
        current_version_uuid?: string | null;
    };
    categories: Category[];
    tags: Tag[];
    postTypes: PostTypeOption[];
    currentPostType?: PostTypeConfig | null;
    language: LanguageInfo | null;
    workflowConfig: WorkflowConfig;
    versionsList?: VersionListItem[];
}>();

// Workflow state
const workflowStatus = ref(props.post.workflow_status || 'draft');
const currentVersionUuid = ref(props.post.current_version_uuid || null);

// Check if the post itself is published (for UI indicators)
const isPublished = computed(() => props.post.status === 'published');

// Check if the current VERSION being viewed is editable
// A version is read-only if:
// 1. It's the active/published version (is_active = true), OR
// 2. It's not in draft status (already submitted for review, approved, etc.)
const currentVersionInfo = computed(() => {
    return props.versionsList?.find(v => v.uuid === currentVersionUuid.value);
});

const isCurrentVersionDraft = computed(() => {
    return currentVersionInfo.value?.workflow_status === 'draft';
});

const isCurrentVersionActive = computed(() => {
    return currentVersionInfo.value?.is_active === true;
});

// Read-only if viewing the published/active version OR if the version is not in draft status
const isReadOnly = computed(() => {
    // If viewing the active (published) version, it's read-only
    if (isCurrentVersionActive.value) return true;
    // If the current version is not a draft, it's read-only (in review, approved, etc.)
    if (!isCurrentVersionDraft.value) return true;
    return false;
});

// Editorial slideover state
const editorialSlideoverOpen = ref(false);

// Delete confirmation modal state
const deleteModalOpen = ref(false);
const isDeleting = ref(false);

// Unpublish loading state
const isUnpublishing = ref(false);

// Create new draft loading state
const creatingNewDraft = ref(false);

// Use workflow composable
const workflow = useWorkflow(props.workflowConfig);
const transitionLoading = workflow.loading;
const toast = useToast();

// Get the current workflow state from config (for badge styling)
const currentWorkflowState = computed(() => workflow.getState(workflowStatus.value));

// Available workflow transitions from current state
const availableTransitions = computed(() => workflow.getAvailableTransitions(workflowStatus.value));

// Workflow transition handling
async function performQuickTransition(transition: { from: string; to: string; label: string }) {
    if (!currentVersionUuid.value) {
        toast.add({ title: 'Error', description: 'Save the post first before transitioning', color: 'error' });
        return;
    }

    const result = await workflow.transition(currentVersionUuid.value, transition.to);
    if (result.success) {
        toast.add({ title: 'Success', description: `Moved to ${workflow.getStateLabel(transition.to)}`, color: 'success' });
        workflowStatus.value = transition.to;
        refreshPage();
    } else {
        toast.add({ title: 'Error', description: result.error || 'Transition failed', color: 'error' });
    }
}

// Handle workflow transition from slideover
function onWorkflowTransition(newStatus: string) {
    workflowStatus.value = newStatus;
}

// Handle workflow refresh after transition
function refreshPage() {
    router.reload({ only: ['post', 'workflowConfig', 'versionsList'] });
}

// Version switcher - computed options for dropdown
const versionDropdownItems = computed(() => {
    if (!props.versionsList?.length) return [];
    return props.versionsList.map(v => {
        // Build a clear suffix based on version state
        let suffix = '';
        if (v.is_active) {
            suffix = 'Live';
        } else {
            // Show the workflow status label
            const stateConfig = workflow.getState(v.workflow_status);
            suffix = stateConfig.label;
        }
        return {
            label: `v${v.version_number}`,
            value: v.uuid,
            suffix,
            is_current: v.is_current,
        };
    });
});

// Current version display label
const currentVersionLabel = computed(() => {
    const current = props.versionsList?.find(v => v.uuid === currentVersionUuid.value);
    if (!current) return 'v1';
    let label = `v${current.version_number}`;
    if (current.is_active) label += ' (Live)';
    else if (current.is_draft) label += ' (Draft)';
    return label;
});

// Switch to a different version via URL
function switchToVersion(versionUuid: string) {
    if (versionUuid === currentVersionUuid.value) return;
    const url = `/cms/posts/${props.post.language_code}/${props.post.uuid}/edit?version=${versionUuid}`;
    router.visit(url, { preserveState: false });
}

// Dhivehi keyboard for RTL content
const isRtl = computed(() => props.language?.direction === 'rtl');
const {
    enabled: dhivehiEnabled,
    toggle: toggleDhivehi,
    handleKeyDown: dhivehiKeyDown,
    layout: dhivehiLayout,
    layoutOptions,
} = useDhivehiKeyboard({ defaultEnabled: isRtl.value });

// Compute RTL state based on language
const textDirection = computed(() => isRtl.value ? 'rtl' : 'ltr');
const textAlign = computed(() => isRtl.value ? 'text-right' : 'text-left');

// Dhivehi placeholders
const placeholders = computed(() => ({
    title: isRtl.value ? 'ސުރުޚީ' : 'Title',
    subtitle: isRtl.value ? 'ސަބް ސުރުޚީ...' : 'Add a subtitle...',
    excerpt: isRtl.value ? 'ކުރު ޚުލާސާއެއް ލިޔޭ...' : 'Write a brief excerpt or summary...',
}));

// Handle keydown for Dhivehi input fields
function onDhivehiKeyDown(e: KeyboardEvent) {
    if (isRtl.value) {
        dhivehiKeyDown(e, e.target as HTMLInputElement | HTMLTextAreaElement);
    }
}

const form = useForm({
    title: props.post.title,
    subtitle: props.post.subtitle || '',
    slug: props.post.slug,
    excerpt: props.post.excerpt || '',
    content: props.post.content,
    post_type: props.post.post_type,
    scheduled_at: props.post.scheduled_at || '',
    category_id: props.post.category_id ?? null,
    tags: props.post.tags || [],
    featured_media_id: props.post.featured_media_id ?? null,
    custom_fields: (props.post.custom_fields || {}) as Record<string, unknown>,
    meta_title: props.post.meta_title || '',
    meta_description: props.post.meta_description || '',
});

// Get current post type config - either from props or find it from postTypes
const currentPostType = computed<PostTypeConfig | null>(() => {
    // First check if passed from backend
    if (props.currentPostType) {
        return props.currentPostType;
    }
    // Otherwise find from postTypes list based on form's current selection
    const found = props.postTypes.find(pt => pt.value === form.post_type);
    if (found && 'fields' in found) {
        return {
            key: found.value,
            label: found.label,
            fields: (found as any).fields || [],
        };
    }
    return null;
});

// Title textarea ref for auto-resize
const titleTextarea = ref<HTMLTextAreaElement | null>(null);

// Auto-resize title textarea to fit content
function autoResizeTitle() {
    const textarea = titleTextarea.value;
    if (textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
}

// Unified media picker state
const mediaPickerOpen = ref(false);
const mediaPickerType = ref<'all' | 'images' | 'videos'>('images');
const mediaPickerMultiple = ref(false);
const mediaPickerPurpose = ref<'cover' | 'editor'>('cover');
const selectedFeaturedMedia = ref<MediaItem | null>(props.post.featured_media || null);

// For editor callback
let editorMediaResolve: ((items: MediaBlockItem[] | null) => void) | null = null;

// Open media picker for cover photo
function openCoverPicker() {
    mediaPickerType.value = 'images';
    mediaPickerMultiple.value = false;
    mediaPickerPurpose.value = 'cover';
    mediaPickerOpen.value = true;
}

// Callback for BlockEditor to open media picker
const handleEditorSelectMedia: MediaSelectCallback = ({ multiple }) => {
    return new Promise((resolve) => {
        mediaPickerType.value = 'all';
        mediaPickerMultiple.value = multiple;
        mediaPickerPurpose.value = 'editor';
        editorMediaResolve = resolve;
        mediaPickerOpen.value = true;
    });
};

// Handle media selection from unified picker
function handleMediaSelect(items: MediaItem[]) {
    if (mediaPickerPurpose.value === 'cover') {
        // Cover photo selection
        if (items.length > 0) {
            const item = items[0];
            selectedFeaturedMedia.value = item;
            form.featured_media_id = item.id;
        }
    } else if (mediaPickerPurpose.value === 'editor' && editorMediaResolve) {
        // Editor block selection - convert to MediaBlockItem format
        const blockItems: MediaBlockItem[] = items.map(item => ({
            id: item.id,
            uuid: item.uuid,
            url: item.url || '',
            thumbnail_url: item.thumbnail_url,
            title: item.title,
            alt_text: item.alt_text,
            caption: item.caption || item.title || null,
            credit_display: item.credit_display || null,
            is_image: item.is_image === true,
            is_video: item.is_video === true,
        }));
        editorMediaResolve(blockItems.length > 0 ? blockItems : null);
        editorMediaResolve = null;
    }
}

// Handle picker close without selection
function handleMediaPickerClose(open: boolean) {
    if (!open && mediaPickerPurpose.value === 'editor' && editorMediaResolve) {
        editorMediaResolve(null);
        editorMediaResolve = null;
    }
}

// Sidebar toggle for mobile
const sidebarOpen = ref(false);

// Fullscreen mode
const isFullscreen = ref(false);

function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
    // Also hide/show the main sidebar
    if (isFullscreen.value) {
        hideSidebar();
    } else {
        showSidebar();
    }
}

// Auto-save functionality
const autoSaveTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const lastSaved = ref<Date | null>(null);
const isSaving = ref(false);

function autoSave() {
    // Don't auto-save published posts (read-only)
    if (isReadOnly.value) return;

    // Only auto-save if there's a title
    if (!form.title.trim()) return;

    // Don't auto-save while modal is open
    if (mediaPickerOpen.value) return;

    // Clear existing timer
    if (autoSaveTimer.value) {
        clearTimeout(autoSaveTimer.value);
    }

    // Set new timer for 3 seconds after last change
    autoSaveTimer.value = setTimeout(async () => {
        if (form.processing || isSaving.value) return;

        // Double-check modal isn't open when timer fires
        if (mediaPickerOpen.value) return;

        isSaving.value = true;

        const langCode = props.language?.code || props.post.language_code || 'en';
        form.post(`/cms/posts/${langCode}/${props.post.uuid}`, {
            forceFormData: true,
            headers: { 'X-HTTP-Method-Override': 'PUT' },
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                lastSaved.value = new Date();
                isSaving.value = false;
            },
            onError: () => {
                isSaving.value = false;
            },
        });
    }, 3000);
}

// Watch for changes to trigger auto-save
watch(
    () => [form.title, form.subtitle, form.excerpt, form.content],
    () => {
        autoSave();
    },
    { deep: true }
);

// Keyboard shortcut for fullscreen (Escape to exit)
function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && isFullscreen.value) {
        isFullscreen.value = false;
        showSidebar();
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    // Auto-resize title on mount if there's existing content
    nextTick(() => autoResizeTitle());
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
    if (autoSaveTimer.value) {
        clearTimeout(autoSaveTimer.value);
    }
    // Restore sidebar when leaving the page
    showSidebar();
});

// Format last saved time
const lastSavedText = computed(() => {
    if (!lastSaved.value) return null;
    const now = new Date();
    const diff = Math.floor((now.getTime() - lastSaved.value.getTime()) / 1000);
    if (diff < 60) return 'Saved just now';
    if (diff < 3600) return `Saved ${Math.floor(diff / 60)}m ago`;
    return `Saved at ${lastSaved.value.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
});

function removeFeaturedMedia() {
    selectedFeaturedMedia.value = null;
    form.featured_media_id = null;
}

// Category and tag options for sidebar
const flattenedCategories = computed(() => {
    const flatten = (cats: Category[], depth = 0): { label: string; value: number }[] => {
        return cats.flatMap((cat) => {
            const prefix = '— '.repeat(depth);
            const result = [{ label: prefix + cat.name, value: cat.id }];
            if (cat.children && cat.children.length > 0) {
                result.push(...flatten(cat.children, depth + 1));
            }
            return result;
        });
    };
    return flatten(props.categories);
});

const tagOptions = computed(() =>
    props.tags.map((tag) => ({ label: tag.name, value: tag.id }))
);

function generateSlug() {
    form.slug = form.title
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
}

// Extract text from Editor.js blocks
function extractTextFromBlocks(content: any): string {
    if (!content?.blocks) return '';

    let text = '';
    for (const block of content.blocks) {
        if (block.data?.text) {
            // Strip HTML tags
            text += ' ' + block.data.text.replace(/<[^>]*>/g, '');
        }
        if (block.data?.items) {
            for (const item of block.data.items) {
                if (typeof item === 'string') {
                    text += ' ' + item.replace(/<[^>]*>/g, '');
                } else if (item.content) {
                    text += ' ' + item.content.replace(/<[^>]*>/g, '');
                }
            }
        }
        if (block.data?.caption) {
            text += ' ' + block.data.caption.replace(/<[^>]*>/g, '');
        }
    }
    return text.trim();
}

const contentText = computed(() => extractTextFromBlocks(form.content));
const wordCount = computed(() => {
    if (!contentText.value) return 0;
    return contentText.value.split(/\s+/).filter(Boolean).length;
});
const charCount = computed(() => contentText.value.length);

// Reading time estimate (200 words per minute)
const readingTime = computed(() => {
    const minutes = Math.ceil(wordCount.value / 200);
    return minutes < 1 ? 'Less than 1 min' : `${minutes} min read`;
});

// Time ago helper
const timeAgo = computed(() => {
    const date = new Date(props.post.updated_at);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    return 'just now';
});

function submit() {
    const langCode = props.language?.code || props.post.language_code || 'en';
    form.post(`/cms/posts/${langCode}/${props.post.uuid}`, {
        forceFormData: true,
        headers: {
            'X-HTTP-Method-Override': 'PUT',
        },
    });
}

function goBack() {
    const langCode = props.language?.code || props.post.language_code || 'en';
    router.visit(`/cms/posts/${langCode}`);
}

function openDeleteModal() {
    deleteModalOpen.value = true;
}

function deletePost() {
    isDeleting.value = true;
    const langCode = props.language?.code || props.post.language_code || 'en';
    router.delete(`/cms/posts/${langCode}/${props.post.uuid}`, {
        onSuccess: () => {
            deleteModalOpen.value = false;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}

// Unpublish confirmation modal
const unpublishModalOpen = ref(false);

function openUnpublishModal() {
    unpublishModalOpen.value = true;
}

// Quick unpublish - transitions to draft status
async function unpublishPost() {
    if (!currentVersionUuid.value) {
        toast.add({ title: 'Error', description: 'No version to unpublish', color: 'error' });
        return;
    }

    isUnpublishing.value = true;
    try {
        const result = await workflow.transition(currentVersionUuid.value, 'draft');
        if (result.success) {
            toast.add({ title: 'Unpublished', description: 'Post has been unpublished and moved to draft', color: 'success' });
            workflowStatus.value = 'draft';
            unpublishModalOpen.value = false;
            // Full page reload to ensure all props are updated correctly (including post.status for isReadOnly)
            router.visit(window.location.href, { preserveState: false });
        } else {
            toast.add({ title: 'Error', description: result.error || 'Failed to unpublish', color: 'error' });
        }
    } catch (error: any) {
        toast.add({ title: 'Error', description: error.message || 'Failed to unpublish', color: 'error' });
    } finally {
        isUnpublishing.value = false;
    }
}

// Get the active (published) version UUID
const activeVersionUuid = computed(() => {
    return props.versionsList?.find(v => v.is_active)?.uuid || null;
});

// Check if there's already a draft version (other than the current active one)
// Check both is_draft flag and workflow_status as a fallback
const existingDraftVersion = computed(() => {
    return props.versionsList?.find(v =>
        (v.is_draft || v.workflow_status === 'draft') && !v.is_active
    ) || null;
});

// Create a new draft version from the published version
async function createNewDraftVersion() {
    // If there's already an existing draft, switch to it instead of creating a new one
    if (existingDraftVersion.value) {
        toast.add({
            title: 'Draft Already Exists',
            description: 'Switching to existing draft version.',
            color: 'info'
        });
        switchToVersion(existingDraftVersion.value.uuid);
        return;
    }

    // Use the active (published) version to create the draft, not the current version
    const sourceVersionUuid = activeVersionUuid.value || currentVersionUuid.value;

    if (!sourceVersionUuid) {
        console.error('createNewDraftVersion: No source version UUID found', { activeVersionUuid: activeVersionUuid.value, currentVersionUuid: currentVersionUuid.value });
        toast.add({ title: 'Error', description: 'No version to create draft from. Please contact support.', color: 'error' });
        return;
    }

    creatingNewDraft.value = true;
    try {
        await axios.post(`/cms/workflow/versions/${sourceVersionUuid}/revert`);
        toast.add({ title: 'Draft Created', description: 'New draft version created. You can now make changes.', color: 'success' });
        refreshPage();
    } catch (error: any) {
        console.error('createNewDraftVersion: Failed to create draft', error);
        toast.add({ title: 'Error', description: error.response?.data?.message || 'Failed to create draft', color: 'error' });
    } finally {
        creatingNewDraft.value = false;
    }
}

// Publish the current approved version
async function publishApprovedVersion() {
    if (!currentVersionUuid.value) return;

    const result = await workflow.transition(currentVersionUuid.value, 'published');
    if (result.success) {
        toast.add({ title: 'Published', description: 'Post has been published successfully', color: 'success' });
        workflowStatus.value = 'published';
        refreshPage();
    } else {
        toast.add({ title: 'Error', description: result.error || 'Failed to publish', color: 'error' });
    }
}

function openPreview() {
    // Open preview in new tab
    const langCode = props.language?.code || props.post.language_code || 'en';
    window.open(`/${langCode}/preview/${props.post.uuid}?version=${currentVersionUuid.value}`, '_blank');
}

function openDiff() {
    // Navigate to diff view (placeholder for now)
    toast.add({ title: 'Coming Soon', description: 'Diff view is under development', color: 'info' });
}
</script>

<template>
    <Head :title="`Edit: ${post.title}`" />

    <DashboardLayout>
        <UDashboardPanel id="edit-post" :ui="{ body: 'p-0 gap-0' }">
            <template #header>
                <UDashboardNavbar>
                    <template #leading>
                        <div class="flex items-center gap-3">
                            <UDashboardSidebarCollapse />
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-arrow-left"
                                size="sm"
                                @click="goBack"
                            />
                            <div class="h-4 w-px bg-default" />
                            <span class="text-sm text-muted">Editing</span>
                            <UBadge
                                :color="currentWorkflowState.color as any"
                                variant="subtle"
                                size="sm"
                            >
                                <UIcon :name="currentWorkflowState.icon" class="size-3 mr-1" />
                                {{ currentWorkflowState.label }}
                            </UBadge>
                            <!-- Version Switcher Dropdown -->
                            <UDropdownMenu
                                v-if="versionDropdownItems.length > 1"
                                :items="versionDropdownItems.map(v => ({
                                    label: v.label + (v.suffix ? ` (${v.suffix})` : ''),
                                    icon: v.is_current ? 'i-lucide-check' : 'i-lucide-git-branch',
                                    onSelect: () => switchToVersion(v.value),
                                }))"
                            >
                                <UButton
                                    color="neutral"
                                    variant="soft"
                                    size="xs"
                                    trailing-icon="i-lucide-chevron-down"
                                >
                                    <UIcon name="i-lucide-history" class="size-3 mr-1" />
                                    {{ currentVersionLabel }}
                                </UButton>
                            </UDropdownMenu>
                            <!-- Quick Unpublish button for published posts -->
                            <UButton
                                v-if="isPublished"
                                color="error"
                                variant="soft"
                                size="xs"
                                @click="openUnpublishModal"
                            >
                                <UIcon name="i-lucide-globe-lock" class="size-3 mr-1" />
                                Unpublish
                            </UButton>
                            <UBadge
                                v-if="language"
                                :color="isRtl ? 'warning' : 'primary'"
                                variant="subtle"
                                size="sm"
                            >
                                <span :class="isRtl ? 'font-dhivehi' : ''">{{ language.native_name }}</span>
                            </UBadge>
                        </div>
                    </template>

                    <template #right>
                        <div class="flex items-center gap-2">
                            <!-- Auto-save status -->
                            <span v-if="isSaving" class="text-xs text-muted flex items-center gap-1">
                                <UIcon name="i-lucide-loader-2" class="size-3 animate-spin" />
                                Saving...
                            </span>
                            <span v-else-if="lastSavedText" class="text-xs text-muted hidden sm:inline">
                                {{ lastSavedText }}
                            </span>

                            <div class="h-4 w-px bg-default hidden sm:block" />

                            <!-- Dhivehi keyboard toggle (only for RTL languages) -->
                            <UTooltip v-if="isRtl" :text="dhivehiEnabled ? 'Dhivehi keyboard ON' : 'Dhivehi keyboard OFF'">
                                <UButton
                                    color="neutral"
                                    :variant="dhivehiEnabled ? 'soft' : 'ghost'"
                                    icon="i-lucide-keyboard"
                                    size="sm"
                                    :class="dhivehiEnabled ? 'text-warning' : ''"
                                    @click="toggleDhivehi"
                                />
                            </UTooltip>

                            <!-- Notifications -->
                            <NotificationDropdown />

                            <!-- Editorial Slideover Toggle -->
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-message-square"
                                size="sm"
                                @click="editorialSlideoverOpen = true"
                            />

                            <!-- Fullscreen toggle -->
                            <UButton
                                color="neutral"
                                variant="ghost"
                                :icon="isFullscreen ? 'i-lucide-minimize-2' : 'i-lucide-maximize-2'"
                                size="sm"
                                @click="toggleFullscreen"
                            />

                            <UButton
                                v-if="!isReadOnly && workflowStatus !== 'approved'"
                                color="primary"
                                size="sm"
                                :loading="form.processing"
                                @click="submit()"
                            >
                                Save Draft
                            </UButton>

                            <!-- Publish Button for Approved Versions -->
                            <UButton
                                v-if="workflowStatus === 'approved'"
                                color="success"
                                size="sm"
                                :loading="transitionLoading"
                                icon="i-lucide-rocket"
                                @click="publishApprovedVersion"
                            >
                                Publish
                            </UButton>

                            <!-- Preview Button -->
                             <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-eye"
                                size="sm"
                                @click="openPreview"
                                title="Preview"
                            />

                            <!-- Diff Button (if not draft) -->
                            <UButton
                                v-if="!isReadOnly && activeVersionUuid"
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-git-compare"
                                size="sm"
                                @click="openDiff"
                                title="Compare with Live"
                            />
                            <!-- Mobile sidebar toggle -->
                            <UButton
                                v-if="!isFullscreen"
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-panel-right"
                                size="sm"
                                class="lg:hidden"
                                @click="sidebarOpen = !sidebarOpen"
                            />
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex h-full relative">
                    <!-- Mobile sidebar overlay -->
                    <div
                        v-if="sidebarOpen && !isFullscreen"
                        class="fixed inset-0 bg-black/50 z-50 lg:hidden"
                        @click="sidebarOpen = false"
                    />

                    <!-- Main Editor Area -->
                    <div
                        :class="[
                            'flex-1 overflow-y-auto transition-all duration-300',
                            isFullscreen ? 'absolute inset-0 z-50 bg-[var(--ui-bg)]' : '',
                        ]"
                    >
                        <div :class="['mx-auto px-6 py-12', isFullscreen ? 'max-w-screen-2xl' : 'max-w-2xl']">
                            <!-- Read-only notice -->
                            <div v-if="isReadOnly" class="mb-6 p-4 rounded-lg bg-elevated border border-default">
                                <div class="flex items-start gap-3">
                                    <UIcon name="i-lucide-lock" class="size-5 text-muted shrink-0 mt-0.5" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">
                                            {{ isCurrentVersionActive ? 'This is the published version' : 'This version is in review' }}
                                        </p>
                                        <p class="text-xs text-muted mt-1">
                                            <template v-if="isCurrentVersionActive">
                                                Published content is read-only. To make changes, {{ existingDraftVersion ? 'edit the existing draft' : 'create a new draft version' }}.
                                            </template>
                                            <template v-else>
                                                This version is currently in the workflow ({{ currentVersionInfo?.workflow_status }}). Only draft versions can be edited.
                                            </template>
                                        </p>
                                        <div v-if="isCurrentVersionActive" class="flex flex-wrap gap-2 mt-3">
                                            <!-- Show "Edit Draft" if draft exists, otherwise "Create New Draft" -->
                                            <UButton
                                                v-if="existingDraftVersion"
                                                size="sm"
                                                color="primary"
                                                icon="i-lucide-edit"
                                                @click="switchToVersion(existingDraftVersion.uuid)"
                                            >
                                                Edit Draft (v{{ existingDraftVersion.version_number }})
                                            </UButton>
                                            <UButton
                                                v-else
                                                size="sm"
                                                color="primary"
                                                icon="i-lucide-file-plus"
                                                :loading="creatingNewDraft"
                                                @click="createNewDraftVersion"
                                            >
                                                Create New Draft
                                            </UButton>
                                            <UButton
                                                size="sm"
                                                color="error"
                                                variant="soft"
                                                icon="i-lucide-globe-lock"
                                                @click="openUnpublishModal"
                                            >
                                                Unpublish
                                            </UButton>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Title -->
                            <textarea
                                ref="titleTextarea"
                                v-model="form.title"
                                :placeholder="placeholders.title"
                                :dir="textDirection"
                                :readonly="isReadOnly"
                                rows="1"
                                :class="[
                                    'w-full font-bold bg-transparent border-0 outline-none placeholder:text-muted/40 mb-2 resize-none overflow-hidden',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-4xl leading-relaxed placeholder:font-dhivehi' : 'text-4xl leading-tight',
                                    isReadOnly ? 'cursor-not-allowed opacity-70' : '',
                                ]"
                                @input="autoResizeTitle"
                                @keydown="onDhivehiKeyDown"
                            />
                            <p v-if="form.errors.title" class="text-error text-sm mb-2">{{ form.errors.title }}</p>

                            <!-- Subtitle -->
                            <input
                                v-model="form.subtitle"
                                type="text"
                                :placeholder="placeholders.subtitle"
                                :dir="textDirection"
                                :readonly="isReadOnly"
                                :class="[
                                    'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 mb-4',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-xl leading-relaxed placeholder:font-dhivehi' : 'text-xl',
                                    isReadOnly ? 'cursor-not-allowed opacity-70' : '',
                                ]"
                                @keydown="onDhivehiKeyDown"
                            />

                            <!-- Excerpt -->
                            <textarea
                                v-model="form.excerpt"
                                :placeholder="placeholders.excerpt"
                                rows="2"
                                :dir="textDirection"
                                :readonly="isReadOnly"
                                :class="[
                                    'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 resize-none',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-base leading-relaxed placeholder:font-dhivehi' : 'text-base',
                                    isReadOnly ? 'cursor-not-allowed opacity-70' : '',
                                ]"
                                @keydown="onDhivehiKeyDown"
                            />

                            <!-- Content Editor -->
                            <BlockEditor
                                v-model="form.content"
                                placeholder="Tell your story..."
                                :rtl="isRtl"
                                :dhivehi-enabled="dhivehiEnabled"
                                :dhivehi-layout="dhivehiLayout"
                                :on-select-media="handleEditorSelectMedia"
                                :read-only="isReadOnly"
                            />
                        </div>
                    </div>

                    <!-- Slim Sidebar - Document stats, cover, and actions only -->
                    <div
                        v-show="!isFullscreen"
                        :class="[
                            'w-72 border-l border-default overflow-y-auto',
                            'bg-[var(--ui-bg)] lg:bg-elevated/25',
                            'fixed lg:relative inset-y-0 right-0 z-[60]',
                            'transition-transform duration-200 ease-in-out',
                            sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0',
                        ]"
                    >
                        <!-- Mobile close button -->
                        <div class="lg:hidden flex items-center justify-between p-4 border-b border-default">
                            <span class="text-sm font-medium">Post Info</span>
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-x"
                                size="sm"
                                @click="sidebarOpen = false"
                            />
                        </div>
                        <div class="p-4 space-y-5">

                            <!-- Workflow Quick Actions -->
                            <div v-if="availableTransitions.length > 0 && !isReadOnly">
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon :name="currentWorkflowState.icon" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Workflow</span>
                                    <UBadge :color="workflow.getStateColor(workflowStatus)" variant="subtle" size="xs" class="ml-auto">
                                        {{ currentWorkflowState.label }}
                                    </UBadge>
                                </div>
                                <div class="space-y-2">
                                    <UButton
                                        v-for="transition in availableTransitions"
                                        :key="`${transition.from}-${transition.to}`"
                                        :color="workflow.getStateColor(transition.to)"
                                        variant="soft"
                                        size="sm"
                                        block
                                        :loading="transitionLoading"
                                        @click="performQuickTransition(transition)"
                                    >
                                        <template #leading>
                                            <UIcon name="i-lucide-arrow-right" class="size-4" />
                                        </template>
                                        {{ transition.label }}
                                    </UButton>
                                </div>
                            </div>

                            <!-- Current Status (when no actions available) -->
                            <div v-else-if="isReadOnly" class="p-3 rounded-lg bg-success/10 border border-success/20">
                                <div class="flex items-center gap-2">
                                    <UIcon name="i-lucide-check-circle" class="size-5 text-success" />
                                    <div>
                                        <p class="text-sm font-medium text-success">Published</p>
                                        <p class="text-xs text-muted">This post is live</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="availableTransitions.length > 0 || isReadOnly" class="h-px bg-default" />

                            <!-- Document Stats -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-file-text" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Document</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="text-center p-2 rounded-lg bg-elevated/50">
                                        <p class="text-lg font-semibold text-highlighted">{{ wordCount.toLocaleString() }}</p>
                                        <p class="text-xs text-muted">words</p>
                                    </div>
                                    <div class="text-center p-2 rounded-lg bg-elevated/50">
                                        <p class="text-lg font-semibold text-highlighted">{{ readingTime.replace(' read', '') }}</p>
                                        <p class="text-xs text-muted">read time</p>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-muted space-y-1">
                                    <p><span class="text-highlighted">Last saved:</span> {{ timeAgo }}</p>
                                    <p v-if="post.author"><span class="text-highlighted">Author:</span> {{ post.author.name }}</p>
                                </div>
                            </div>

                            <div class="h-px bg-default" />

                            <!-- Featured Image -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-image" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Cover</span>
                                </div>
                                <div v-if="selectedFeaturedMedia" class="relative mb-2">
                                    <img
                                        :src="selectedFeaturedMedia.thumbnail_url || selectedFeaturedMedia.url || ''"
                                        :alt="selectedFeaturedMedia.title || 'Cover'"
                                        class="w-full h-32 object-cover rounded-lg"
                                    />
                                    <UButton
                                        v-if="!isReadOnly"
                                        color="neutral"
                                        variant="solid"
                                        icon="i-lucide-x"
                                        size="xs"
                                        class="absolute top-1.5 right-1.5"
                                        @click="removeFeaturedMedia"
                                    />
                                </div>
                                <button
                                    v-if="!isReadOnly"
                                    type="button"
                                    class="w-full border border-dashed border-default rounded-lg px-3 py-4 text-center hover:border-primary hover:bg-primary/5 transition-colors"
                                    @click="openCoverPicker"
                                >
                                    <UIcon name="i-lucide-image-plus" class="size-5 text-muted mx-auto mb-1" />
                                    <p class="text-xs text-muted">{{ selectedFeaturedMedia ? 'Change cover' : 'Select cover image' }}</p>
                                </button>
                                <div v-else-if="!selectedFeaturedMedia" class="text-xs text-muted text-center py-4">
                                    No cover image
                                </div>
                            </div>

                            <div class="h-px bg-default" />

                            <!-- Settings -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-settings-2" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Settings</span>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Type</label>
                                        <USelectMenu
                                            v-model="form.post_type"
                                            :items="postTypes"
                                            value-key="value"
                                            size="sm"
                                            class="w-full"
                                            :disabled="isReadOnly"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">URL Slug</label>
                                        <div class="flex gap-1.5">
                                            <UInput v-model="form.slug" placeholder="post-slug" size="sm" class="flex-1 min-w-0" :disabled="isReadOnly" />
                                            <UButton size="sm" color="neutral" variant="ghost" icon="i-lucide-refresh-cw" :disabled="isReadOnly" @click="generateSlug" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Category</label>
                                        <USelectMenu
                                            v-model="form.category_id"
                                            :items="flattenedCategories"
                                            value-key="value"
                                            placeholder="Select..."
                                            size="sm"
                                            class="w-full"
                                            :disabled="isReadOnly"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Tags</label>
                                        <USelectMenu
                                            v-model="form.tags"
                                            :items="tagOptions"
                                            value-key="value"
                                            multiple
                                            placeholder="Select..."
                                            searchable
                                            size="sm"
                                            class="w-full"
                                            :disabled="isReadOnly"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="h-px bg-default" />

                            <!-- Open Editorial Slideover -->
                            <UButton
                                color="neutral"
                                variant="soft"
                                icon="i-lucide-panel-right-open"
                                block
                                @click="editorialSlideoverOpen = true"
                            >
                                Editorial Panel
                            </UButton>

                            <div class="h-px bg-default" />

                            <!-- Actions -->
                            <div class="space-y-1">
                                <UButton
                                    color="neutral"
                                    variant="ghost"
                                    icon="i-lucide-trash"
                                    size="sm"
                                    class="w-full justify-start text-error hover:bg-error/10"
                                    @click="openDeleteModal"
                                >
                                    Move to trash
                                </UButton>
                            </div>

                        </div>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Unified Media Picker Modal -->
        <MediaPickerModal
            v-model:open="mediaPickerOpen"
            :type="mediaPickerType"
            :multiple="mediaPickerMultiple"
            @select="handleMediaSelect"
            @update:open="handleMediaPickerClose"
        />

        <!-- Editorial Slideover -->
        <EditorialSlideover
            v-model:open="editorialSlideoverOpen"
            content-type="posts"
            :content-uuid="post.uuid"
            :current-version-uuid="currentVersionUuid"
            :workflow-status="workflowStatus"
            :workflow-config="workflowConfig"
            :post-status="post.status"
            :versions-list="versionsList"
            :categories="categories"
            :tags="tags"
            :post-types="postTypes"
            :current-post-type="currentPostType"
            :category-id="form.category_id"
            :selected-tags="form.tags"
            :post-type="form.post_type"
            :slug="form.slug"
            :meta-title="form.meta_title"
            :meta-description="form.meta_description"
            :custom-fields="form.custom_fields"
            :is-read-only="isReadOnly"
            :author="post.author"
            :published-at="post.published_at"
            @transition="onWorkflowTransition"
            @refresh="refreshPage"
            @create-draft="refreshPage"
            @version-switch="switchToVersion"
            @update:category-id="form.category_id = $event"
            @update:selected-tags="form.tags = $event"
            @update:post-type="form.post_type = $event"
            @update:slug="form.slug = $event"
            @update:meta-title="form.meta_title = $event"
            @update:meta-description="form.meta_description = $event"
            @update:custom-fields="form.custom_fields = $event"
            @generate-slug="generateSlug"
        />

        <!-- Delete Confirmation Modal -->
        <UModal v-model:open="deleteModalOpen">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <div class="size-10 rounded-full flex items-center justify-center bg-error/10">
                                <UIcon name="i-lucide-trash-2" class="size-5 text-error" />
                            </div>
                            <div>
                                <h3 class="font-semibold">Move to Trash</h3>
                                <p class="text-sm text-muted">This action can be undone</p>
                            </div>
                        </div>
                    </template>

                    <p class="text-sm">
                        Are you sure you want to move <strong>"{{ post.title }}"</strong> to trash?
                        You can restore it later from the trash.
                    </p>

                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <UButton color="neutral" variant="ghost" @click="deleteModalOpen = false">
                                Cancel
                            </UButton>
                            <UButton color="error" :loading="isDeleting" @click="deletePost">
                                <UIcon name="i-lucide-trash-2" class="size-4 mr-1" />
                                Move to Trash
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>

        <!-- Unpublish Confirmation Modal -->
        <UModal v-model:open="unpublishModalOpen">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <div class="size-10 rounded-full flex items-center justify-center bg-error/10">
                                <UIcon name="i-lucide-globe-lock" class="size-5 text-error" />
                            </div>
                            <div>
                                <h3 class="font-semibold">Unpublish Post</h3>
                                <p class="text-sm text-muted">This will remove it from public view</p>
                            </div>
                        </div>
                    </template>

                    <p class="text-sm">
                        Are you sure you want to unpublish <strong>"{{ post.title }}"</strong>?
                        The post will be moved back to draft status and will no longer be visible to the public.
                    </p>

                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <UButton color="neutral" variant="ghost" @click="unpublishModalOpen = false">
                                Cancel
                            </UButton>
                            <UButton color="error" :loading="isUnpublishing" @click="unpublishPost">
                                <UIcon name="i-lucide-globe-lock" class="size-4 mr-1" />
                                Unpublish
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>

<style scoped>
input::placeholder,
textarea::placeholder {
    opacity: 1;
}
</style>
