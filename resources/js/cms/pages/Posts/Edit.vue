<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
    crops?: CropVersion[];
    crop_version?: CropVersion | null;
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

interface EditLockInfo {
    canEdit: boolean;
    lock: {
        id: number;
        user_id: number;
        user_name: string;
        locked_at: string;
        last_heartbeat_at: string;
        is_stale: boolean;
    } | null;
    isMine: boolean;
    heartbeatInterval: number;
}

interface TemplateOption {
    key: string;
    name: string;
    description: string;
    icon: string;
}

// Get page props for CSRF token
const page = usePage();

interface Sponsor {
    id: number;
    name: string;
}

const props = defineProps<{
    post: Post & {
        category_id: number | null;
        featured_tag_id: number | null;
        sponsor_id: number | null;
        tags: number[];
        language_code: string;
        featured_media?: MediaItem | null;
        featured_media_id?: number | null;
        workflow_status?: string;
        current_version_uuid?: string | null;
        template?: string;
        show_author?: boolean;
    };
    categories: Category[];
    tags: Tag[];
    sponsors: Sponsor[];
    postTypes: PostTypeOption[];
    currentPostType?: PostTypeConfig | null;
    language: LanguageInfo | null;
    workflowConfig: WorkflowConfig;
    versionsList?: VersionListItem[];
    editLock: EditLockInfo;
    templates: TemplateOption[];
}>();

// Edit lock state
const lockStatus = ref(props.editLock);
const heartbeatTimer = ref<ReturnType<typeof setInterval> | null>(null);
const isTakingOver = ref(false);

// Check if locked by someone else
const isLockedByOther = computed(() => {
    return lockStatus.value.lock !== null && !lockStatus.value.isMine;
});

// Can we take over the lock?
const canTakeOver = computed(() => {
    return isLockedByOther.value && lockStatus.value.lock?.is_stale;
});

// Start heartbeat to keep lock alive
function startHeartbeat() {
    if (heartbeatTimer.value) return;
    if (!lockStatus.value.isMine) return;

    heartbeatTimer.value = setInterval(async () => {
        try {
            await axios.post(`/cms/posts/${props.post.uuid}/lock/heartbeat`);
        } catch (error) {
            console.error('Failed to send heartbeat:', error);
            // If heartbeat fails, we may have lost the lock
            stopHeartbeat();
        }
    }, lockStatus.value.heartbeatInterval);
}

// Stop heartbeat
function stopHeartbeat() {
    if (heartbeatTimer.value) {
        clearInterval(heartbeatTimer.value);
        heartbeatTimer.value = null;
    }
}

// Release lock when leaving page
async function releaseLock() {
    if (!lockStatus.value.isMine) return;
    try {
        await axios.post(`/cms/posts/${props.post.uuid}/lock/release`);
    } catch (error) {
        console.error('Failed to release lock:', error);
    }
}

// Take over editing from someone else (only if lock is stale)
async function takeOverEditing() {
    isTakingOver.value = true;
    try {
        const response = await axios.post(`/cms/posts/${props.post.uuid}/lock/force`);
        if (response.data.success) {
            lockStatus.value = {
                canEdit: true,
                lock: response.data.lock,
                isMine: true,
                heartbeatInterval: lockStatus.value.heartbeatInterval,
            };
            startHeartbeat();
            toast.add({ title: 'Success', description: 'You are now editing this post', color: 'success' });
            // Reload page to get fresh content
            router.reload();
        }
    } catch (error: any) {
        toast.add({
            title: 'Error',
            description: error.response?.data?.message || 'Failed to take over editing',
            color: 'error',
        });
    }
    isTakingOver.value = false;
}

// Workflow state
const workflowStatus = ref(props.post.workflow_status || 'draft');
const currentVersionUuid = ref(props.post.current_version_uuid || null);

// Watch for props changes after Inertia partial reload to sync refs
watch(
    () => props.versionsList,
    (newVersionsList) => {
        // After reload, sync the workflow status from the current version
        const currentVersion = newVersionsList?.find(v => v.uuid === currentVersionUuid.value);
        if (currentVersion) {
            workflowStatus.value = currentVersion.workflow_status;
        }
    },
    { deep: true }
);

watch(
    () => props.post.workflow_status,
    (newStatus) => {
        if (newStatus) {
            workflowStatus.value = newStatus;
        }
    }
);

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

// Read-only if viewing the published/active version OR if the version is not in draft status OR if locked by someone else
const isReadOnly = computed(() => {
    // If locked by someone else, it's read-only
    if (isLockedByOther.value && !canTakeOver.value) return true;
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
    kicker: isRtl.value ? 'ކިކާ (މިސާލު: ފީޗާ)' : 'KICKER (e.g., TASTY FEATURE)',
    title: isRtl.value ? 'ސުރުޚީ' : 'Title',
    subtitle: isRtl.value ? 'ސަބް ސުރުޚީ...' : 'Add a subtitle...',
    excerpt: isRtl.value ? 'ޚުލާސާ / ޑެކް ލިޔޭ...' : 'Write a deck or summary...',
}));

// Handle keydown for Dhivehi input fields
function onDhivehiKeyDown(e: KeyboardEvent) {
    if (isRtl.value) {
        dhivehiKeyDown(e, e.target as HTMLInputElement | HTMLTextAreaElement);
    }
}

const form = useForm({
    title: props.post.title,
    kicker: props.post.kicker || '',
    subtitle: props.post.subtitle || '',
    slug: props.post.slug,
    excerpt: props.post.excerpt || '',
    content: props.post.content,
    post_type: props.post.post_type,
    template: props.post.template || 'default',
    scheduled_at: props.post.scheduled_at || '',
    category_id: props.post.category_id ?? null,
    featured_tag_id: props.post.featured_tag_id ?? null,
    sponsor_id: props.post.sponsor_id ?? null,
    tags: props.post.tags || [],
    featured_media_id: props.post.featured_media_id ?? null,
    custom_fields: (props.post.custom_fields || {}) as Record<string, unknown>,
    meta_title: props.post.meta_title || '',
    meta_description: props.post.meta_description || '',
    show_author: props.post.show_author ?? true,
});

// Get current post type config - either from props or find it from postTypes
const currentPostType = computed<PostTypeConfig | null>(() => {
    // First check if passed from backend AND matches current selection
    if (props.currentPostType && props.currentPostType.key === form.post_type) {
        return props.currentPostType;
    }
    // Otherwise find from postTypes list based on form's current selection
    const found = props.postTypes.find(pt => pt.value === form.post_type);
    if (found && 'fields' in found) {
        return {
            key: found.value,
            label: found.label,
            icon: (found as any).icon || null,
            fields: (found as any).fields || [],
        };
    }
    return null;
});

// Toast for post type changes
const previousPostType = ref(form.post_type);

watch(() => form.post_type, (newType) => {
    const type = props.postTypes.find(t => t.value === newType);
    if (type && 'fields' in type && (type as any).fields?.length > 0 && newType !== previousPostType.value) {
        toast.add({
            title: `${type.label} Fields Available`,
            description: 'Additional fields are now available below the content editor.',
            icon: (type as any).icon || 'i-lucide-info',
            color: 'info',
        });
    }
    previousPostType.value = newType;
});

// Textarea refs for auto-resize
const titleTextarea = ref<HTMLTextAreaElement | null>(null);
const subtitleTextarea = ref<HTMLTextAreaElement | null>(null);
const excerptTextarea = ref<HTMLTextAreaElement | null>(null);

// Auto-resize textarea to fit content
function autoResizeTitle() {
    const textarea = titleTextarea.value;
    if (textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
}

function autoResizeSubtitle() {
    const textarea = subtitleTextarea.value;
    if (textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
}

function autoResizeExcerpt() {
    const textarea = excerptTextarea.value;
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
            original_url: item.url || '', // Store original URL for crop switching
            thumbnail_url: item.thumbnail_url,
            title: item.title,
            alt_text: item.alt_text,
            caption: item.caption || item.title || null,
            credit_display: item.credit_display || null,
            is_image: item.is_image === true,
            is_video: item.is_video === true,
            // Include crop versions for in-editor crop selection
            crops: item.crops || [],
            crop_version: item.crop_version || null,
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

// Preview mode
const isPreviewMode = ref(false);
const previewIframe = ref<HTMLIFrameElement | null>(null);
const previewFormRef = ref<HTMLFormElement | null>(null);
const previewLoading = ref(false);

function togglePreviewMode() {
    isPreviewMode.value = !isPreviewMode.value;
}

// Get current template config
const currentTemplateConfig = computed(() => {
    return props.templates.find(t => t.key === form.template) || props.templates[0];
});

// Update preview when entering preview mode or when relevant data changes
function refreshPreview() {
    if (!isPreviewMode.value || !previewFormRef.value) return;
    previewLoading.value = true;
    previewFormRef.value.submit();
}

// Watch for preview mode changes
watch(isPreviewMode, (newVal) => {
    if (newVal) {
        nextTick(() => refreshPreview());
    }
});

// Watch for content changes in preview mode (debounced)
const previewDebounceTimer = ref<ReturnType<typeof setTimeout> | null>(null);
watch(
    () => [form.title, form.subtitle, form.content, form.template, form.show_author, form.category_id, form.tags, form.post_type, form.custom_fields, selectedFeaturedMedia.value?.url],
    () => {
        if (!isPreviewMode.value) return;
        if (previewDebounceTimer.value) {
            clearTimeout(previewDebounceTimer.value);
        }
        previewDebounceTimer.value = setTimeout(() => {
            refreshPreview();
        }, 500);
    },
    { deep: true }
);

function onPreviewLoad() {
    previewLoading.value = false;
}

// Auto-save functionality
const autoSaveTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const lastSaved = ref<Date | null>(null);
const isSaving = ref(false);

function autoSave() {
    // Don't auto-save if we don't have the lock
    if (!lockStatus.value.isMine) return;

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

// Track unsaved changes
const hasUnsavedChanges = ref(false);

// Watch for changes to mark as dirty
watch(
    () => [form.title, form.subtitle, form.excerpt, form.content, form.custom_fields],
    () => {
        hasUnsavedChanges.value = true;
    },
    { deep: true }
);

// Manual save function
function manualSave() {
    // Don't save if we don't have the lock
    if (!lockStatus.value.isMine) return;

    // Don't save published posts (read-only)
    if (isReadOnly.value) return;

    // Only save if there's a title
    if (!form.title.trim()) {
        toast.add({ title: 'Cannot save', description: 'Please add a title first', color: 'warning' });
        return;
    }

    // Don't save while already saving
    if (form.processing || isSaving.value) return;

    isSaving.value = true;

    const langCode = props.language?.code || props.post.language_code || 'en';
    form.post(`/cms/posts/${langCode}/${props.post.uuid}`, {
        forceFormData: true,
        headers: { 'X-HTTP-Method-Override': 'PUT' },
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            lastSaved.value = new Date();
            hasUnsavedChanges.value = false;
            isSaving.value = false;
            toast.add({ title: 'Saved', description: 'Your changes have been saved', color: 'success', duration: 2000 });
        },
        onError: () => {
            isSaving.value = false;
            toast.add({ title: 'Error', description: 'Failed to save changes', color: 'error' });
        },
    });
}

// Keyboard shortcuts (Escape for fullscreen, Cmd/Ctrl+S for save)
function handleKeydown(e: KeyboardEvent) {
    // Escape to exit fullscreen
    if (e.key === 'Escape' && isFullscreen.value) {
        isFullscreen.value = false;
        showSidebar();
    }

    // Cmd+S or Ctrl+S to save
    if ((e.metaKey || e.ctrlKey) && e.key === 's') {
        e.preventDefault();
        manualSave();
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    // Auto-resize textareas on mount if there's existing content
    nextTick(() => {
        autoResizeTitle();
        autoResizeSubtitle();
        autoResizeExcerpt();
    });
    // Start heartbeat if we have the lock
    if (lockStatus.value.isMine) {
        startHeartbeat();
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
    if (autoSaveTimer.value) {
        clearTimeout(autoSaveTimer.value);
    }
    // Stop heartbeat and release lock
    stopHeartbeat();
    releaseLock();
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

const sponsorOptions = computed(() =>
    props.sponsors.map((sponsor) => ({ label: sponsor.name, value: sponsor.id }))
);

// Get selected category name for preview
const selectedCategoryName = computed(() => {
    if (!form.category_id) return '';
    const findCategory = (cats: Category[]): string => {
        for (const cat of cats) {
            if (cat.id === form.category_id) return cat.name;
            if (cat.children?.length) {
                const found = findCategory(cat.children);
                if (found) return found;
            }
        }
        return '';
    };
    return findCategory(props.categories);
});

// Get selected tag names for preview
const selectedTagNames = computed(() => {
    if (!form.tags?.length) return [];
    return props.tags
        .filter(tag => form.tags.includes(tag.id))
        .map(tag => tag.name);
});

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
    // Open preview in new tab using CMS preview route
    const langCode = props.language?.code || props.post.language_code || 'en';
    const versionParam = activeVersionUuid.value ? `?version=${activeVersionUuid.value}` : '';
    window.open(`/cms/preview/${langCode}/${props.post.uuid}${versionParam}`, '_blank');
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
                        <div class="flex items-center gap-1.5 sm:gap-3 min-w-0 overflow-x-auto scrollbar-hide">
                            <UDashboardSidebarCollapse class="shrink-0" />
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-arrow-left"
                                size="sm"
                                class="shrink-0"
                                @click="goBack"
                            />
                            <div class="h-4 w-px bg-default shrink-0 hidden sm:block" />
                            <span class="text-sm text-muted hidden sm:inline shrink-0">Editing</span>
                            <UBadge
                                :color="currentWorkflowState.color as any"
                                variant="subtle"
                                size="sm"
                                class="shrink-0"
                            >
                                <UIcon :name="currentWorkflowState.icon" class="size-3 mr-1" />
                                <span class="hidden xs:inline">{{ currentWorkflowState.label }}</span>
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
                                    class="shrink-0"
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
                                class="shrink-0 hidden md:flex"
                                @click="openUnpublishModal"
                            >
                                <UIcon name="i-lucide-globe-lock" class="size-3 mr-1" />
                                <span class="hidden lg:inline">Unpublish</span>
                            </UButton>
                            <UBadge
                                v-if="language"
                                :color="isRtl ? 'warning' : 'primary'"
                                variant="subtle"
                                size="sm"
                                class="shrink-0 hidden sm:flex"
                            >
                                <span :class="isRtl ? 'font-dhivehi' : ''">{{ language.native_name }}</span>
                            </UBadge>
                        </div>
                    </template>

                    <template #right>
                        <div class="flex items-center gap-2">
                            <!-- Save status -->
                            <span v-if="isSaving" class="text-xs text-muted flex items-center gap-1">
                                <UIcon name="i-lucide-loader-2" class="size-3 animate-spin" />
                                Saving...
                            </span>
                            <span v-else-if="hasUnsavedChanges" class="text-xs text-warning flex items-center gap-1.5 hidden sm:flex">
                                <span class="size-1.5 rounded-full bg-warning"></span>
                                Unsaved changes
                                <kbd class="text-[10px] px-1 py-0.5 rounded bg-elevated border border-default text-muted">⌘S</kbd>
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

                            <!-- Edit/Preview Toggle -->
                            <div class="flex items-center rounded-lg bg-elevated p-0.5">
                                <UButton
                                    :color="!isPreviewMode ? 'primary' : 'neutral'"
                                    :variant="!isPreviewMode ? 'soft' : 'ghost'"
                                    size="xs"
                                    @click="isPreviewMode = false"
                                >
                                    <UIcon name="i-lucide-edit-3" class="size-3.5 mr-1" />
                                    Edit
                                </UButton>
                                <UButton
                                    :color="isPreviewMode ? 'primary' : 'neutral'"
                                    :variant="isPreviewMode ? 'soft' : 'ghost'"
                                    size="xs"
                                    @click="isPreviewMode = true"
                                >
                                    <UIcon name="i-lucide-eye" class="size-3.5 mr-1" />
                                    Preview
                                </UButton>
                            </div>

                            <!-- Open in new tab preview -->
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-external-link"
                                size="sm"
                                @click="openPreview"
                                title="Open preview in new tab"
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
                <!-- Lock Warning Banner -->
                <div
                    v-if="isLockedByOther"
                    class="bg-warning-50 dark:bg-warning-900/20 border-b border-warning-200 dark:border-warning-800 px-4 py-3"
                >
                    <div class="flex items-center justify-between gap-4 max-w-5xl mx-auto">
                        <div class="flex items-center gap-3">
                            <UIcon name="i-lucide-lock" class="size-5 text-warning-600 dark:text-warning-400" />
                            <div>
                                <p class="text-sm font-medium text-warning-800 dark:text-warning-200">
                                    This post is being edited by {{ lockStatus.lock?.user_name }}
                                </p>
                                <p class="text-xs text-warning-600 dark:text-warning-400">
                                    <template v-if="canTakeOver">
                                        Their session appears to be inactive. You can take over editing.
                                    </template>
                                    <template v-else>
                                        You can view this post but cannot make changes until they're done.
                                    </template>
                                </p>
                            </div>
                        </div>
                        <UButton
                            v-if="canTakeOver"
                            color="warning"
                            variant="solid"
                            size="sm"
                            :loading="isTakingOver"
                            @click="takeOverEditing"
                        >
                            <UIcon name="i-lucide-user-check" class="size-4 mr-1" />
                            Take Over
                        </UButton>
                    </div>
                </div>

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
                        <!-- Preview Mode (Blade rendered via iframe) -->
                        <div v-if="isPreviewMode" class="h-full relative">
                            <!-- Loading overlay -->
                            <div
                                v-if="previewLoading"
                                class="absolute inset-0 bg-[var(--ui-bg)]/80 flex items-center justify-center z-10"
                            >
                                <div class="flex items-center gap-2 text-muted">
                                    <UIcon name="i-lucide-loader-2" class="size-5 animate-spin" />
                                    <span class="text-sm">Rendering preview...</span>
                                </div>
                            </div>

                            <!-- Hidden form for submitting preview data -->
                            <form
                                ref="previewFormRef"
                                action="/cms/api/preview/post"
                                method="POST"
                                target="preview-iframe"
                                class="hidden"
                            >
                                <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                <input type="hidden" name="title" :value="form.title || 'Untitled'" />
                                <input type="hidden" name="subtitle" :value="form.subtitle || ''" />
                                <input type="hidden" name="excerpt" :value="form.excerpt || ''" />
                                <input type="hidden" name="content" :value="JSON.stringify(form.content || {})" />
                                <input type="hidden" name="template" :value="form.template || 'default'" />
                                <input type="hidden" name="language_code" :value="language?.code || 'en'" />
                                <input type="hidden" name="featured_image_url" :value="selectedFeaturedMedia?.url || selectedFeaturedMedia?.thumbnail_url || ''" />
                                <input type="hidden" name="author[name]" :value="post.author?.name || ''" />
                                <input type="hidden" name="show_author" :value="form.show_author ? '1' : '0'" />
                                <input type="hidden" name="category" :value="selectedCategoryName || ''" />
                                <input type="hidden" name="tags" :value="(selectedTagNames || []).join(',')" />
                                <input type="hidden" name="post_type" :value="form.post_type || 'article'" />
                                <input type="hidden" name="custom_fields" :value="JSON.stringify(form.custom_fields || {})" />
                            </form>

                            <!-- Preview iframe -->
                            <iframe
                                ref="previewIframe"
                                name="preview-iframe"
                                class="w-full h-full border-0"
                                @load="onPreviewLoad"
                            ></iframe>
                        </div>

                        <!-- Edit Mode -->
                        <div v-else :class="['mx-auto px-6 py-12', isFullscreen ? 'max-w-screen-2xl' : 'max-w-2xl']">
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
                                        <!-- Actions for published version -->
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
                                        <!-- Actions for version in workflow (review, copydesk, approved, etc.) -->
                                        <div v-else class="flex flex-wrap gap-2 mt-3">
                                            <!-- Show available workflow transitions -->
                                            <UButton
                                                v-for="transition in availableTransitions"
                                                :key="`${transition.from}-${transition.to}`"
                                                size="sm"
                                                :color="workflow.getStateColor(transition.to)"
                                                variant="soft"
                                                :loading="transitionLoading"
                                                @click="performQuickTransition(transition)"
                                            >
                                                <template #leading>
                                                    <UIcon name="i-lucide-arrow-right" class="size-4" />
                                                </template>
                                                {{ transition.label }}
                                            </UButton>
                                            <!-- If there's a draft version, offer to switch to it -->
                                            <UButton
                                                v-if="existingDraftVersion"
                                                size="sm"
                                                color="primary"
                                                icon="i-lucide-edit"
                                                @click="switchToVersion(existingDraftVersion.uuid)"
                                            >
                                                Edit Draft (v{{ existingDraftVersion.version_number }})
                                            </UButton>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Kicker -->
                            <input
                                v-model="form.kicker"
                                type="text"
                                :placeholder="placeholders.kicker"
                                :dir="textDirection"
                                :readonly="isReadOnly"
                                :class="[
                                    'w-full bg-transparent border-0 outline-none placeholder:text-muted/30 mb-3 uppercase tracking-wider',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-sm placeholder:font-dhivehi' : 'text-sm font-medium',
                                    isReadOnly ? 'cursor-not-allowed opacity-70' : '',
                                ]"
                                @keydown="onDhivehiKeyDown"
                            />

                            <!-- Title -->
                            <textarea
                                ref="titleTextarea"
                                v-model="form.title"
                                :placeholder="placeholders.title"
                                :dir="textDirection"
                                :readonly="isReadOnly"
                                rows="1"
                                maxlength="70"
                                :class="[
                                    'w-full font-bold bg-transparent border-0 outline-none placeholder:text-muted/40 mb-1 resize-none overflow-hidden',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-4xl leading-relaxed placeholder:font-dhivehi' : 'text-4xl leading-tight',
                                    isReadOnly ? 'cursor-not-allowed opacity-70' : '',
                                ]"
                                @input="autoResizeTitle"
                                @keydown="onDhivehiKeyDown"
                            />
                            <div class="flex items-center justify-between mb-3">
                                <p v-if="form.errors.title" class="text-error text-xs">{{ form.errors.title }}</p>
                                <span v-else />
                                <span :class="['text-xs', form.title.length > 70 ? 'text-error' : 'text-muted']">
                                    {{ 70 - form.title.length }} left
                                </span>
                            </div>

                            <!-- Separator between headline and description -->
                            <div class="h-px bg-gray-200 dark:bg-gray-700 my-6" />

                            <!-- Subtitle -->
                            <textarea
                                ref="subtitleTextarea"
                                v-model="form.subtitle"
                                :placeholder="placeholders.subtitle"
                                :dir="textDirection"
                                :readonly="isReadOnly"
                                rows="1"
                                maxlength="120"
                                :class="[
                                    'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 mb-1 resize-none overflow-hidden',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-xl leading-relaxed placeholder:font-dhivehi' : 'text-xl',
                                    isReadOnly ? 'cursor-not-allowed opacity-70' : '',
                                ]"
                                @input="autoResizeSubtitle"
                                @keydown="onDhivehiKeyDown"
                            />
                            <div class="flex items-center justify-end mb-4">
                                <span :class="['text-xs', (form.subtitle?.length || 0) > 120 ? 'text-error' : 'text-muted']">
                                    {{ 120 - (form.subtitle?.length || 0) }} left
                                </span>
                            </div>

                            <!-- Excerpt / Deck -->
                            <textarea
                                ref="excerptTextarea"
                                v-model="form.excerpt"
                                :placeholder="placeholders.excerpt"
                                rows="1"
                                maxlength="160"
                                :dir="textDirection"
                                :readonly="isReadOnly"
                                :class="[
                                    'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 mb-1 resize-none overflow-hidden',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-base leading-relaxed placeholder:font-dhivehi' : 'text-base',
                                    isReadOnly ? 'cursor-not-allowed opacity-70' : '',
                                ]"
                                @input="autoResizeExcerpt"
                                @keydown="onDhivehiKeyDown"
                            />
                            <div class="flex items-center justify-end">
                                <span :class="['text-xs', (form.excerpt?.length || 0) > 160 ? 'text-error' : 'text-muted']">
                                    {{ 160 - (form.excerpt?.length || 0) }} left
                                </span>
                            </div>

                            <!-- Separator between header and content -->
                            <div class="h-px bg-gray-200 dark:bg-gray-700 my-8" />

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

                            <!-- Custom Fields Section (for post types with additional fields) -->
                            <div v-if="currentPostType?.fields?.length" class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="flex items-center justify-center size-9 rounded-full bg-primary/10">
                                        <UIcon :name="currentPostType.icon || 'i-lucide-file-text'" class="size-4 text-primary" />
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-highlighted">{{ currentPostType.label }} Details</h3>
                                        <p class="text-xs text-muted">Additional information specific to this content type</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <template v-for="field in currentPostType.fields" :key="field.name">
                                        <!-- Text Field -->
                                        <div v-if="field.type === 'text'">
                                            <label class="text-sm font-medium mb-1.5 block">{{ field.label }}</label>
                                            <UInput
                                                :model-value="(form.custom_fields?.[field.name] as string) ?? ''"
                                                class="w-full"
                                                :disabled="isReadOnly"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Number Field -->
                                        <div v-else-if="field.type === 'number'">
                                            <label class="text-sm font-medium mb-1.5 block">
                                                {{ field.label }}
                                                <span v-if="field.suffix" class="text-muted font-normal">({{ field.suffix }})</span>
                                            </label>
                                            <UInput
                                                :model-value="(form.custom_fields?.[field.name] as number | null) ?? null"
                                                type="number"
                                                min="0"
                                                class="w-full"
                                                :disabled="isReadOnly"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Textarea Field -->
                                        <div v-else-if="field.type === 'textarea'">
                                            <label class="text-sm font-medium mb-1.5 block">{{ field.label }}</label>
                                            <UTextarea
                                                :model-value="(form.custom_fields?.[field.name] as string) ?? ''"
                                                :rows="4"
                                                class="w-full"
                                                :disabled="isReadOnly"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Select Field -->
                                        <div v-else-if="field.type === 'select'">
                                            <label class="text-sm font-medium mb-1.5 block">{{ field.label }}</label>
                                            <USelectMenu
                                                :model-value="(form.custom_fields?.[field.name] as string) ?? ''"
                                                :items="(field.options ?? []).map(opt => ({ label: opt, value: opt }))"
                                                value-key="value"
                                                class="w-full"
                                                :disabled="isReadOnly"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Toggle Field -->
                                        <div v-else-if="field.type === 'toggle'" class="flex items-center justify-between p-3 rounded-lg bg-elevated/50 border border-default">
                                            <label class="text-sm font-medium">{{ field.label }}</label>
                                            <USwitch
                                                :model-value="(form.custom_fields?.[field.name] as boolean) ?? false"
                                                :disabled="isReadOnly"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Repeater Field -->
                                        <div v-else-if="field.type === 'repeater'">
                                            <label class="text-sm font-medium mb-1.5 block">{{ field.label }}</label>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="(item, index) in ((form.custom_fields?.[field.name] as string[]) ?? [])"
                                                    :key="index"
                                                    class="flex gap-2"
                                                >
                                                    <UInput
                                                        :model-value="item"
                                                        class="flex-1"
                                                        :disabled="isReadOnly"
                                                        @update:model-value="form.custom_fields = {
                                                            ...form.custom_fields,
                                                            [field.name]: ((form.custom_fields?.[field.name] as string[]) ?? []).map((v, i) => i === index ? $event : v)
                                                        }"
                                                    />
                                                    <UButton
                                                        color="neutral"
                                                        variant="ghost"
                                                        icon="i-lucide-x"
                                                        :disabled="isReadOnly"
                                                        @click="form.custom_fields = {
                                                            ...form.custom_fields,
                                                            [field.name]: ((form.custom_fields?.[field.name] as string[]) ?? []).filter((_, i) => i !== index)
                                                        }"
                                                    />
                                                </div>
                                                <div class="flex gap-2">
                                                    <UInput
                                                        :id="`custom-repeater-${field.name}`"
                                                        placeholder="Add new item..."
                                                        class="flex-1"
                                                        :disabled="isReadOnly"
                                                        @keyup.enter.prevent="(e: KeyboardEvent) => {
                                                            const input = e.target as HTMLInputElement;
                                                            if (input.value.trim()) {
                                                                form.custom_fields = {
                                                                    ...form.custom_fields,
                                                                    [field.name]: [...((form.custom_fields?.[field.name] as string[]) ?? []), input.value.trim()]
                                                                };
                                                                input.value = '';
                                                            }
                                                        }"
                                                    />
                                                    <UButton
                                                        color="neutral"
                                                        variant="soft"
                                                        icon="i-lucide-plus"
                                                        :disabled="isReadOnly"
                                                        @click="() => {
                                                            const input = document.getElementById(`custom-repeater-${field.name}`) as HTMLInputElement;
                                                            if (input?.value.trim()) {
                                                                form.custom_fields = {
                                                                    ...form.custom_fields,
                                                                    [field.name]: [...((form.custom_fields?.[field.name] as string[]) ?? []), input.value.trim()]
                                                                };
                                                                input.value = '';
                                                            }
                                                        }"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Grouped Repeater Field (for sectioned ingredients) -->
                                        <div v-else-if="field.type === 'grouped-repeater'">
                                            <label class="text-sm font-medium mb-2 block">{{ field.label }}</label>
                                            <div class="space-y-3">
                                                <!-- Sections -->
                                                <div
                                                    v-for="(section, sectionIndex) in ((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])"
                                                    :key="sectionIndex"
                                                    class="border border-default rounded-lg overflow-hidden"
                                                >
                                                    <!-- Section Header -->
                                                    <div class="flex items-center gap-2 bg-elevated px-3 py-2">
                                                        <UIcon name="i-lucide-grip-vertical" class="size-4 text-muted cursor-move" />
                                                        <UInput
                                                            :model-value="section.section"
                                                            size="sm"
                                                            class="flex-1 min-w-0 font-medium"
                                                            placeholder="Section name..."
                                                            :disabled="isReadOnly"
                                                            @update:model-value="(val: string) => {
                                                                const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                sections[sectionIndex] = { ...sections[sectionIndex], section: val };
                                                                form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                            }"
                                                        />
                                                        <UButton
                                                            size="sm"
                                                            color="error"
                                                            variant="ghost"
                                                            icon="i-lucide-trash-2"
                                                            :disabled="isReadOnly"
                                                            @click="() => {
                                                                const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                sections.splice(sectionIndex, 1);
                                                                form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                            }"
                                                        />
                                                    </div>
                                                    <!-- Section Items -->
                                                    <div class="p-2 space-y-1.5">
                                                        <div
                                                            v-for="(item, itemIndex) in section.items"
                                                            :key="itemIndex"
                                                            class="flex gap-1.5"
                                                        >
                                                            <UInput
                                                                :model-value="item"
                                                                size="sm"
                                                                class="flex-1 min-w-0"
                                                                :disabled="isReadOnly"
                                                                @update:model-value="(val: string) => {
                                                                    const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                    const newItems = [...sections[sectionIndex].items];
                                                                    newItems[itemIndex] = val;
                                                                    sections[sectionIndex] = { ...sections[sectionIndex], items: newItems };
                                                                    form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                }"
                                                            />
                                                            <UButton
                                                                size="sm"
                                                                color="neutral"
                                                                variant="ghost"
                                                                icon="i-lucide-x"
                                                                :disabled="isReadOnly"
                                                                @click="() => {
                                                                    const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                    const newItems = [...sections[sectionIndex].items];
                                                                    newItems.splice(itemIndex, 1);
                                                                    sections[sectionIndex] = { ...sections[sectionIndex], items: newItems };
                                                                    form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                }"
                                                            />
                                                        </div>
                                                        <!-- Add item to section -->
                                                        <div class="flex gap-1.5">
                                                            <UInput
                                                                :id="`grouped-item-new-${field.name}-${sectionIndex}`"
                                                                placeholder="Add item..."
                                                                size="sm"
                                                                class="flex-1 min-w-0"
                                                                :disabled="isReadOnly"
                                                                @keyup.enter.prevent="(e: KeyboardEvent) => {
                                                                    const input = e.target as HTMLInputElement;
                                                                    if (input.value.trim()) {
                                                                        const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                        sections[sectionIndex] = {
                                                                            ...sections[sectionIndex],
                                                                            items: [...sections[sectionIndex].items, input.value.trim()]
                                                                        };
                                                                        form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                        input.value = '';
                                                                    }
                                                                }"
                                                            />
                                                            <UButton
                                                                size="sm"
                                                                color="neutral"
                                                                variant="soft"
                                                                icon="i-lucide-plus"
                                                                :disabled="isReadOnly"
                                                                @click="() => {
                                                                    const input = document.getElementById(`grouped-item-new-${field.name}-${sectionIndex}`) as HTMLInputElement;
                                                                    if (input?.value.trim()) {
                                                                        const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                        sections[sectionIndex] = {
                                                                            ...sections[sectionIndex],
                                                                            items: [...sections[sectionIndex].items, input.value.trim()]
                                                                        };
                                                                        form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                        input.value = '';
                                                                    }
                                                                }"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Add new section -->
                                                <div class="flex gap-1.5">
                                                    <UInput
                                                        :id="`grouped-section-new-${field.name}`"
                                                        placeholder="Add section (e.g., Pasta, Sauce)..."
                                                        size="sm"
                                                        class="flex-1 min-w-0"
                                                        :disabled="isReadOnly"
                                                        @keyup.enter.prevent="(e: KeyboardEvent) => {
                                                            const input = e.target as HTMLInputElement;
                                                            if (input.value.trim()) {
                                                                const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                sections.push({ section: input.value.trim(), items: [] });
                                                                form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                input.value = '';
                                                            }
                                                        }"
                                                    />
                                                    <UButton
                                                        size="sm"
                                                        color="primary"
                                                        variant="soft"
                                                        icon="i-lucide-folder-plus"
                                                        :disabled="isReadOnly"
                                                        @click="() => {
                                                            const input = document.getElementById(`grouped-section-new-${field.name}`) as HTMLInputElement;
                                                            if (input?.value.trim()) {
                                                                const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                sections.push({ section: input.value.trim(), items: [] });
                                                                form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                input.value = '';
                                                            }
                                                        }"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
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
                            <div v-if="availableTransitions.length > 0">
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

                            <!-- Current Status (when in read-only mode) -->
                            <div v-else-if="isReadOnly">
                                <!-- Published/Active version -->
                                <div v-if="isCurrentVersionActive" class="p-3 rounded-lg bg-success/10 border border-success/20">
                                    <div class="flex items-center gap-2">
                                        <UIcon name="i-lucide-check-circle" class="size-5 text-success" />
                                        <div>
                                            <p class="text-sm font-medium text-success">Published</p>
                                            <p class="text-xs text-muted">This post is live</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Version in workflow (review, approved, etc.) -->
                                <div v-else class="p-3 rounded-lg border" :class="`bg-${workflow.getStateColor(workflowStatus)}/10 border-${workflow.getStateColor(workflowStatus)}/20`">
                                    <div class="flex items-center gap-2">
                                        <UIcon :name="currentWorkflowState.icon" class="size-5" :class="`text-${workflow.getStateColor(workflowStatus)}`" />
                                        <div>
                                            <p class="text-sm font-medium" :class="`text-${workflow.getStateColor(workflowStatus)}`">{{ currentWorkflowState.label }}</p>
                                            <p class="text-xs text-muted">This version is in the workflow</p>
                                        </div>
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

                            <!-- Template Selection -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-layout-template" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Template</span>
                                </div>
                                <div class="space-y-2">
                                    <button
                                        v-for="template in templates"
                                        :key="template.key"
                                        type="button"
                                        :class="[
                                            'w-full text-left p-3 rounded-lg border transition-all',
                                            form.template === template.key
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                                : 'border-default hover:border-muted hover:bg-elevated/50',
                                            isReadOnly ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
                                        ]"
                                        :disabled="isReadOnly"
                                        @click="form.template = template.key"
                                    >
                                        <div class="flex items-start gap-2.5">
                                            <UIcon :name="template.icon" :class="[
                                                'size-4 mt-0.5 shrink-0',
                                                form.template === template.key ? 'text-primary' : 'text-muted'
                                            ]" />
                                            <div class="min-w-0">
                                                <p :class="[
                                                    'text-sm font-medium',
                                                    form.template === template.key ? 'text-primary' : ''
                                                ]">{{ template.name }}</p>
                                                <p class="text-xs text-muted mt-0.5 line-clamp-2">{{ template.description }}</p>
                                            </div>
                                        </div>
                                    </button>
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
                                        <label class="text-xs text-muted mb-1 block">Category <span class="text-error">*</span></label>
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
                                        <label class="text-xs text-muted mb-1 block">Featured Tag <span class="text-error">*</span></label>
                                        <USelectMenu
                                            v-model="form.featured_tag_id"
                                            :items="tagOptions"
                                            value-key="value"
                                            placeholder="Select..."
                                            searchable
                                            size="sm"
                                            class="w-full"
                                            :disabled="isReadOnly"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Sponsor</label>
                                        <USelectMenu
                                            v-model="form.sponsor_id"
                                            :items="sponsorOptions"
                                            value-key="value"
                                            placeholder="None"
                                            searchable
                                            size="sm"
                                            class="w-full"
                                            :disabled="isReadOnly"
                                            :ui="{ clear: 'flex' }"
                                            clearable
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
                                    <!-- Display Options -->
                                    <div class="pt-3 border-t border-default mt-3">
                                        <label class="text-xs text-muted mb-2 block">Display Options</label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                v-model="form.show_author"
                                                class="rounded border-default text-primary focus:ring-primary"
                                                :disabled="isReadOnly"
                                            />
                                            <span class="text-sm">Show author name</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

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
            :sponsors="sponsors"
            :post-types="postTypes"
            :current-post-type="currentPostType"
            :category-id="form.category_id"
            :featured-tag-id="form.featured_tag_id"
            :sponsor-id="form.sponsor_id"
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
            @update:featured-tag-id="form.featured_tag_id = $event"
            @update:sponsor-id="form.sponsor_id = $event"
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

/* Hide scrollbar for nav bar horizontal scroll on mobile */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
