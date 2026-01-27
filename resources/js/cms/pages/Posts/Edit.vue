<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BlockEditor, { type MediaSelectCallback, type PostSelectCallback } from '../../components/BlockEditor.vue';
import MediaPickerModal from '../../components/MediaPickerModal.vue';
import EditorPostPickerModal from '../../components/EditorPostPickerModal.vue';
import type { PostBlockItem } from '../../editor-tools/PostsBlock';
import NotificationDropdown from '../../components/NotificationDropdown.vue';
import EditorialSlideover from '../../components/EditorialSlideover.vue';
import ImageAnchorPicker from '../../components/ImageAnchorPicker.vue';
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

interface Author {
    id: number;
    name: string;
    avatar_url?: string | null;
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
        cover_video?: MediaItem | null;
        cover_video_id?: number | null;
        featured_image_anchor?: { x: number; y: number } | null;
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
    authors?: Author[];
    canAssignAuthor?: boolean;
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

// Watch for current_version_uuid changes after save/redirect
watch(
    () => props.post.current_version_uuid,
    (newVersionUuid) => {
        if (newVersionUuid && newVersionUuid !== currentVersionUuid.value) {
            currentVersionUuid.value = newVersionUuid;
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

// Show "Make Live" button when viewing a non-active version on a published post
const canMakeVersionLive = computed(() => {
    return props.post.status === 'published' && !isCurrentVersionActive.value && currentVersionUuid.value;
});

// Check if user is an Editor or Admin (can always edit)
const userRoles = computed(() => {
    return (page.props.auth as any)?.user?.roles || [];
});

const isEditorOrAdmin = computed(() => {
    return userRoles.value.includes('Editor') || userRoles.value.includes('Admin');
});

// Versions that can be edited: draft, copydesk (for editors), or rejected
const isCurrentVersionEditable = computed(() => {
    const status = currentVersionInfo.value?.workflow_status;
    // Editors can edit any version (draft, copydesk, even published)
    if (isEditorOrAdmin.value) return true;
    // Writers can only edit draft or rejected versions
    return status === 'draft' || status === 'rejected';
});

// Read-only if locked by someone else OR if non-editor viewing non-editable version
const isReadOnly = computed(() => {
    // If locked by someone else, it's read-only
    if (isLockedByOther.value && !canTakeOver.value) return true;
    // Editors can always edit - they edit the current version directly
    if (isEditorOrAdmin.value) return false;
    // For non-editors: read-only if viewing active version or non-editable version
    if (isCurrentVersionActive.value) return true;
    if (!isCurrentVersionEditable.value) return true;
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

// Repeater input values (using reactive state instead of direct DOM manipulation)
const repeaterInputValues = ref<Record<string, string>>({});
const groupedSectionInputValues = ref<Record<string, string>>({});
const groupedItemInputValues = ref<Record<string, Record<number, string>>>({});

// Helper to get/set repeater input value
function getRepeaterInput(fieldName: string): string {
    return repeaterInputValues.value[fieldName] ?? '';
}

function setRepeaterInput(fieldName: string, value: string) {
    repeaterInputValues.value[fieldName] = value;
}

function clearRepeaterInput(fieldName: string) {
    repeaterInputValues.value[fieldName] = '';
}

// Helper to get/set grouped section input value
function getGroupedSectionInput(fieldName: string): string {
    return groupedSectionInputValues.value[fieldName] ?? '';
}

function setGroupedSectionInput(fieldName: string, value: string) {
    groupedSectionInputValues.value[fieldName] = value;
}

function clearGroupedSectionInput(fieldName: string) {
    groupedSectionInputValues.value[fieldName] = '';
}

// Helper to get/set grouped item input value
function getGroupedItemInput(fieldName: string, sectionIndex: number): string {
    return groupedItemInputValues.value[fieldName]?.[sectionIndex] ?? '';
}

function setGroupedItemInput(fieldName: string, sectionIndex: number, value: string) {
    if (!groupedItemInputValues.value[fieldName]) {
        groupedItemInputValues.value[fieldName] = {};
    }
    groupedItemInputValues.value[fieldName][sectionIndex] = value;
}

function clearGroupedItemInput(fieldName: string, sectionIndex: number) {
    if (groupedItemInputValues.value[fieldName]) {
        groupedItemInputValues.value[fieldName][sectionIndex] = '';
    }
}

// Bulk mode state for grouped-repeater fields
interface GroupedRepeaterSection {
    section: string;
    items: string[];
}

const bulkModeFields = ref<Record<string, boolean>>({});
const bulkTextValues = ref<Record<string, string>>({});

function isBulkMode(fieldName: string): boolean {
    return bulkModeFields.value[fieldName] ?? false;
}

function toggleBulkMode(fieldName: string) {
    const newMode = !bulkModeFields.value[fieldName];
    bulkModeFields.value[fieldName] = newMode;

    if (newMode) {
        // Switching to bulk mode - convert structure to markdown
        const sections = (form.custom_fields?.[fieldName] as GroupedRepeaterSection[]) ?? [];
        bulkTextValues.value[fieldName] = sectionsToMarkdown(sections);
    } else {
        // Switching to form mode - parse markdown to structure
        const markdown = bulkTextValues.value[fieldName] || '';
        const sections = markdownToSections(markdown);
        form.custom_fields = { ...form.custom_fields, [fieldName]: sections };
    }
}

function getBulkText(fieldName: string): string {
    return bulkTextValues.value[fieldName] ?? '';
}

function setBulkText(fieldName: string, value: string) {
    bulkTextValues.value[fieldName] = value;
}

function applyBulkText(fieldName: string) {
    const markdown = bulkTextValues.value[fieldName] || '';
    const sections = markdownToSections(markdown);
    form.custom_fields = { ...form.custom_fields, [fieldName]: sections };
}

// Convert sections array to markdown format
function sectionsToMarkdown(sections: GroupedRepeaterSection[]): string {
    if (!sections || sections.length === 0) return '';

    return sections.map(section => {
        const header = section.section ? `## ${section.section}` : '## (Untitled)';
        const items = (section.items || []).map(item => `- ${item}`).join('\n');
        return items ? `${header}\n${items}` : header;
    }).join('\n\n');
}

// Parse markdown format to sections array
function markdownToSections(markdown: string): GroupedRepeaterSection[] {
    if (!markdown.trim()) return [];

    const sections: GroupedRepeaterSection[] = [];
    const lines = markdown.split('\n');

    let currentSection: GroupedRepeaterSection | null = null;

    for (const line of lines) {
        const trimmed = line.trim();

        // Check for section header (## Section Name)
        if (trimmed.startsWith('## ')) {
            if (currentSection) {
                sections.push(currentSection);
            }
            currentSection = {
                section: trimmed.slice(3).trim(),
                items: []
            };
        }
        // Check for item (- item or * item)
        else if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
            const itemText = trimmed.slice(2).trim();
            if (itemText) {
                if (!currentSection) {
                    // Create default section if items come before any header
                    currentSection = { section: '', items: [] };
                }
                currentSection.items.push(itemText);
            }
        }
        // Plain text line (not empty) - treat as item if we have a section
        else if (trimmed && currentSection) {
            currentSection.items.push(trimmed);
        }
    }

    // Push last section
    if (currentSection) {
        sections.push(currentSection);
    }

    return sections;
}

// Use workflow composable
const workflow = useWorkflow(props.workflowConfig);
const transitionLoading = workflow.loading;
const toast = useToast();

// Get the current workflow state from config (for badge styling)
const currentWorkflowState = computed(() => workflow.getState(workflowStatus.value));

// Available workflow transitions from current state (filtered by user roles)
const availableTransitions = computed(() => workflow.getAvailableTransitions(workflowStatus.value, userRoles.value));

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
            is_active: v.is_active,
        };
    });
});

// Build dropdown items with "Make Live" action for non-active versions
const versionDropdownMenuItems = computed(() => {
    // Version list items
    const versionItems = versionDropdownItems.value.map(v => ({
        label: v.label + (v.suffix ? ` (${v.suffix})` : ''),
        icon: v.is_current ? 'i-lucide-check' : (v.is_active ? 'i-lucide-globe' : 'i-lucide-git-branch'),
        onSelect: () => switchToVersion(v.value),
    }));

    // Check if viewing a non-active version on a published post
    const currentVersion = versionDropdownItems.value.find(v => v.is_current);
    if (currentVersion && !currentVersion.is_active && props.post.status === 'published') {
        // Put "Make Live" action at the TOP so it's always visible
        return [
            [
                {
                    label: 'Make this version live',
                    icon: 'i-lucide-rocket',
                    color: 'primary' as const,
                    onSelect: () => openMakeLiveModal(currentVersion.value),
                },
            ],
            versionItems,
        ];
    }

    return [versionItems];
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

// Make a version the active/live version (revert)
const revertingVersion = ref(false);
const showMakeLiveModal = ref(false);
const pendingMakeLiveVersionUuid = ref<string | null>(null);

function openMakeLiveModal(versionUuid: string) {
    pendingMakeLiveVersionUuid.value = versionUuid;
    showMakeLiveModal.value = true;
}

async function confirmMakeVersionLive() {
    if (revertingVersion.value || !pendingMakeLiveVersionUuid.value) return;

    revertingVersion.value = true;
    try {
        await axios.post(`/cms/workflow/versions/${pendingMakeLiveVersionUuid.value}/make-live`);
        showMakeLiveModal.value = false;
        // Reload the page to get the new version
        router.reload({ preserveState: false });
    } catch (error: any) {
        console.error('Failed to make version live:', error);
        toast.add({
            title: 'Error',
            description: error.response?.data?.message || 'Failed to make this version live',
            color: 'error',
        });
    } finally {
        revertingVersion.value = false;
    }
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

// Dynamic placeholders based on language and post type
const placeholders = computed(() => {
    const isReview = form.post_type === 'restaurant-review';

    return {
        kicker: isReview
            ? (isRtl.value ? 'ރެސްޓޯރެންޓް / ކެފੇ ނަން' : 'Restaurant / Cafe Name')
            : (isRtl.value ? 'ކިކާ (މިސާލު: ފީޗާ)' : 'KICKER (e.g., TASTY FEATURE)'),
        title: isRtl.value ? 'ސުرُޚީ' : 'Title',
        subtitle: isRtl.value ? 'ސަބް ސުرُޚީ...' : 'Add a subtitle...',
        excerpt: isRtl.value ? 'ޚުލާސާ / ޑެކް ލިޔޭ...' : 'Write a deck or summary...',
    };
});

// Handle keydown for Dhivehi input fields
function onDhivehiKeyDown(e: KeyboardEvent) {
    if (isRtl.value) {
        dhivehiKeyDown(e, e.target as HTMLInputElement | HTMLTextAreaElement);
    }
}

// Check if title is auto-generated "Untitled {PostType}" - show empty in that case
const isAutoGeneratedTitle = (title: string | null, postType: string) => {
    if (!title) return true;
    const pattern = new RegExp(`^Untitled\\s+${postType}$`, 'i');
    return pattern.test(title);
};

const initialTitle = isAutoGeneratedTitle(props.post.title, props.post.post_type)
    ? ''
    : props.post.title;

const form = useForm({
    title: initialTitle,
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
    cover_video_id: props.post.cover_video_id ?? null,
    featured_image_anchor: props.post.featured_image_anchor || { x: 50, y: 0 },
    custom_fields: (props.post.custom_fields || {}) as Record<string, unknown>,
    meta_title: props.post.meta_title || '',
    meta_description: props.post.meta_description || '',
    show_author: props.post.show_author ?? true,
    allow_comments: props.post.allow_comments ?? true,
    author_id: props.post.author?.id ?? null,
});

// Authors list for selector
const authorOptions = computed(() => {
    return (props.authors || []).map(author => ({
        label: author.name,
        value: author.id,
        avatar: author.avatar_url,
    }));
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
const mediaPickerPurpose = ref<'cover' | 'cover-video' | 'editor'>('cover');
const selectedFeaturedMedia = ref<MediaItem | null>(props.post.featured_media || null);
const selectedCoverVideo = ref<MediaItem | null>(props.post.cover_video || null);

// For editor callback
let editorMediaResolve: ((items: MediaBlockItem[] | null) => void) | null = null;

// Open media picker for cover photo
function openCoverPicker() {
    mediaPickerType.value = 'images';
    mediaPickerMultiple.value = false;
    mediaPickerPurpose.value = 'cover';
    mediaPickerOpen.value = true;
}

// Open media picker for cover video
function openCoverVideoPicker() {
    mediaPickerType.value = 'videos';
    mediaPickerMultiple.value = false;
    mediaPickerPurpose.value = 'cover-video';
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
    } else if (mediaPickerPurpose.value === 'cover-video') {
        // Cover video selection
        if (items.length > 0) {
            const item = items[0];
            selectedCoverVideo.value = item;
            form.cover_video_id = item.id;
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
            // Video-specific fields for playback
            type: item.type,
            embed_url: item.embed_url,
            embed_provider: item.embed_provider,
            embed_video_id: item.embed_video_id,
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

// Post picker state for editor block
const postPickerOpen = ref(false);
let editorPostResolve: ((items: PostBlockItem[] | null) => void) | null = null;

// Callback for BlockEditor to open post picker
const handleEditorSelectPosts: PostSelectCallback = () => {
    return new Promise((resolve) => {
        editorPostResolve = resolve;
        postPickerOpen.value = true;
    });
};

// Handle post selection from picker
function handlePostSelect(posts: PostBlockItem[]) {
    if (editorPostResolve) {
        editorPostResolve(posts.length > 0 ? posts : null);
        editorPostResolve = null;
    }
}

// Handle post picker close without selection
function handlePostPickerClose(open: boolean) {
    if (!open && editorPostResolve) {
        editorPostResolve(null);
        editorPostResolve = null;
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

// Save state
const lastSaved = ref<Date | null>(null);
const isSaving = ref(false);

// Track unsaved changes
const hasUnsavedChanges = ref(false);
const initialLoadComplete = ref(false);

// Idle auto-save (5 minutes of inactivity)
const IDLE_SAVE_TIMEOUT = 5 * 60 * 1000; // 5 minutes in milliseconds
const idleTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const lastActivity = ref<Date>(new Date());

// Reset idle timer on user activity
function resetIdleTimer() {
    lastActivity.value = new Date();

    if (idleTimer.value) {
        clearTimeout(idleTimer.value);
    }

    // Only set timer if there might be unsaved changes
    if (hasUnsavedChanges.value && lockStatus.value.isMine && !isReadOnly.value) {
        idleTimer.value = setTimeout(() => {
            if (hasUnsavedChanges.value && form.title.trim()) {
                manualSave();
            }
        }, IDLE_SAVE_TIMEOUT);
    }
}

// Track user activity events
function setupIdleDetection() {
    const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart'];
    events.forEach(event => {
        document.addEventListener(event, resetIdleTimer, { passive: true });
    });
}

function cleanupIdleDetection() {
    const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart'];
    events.forEach(event => {
        document.removeEventListener(event, resetIdleTimer);
    });
    if (idleTimer.value) {
        clearTimeout(idleTimer.value);
        idleTimer.value = null;
    }
}

// Mark initial load complete after first render
onMounted(() => {
    nextTick(() => {
        initialLoadComplete.value = true;
    });
});

// Watch for changes to mark as dirty (skip initial load)
watch(
    () => [
        form.title,
        form.subtitle,
        form.kicker,
        form.excerpt,
        form.content,
        form.slug,
        form.category_id,
        form.featured_tag_id,
        form.sponsor_id,
        form.tags,
        form.template,
        form.scheduled_at,
        form.featured_media_id,
        form.cover_video_id,
        form.featured_image_anchor,
        form.custom_fields,
        form.meta_title,
        form.meta_description,
        form.show_author,
        form.post_type,
    ],
    () => {
        if (initialLoadComplete.value) {
            hasUnsavedChanges.value = true;
            // Reset idle timer when content changes
            resetIdleTimer();
        }
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
        onError: (errors) => {
            isSaving.value = false;
            // Show specific validation errors
            const errorMessages = Object.values(errors).flat();
            if (errorMessages.length > 0) {
                const description = errorMessages.length === 1
                    ? errorMessages[0]
                    : `${errorMessages.length} issues need attention`;
                toast.add({
                    title: 'Unable to save',
                    description: description as string,
                    color: 'error',
                    duration: 5000,
                });
            } else {
                toast.add({ title: 'Error', description: 'Failed to save changes', color: 'error' });
            }
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
    // Setup idle detection for auto-save after 5 minutes of inactivity
    setupIdleDetection();
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
    // Stop heartbeat and release lock
    stopHeartbeat();
    releaseLock();
    // Cleanup idle detection
    cleanupIdleDetection();
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

function removeCoverVideo() {
    selectedCoverVideo.value = null;
    form.cover_video_id = null;
}

// Category and tag options for sidebar
const flattenedCategories = computed(() => {
    const flatten = (cats: Category[], depth = 0): { label: string; value: number; depth: number }[] => {
        return cats.flatMap((cat) => {
            // Use non-breaking spaces for visual indentation in dropdown
            const indent = '\u00A0\u00A0\u00A0\u00A0'.repeat(depth);
            const result = [{ label: indent + cat.name, value: cat.id, depth }];
            if (cat.children && cat.children.length > 0) {
                result.push(...flatten(cat.children, depth + 1));
            }
            return result;
        });
    };
    return flatten(props.categories);
});

// Reactive tags list (can be extended when creating new tags inline)
const localTags = ref<Tag[]>([...props.tags]);

const tagOptions = computed(() =>
    localTags.value.map((tag) => ({ label: tag.name, value: tag.id }))
);

// Tags not yet selected (for the add dropdown)
const availableTagOptions = computed(() =>
    tagOptions.value.filter((tag) => !form.tags.includes(tag.value))
);

// Get tag label by ID
function getTagLabel(tagId: number): string | undefined {
    const tag = localTags.value.find((t) => t.id === tagId);
    return tag?.name;
}

// Remove a tag from selection
function removeTag(tagId: number) {
    const index = form.tags.indexOf(tagId);
    if (index > -1) {
        form.tags.splice(index, 1);
    }
}

// Add a tag to selection
function addTag(tagId: number | null) {
    if (tagId && !form.tags.includes(tagId)) {
        form.tags.push(tagId);
    }
}

// Custom tag input state
const tagInputRef = ref<HTMLInputElement | null>(null);
const tagSearchQuery = ref('');
const showTagSuggestions = ref(false);
const tagSuggestions = ref<{ id: number; name: string; slug: string }[]>([]);
const isSearchingTags = ref(false);
let tagSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

// Search tags via API with debounce
async function searchTags(query: string) {
    if (!query.trim()) {
        tagSuggestions.value = [];
        return;
    }

    isSearchingTags.value = true;
    try {
        const response = await axios.get('/cms/tags/search', {
            params: {
                q: query,
                exclude: form.tags,
                limit: 10,
            },
        });
        tagSuggestions.value = response.data;
    } catch (error) {
        console.error('Failed to search tags:', error);
        tagSuggestions.value = [];
    } finally {
        isSearchingTags.value = false;
    }
}

// Debounced tag search
function debouncedTagSearch(query: string) {
    if (tagSearchDebounceTimer) {
        clearTimeout(tagSearchDebounceTimer);
    }
    tagSearchDebounceTimer = setTimeout(() => {
        searchTags(query);
    }, 300);
}

// Check if the search query exactly matches an existing tag in suggestions
const tagExistsInSuggestions = computed(() => {
    const query = tagSearchQuery.value.toLowerCase().trim();
    if (!query) return false;
    return tagSuggestions.value.some((tag) => tag.name.toLowerCase() === query);
});

// Handle tag input
function onTagInput() {
    showTagSuggestions.value = true;
    debouncedTagSearch(tagSearchQuery.value);
}

// Handle Enter key in tag input
async function onTagEnter() {
    const query = tagSearchQuery.value.trim();
    if (!query) return;

    // Check if it matches an existing tag in suggestions
    const existingTag = tagSuggestions.value.find((tag) => tag.name.toLowerCase() === query.toLowerCase());

    if (existingTag) {
        selectTagSuggestion(existingTag.id, existingTag.name);
    } else {
        // Create new tag
        await createAndAddTag();
    }
}

// Handle backspace to remove last tag when input is empty
function onTagBackspace() {
    if (tagSearchQuery.value === '' && form.tags.length > 0) {
        form.tags.pop();
    }
}

// Handle blur on tag input
function onTagInputBlur() {
    // Delay to allow click on suggestions
    setTimeout(() => {
        showTagSuggestions.value = false;
    }, 200);
}

// Select a tag from suggestions
function selectTagSuggestion(tagId: number, tagName: string) {
    addTag(tagId);
    // Add to localTags so getTagLabel works
    if (!localTags.value.find((t) => t.id === tagId)) {
        localTags.value.push({ id: tagId, name: tagName, slug: '' });
    }
    // Clear input immediately
    tagSearchQuery.value = '';
    tagSuggestions.value = [];
    showTagSuggestions.value = false;
    // Focus after a small delay to ensure state is updated
    nextTick(() => {
        tagInputRef.value?.focus();
    });
}

// Create and add a new tag
async function createAndAddTag() {
    const name = tagSearchQuery.value.trim();
    if (!name) return;

    const newTag = await createTag(name);
    if (newTag) {
        form.tags.push(newTag.id);
        tagSearchQuery.value = '';
        tagSuggestions.value = [];
        showTagSuggestions.value = false;
        tagInputRef.value?.focus();
    }
}

const sponsorOptions = computed(() =>
    props.sponsors.map((sponsor) => ({ label: sponsor.name, value: sponsor.id }))
);

// Create a new tag inline (for multi-select tags) - kept for featured tag
async function onCreateTag(name: string) {
    const newTag = await createTag(name);
    if (newTag) {
        form.tags.push(newTag.id);
    }
}

// Create a new featured tag inline (for single-select)
async function onCreateFeaturedTag(name: string) {
    const newTag = await createTag(name);
    if (newTag) {
        form.featured_tag_id = newTag.id;
    }
}

// Shared function to create a tag
async function createTag(name: string): Promise<{ id: number; name: string; slug: string } | null> {
    try {
        const langCode = props.language?.code || props.post.language_code || 'en';
        const response = await axios.post('/cms/tags', {
            name: { [langCode]: name },
        });
        const newTag = response.data;
        // Add to local tags list
        localTags.value.push({ id: newTag.id, name: newTag.name, slug: newTag.slug });
        toast.add({
            title: 'Tag Created',
            description: `Tag "${name}" has been created.`,
            color: 'success',
        });
        return newTag;
    } catch (error: any) {
        toast.add({
            title: 'Error',
            description: error.response?.data?.message || 'Failed to create tag',
            color: 'error',
        });
        return null;
    }
}

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

// Quick unpublish - uses dedicated unpublish endpoint
async function unpublishPost() {
    isUnpublishing.value = true;
    try {
        const result = await workflow.unpublish('posts', props.post.uuid);
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
    // Use the current version being edited, not the active (published) version
    const langCode = props.language?.code || props.post.language_code || 'en';
    const versionParam = currentVersionUuid.value ? `?version=${currentVersionUuid.value}` : '';
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
                                :items="versionDropdownMenuItems"
                                :ui="{ content: 'w-56', viewport: 'max-h-60' }"
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
                            <!-- Workflow Actions Dropdown -->
                            <UDropdownMenu
                                v-if="availableTransitions.length > 0"
                                :items="availableTransitions.map(t => ({
                                    label: t.label,
                                    icon: 'i-lucide-arrow-right',
                                    onSelect: () => performQuickTransition(t),
                                }))"
                            >
                                <UButton
                                    color="primary"
                                    variant="soft"
                                    size="xs"
                                    trailing-icon="i-lucide-chevron-down"
                                    class="shrink-0"
                                    :loading="transitionLoading"
                                >
                                    <UIcon name="i-lucide-workflow" class="size-3 mr-1" />
                                    <span class="hidden sm:inline">Actions</span>
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
                                :loading="form.processing || isSaving"
                                :disabled="!hasUnsavedChanges && !!lastSaved"
                                @click="manualSave()"
                            >
                                {{ hasUnsavedChanges ? 'Save' : 'Saved' }}
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

                            <!-- Word count indicator -->
                            <div class="hidden sm:flex items-center gap-1.5 text-xs text-muted px-2">
                                <UIcon name="i-lucide-file-text" class="size-3.5" />
                                <span>{{ wordCount }} words</span>
                                <span class="text-muted/50">·</span>
                                <span>{{ readingTime }}</span>
                            </div>
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
                                <input type="hidden" name="kicker" :value="form.kicker || ''" />
                                <input type="hidden" name="subtitle" :value="form.subtitle || ''" />
                                <input type="hidden" name="excerpt" :value="form.excerpt || ''" />
                                <input type="hidden" name="content" :value="JSON.stringify(form.content || {})" />
                                <input type="hidden" name="template" :value="form.template || 'default'" />
                                <input type="hidden" name="language_code" :value="language?.code || 'en'" />
                                <input type="hidden" name="featured_image_url" :value="selectedFeaturedMedia?.url || selectedFeaturedMedia?.thumbnail_url || ''" />
                                <input type="hidden" name="featured_image_anchor" :value="JSON.stringify(form.featured_image_anchor || { x: 50, y: 0 })" />
                                <input type="hidden" name="author[id]" :value="post.author?.id || ''" />
                                <input type="hidden" name="author[name]" :value="post.author?.name || ''" />
                                <input type="hidden" name="show_author" :value="form.show_author ? '1' : '0'" />
                                <input type="hidden" name="category" :value="selectedCategoryName || ''" />
                                <input type="hidden" name="tags" :value="(selectedTagNames || []).join(',')" />
                                <input type="hidden" name="post_type" :value="form.post_type || 'article'" />
                                <input type="hidden" name="custom_fields" :value="JSON.stringify(form.custom_fields || {})" />
                                <input type="hidden" name="cover_video" :value="selectedCoverVideo ? JSON.stringify(selectedCoverVideo) : ''" />
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
                        <div v-else :class="['mx-auto py-12 px-6 md:pl-20 md:pr-8', isFullscreen ? 'max-w-screen-2xl' : 'max-w-4xl']">
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

                            <!-- Validation Errors Alert -->
                            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-lg bg-error/10 border border-error/20">
                                <div class="flex items-start gap-3">
                                    <UIcon name="i-lucide-alert-circle" class="size-5 text-error shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-error">Please fix the following issues:</p>
                                        <ul class="mt-2 space-y-1 text-sm text-error/80">
                                            <li v-for="(error, field) in form.errors" :key="field">• {{ error }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Make Version Live Banner -->
                            <div
                                v-if="canMakeVersionLive"
                                class="mb-6 p-4 rounded-lg bg-primary/5 border border-primary/20"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 rounded-full flex items-center justify-center bg-primary/10">
                                            <UIcon name="i-lucide-history" class="size-5 text-primary" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">Viewing {{ currentVersionLabel }}</p>
                                            <p class="text-xs text-muted">This is not the live version</p>
                                        </div>
                                    </div>
                                    <UButton
                                        color="primary"
                                        size="sm"
                                        :loading="revertingVersion"
                                        @click="openMakeLiveModal(currentVersionUuid!)"
                                    >
                                        <UIcon name="i-lucide-rocket" class="size-4 mr-1" />
                                        Make Live
                                    </UButton>
                                </div>
                            </div>

                            <!-- Post Metadata (Medium-inspired) -->
                            <div class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-base">
                                <!-- Category inline -->
                                <span class="text-muted">in</span>
                                <USelectMenu
                                    v-model="form.category_id"
                                    :items="flattenedCategories"
                                    value-key="value"
                                    placeholder="Category"
                                    variant="none"
                                    size="md"
                                    :disabled="isReadOnly"
                                    :search-input="{ placeholder: 'Search...', variant: 'none' }"
                                    :ui="{ content: 'min-w-56' }"
                                    :content="{ align: 'start' }"
                                    :class="[
                                        'transition-colors',
                                        form.errors.category_id ? 'text-error' : 'text-highlighted hover:text-primary'
                                    ]"
                                />
                                <span class="text-muted/40">·</span>
                                <!-- Featured Tag -->
                                <USelectMenu
                                    v-model="form.featured_tag_id"
                                    :items="tagOptions"
                                    value-key="value"
                                    placeholder="Featured Tag"
                                    variant="none"
                                    searchable
                                    :create-item="!isReadOnly"
                                    size="md"
                                    :disabled="isReadOnly"
                                    :ui="{ content: 'min-w-56' }"
                                    :content="{ align: 'start' }"
                                    :class="[
                                        'transition-colors',
                                        form.errors.featured_tag_id ? 'text-error' : 'text-highlighted hover:text-primary'
                                    ]"
                                    @create="onCreateFeaturedTag"
                                />
                                <span class="text-muted/40">·</span>
                                <!-- Post Type -->
                                <USelectMenu
                                    v-model="form.post_type"
                                    :items="postTypes"
                                    value-key="value"
                                    variant="none"
                                    size="md"
                                    :search-input="false"
                                    :disabled="isReadOnly"
                                    :ui="{ content: 'min-w-40' }"
                                    :content="{ align: 'start' }"
                                    class="text-highlighted hover:text-primary transition-colors"
                                />

                                <!-- Template switcher (subtle, right-aligned) -->
                                <div class="ml-auto flex items-center">
                                    <div class="flex bg-elevated/50 rounded-lg p-1">
                                        <button
                                            v-for="template in templates"
                                            :key="template.key"
                                            type="button"
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm transition-all',
                                                form.template === template.key
                                                    ? 'bg-default text-highlighted shadow-sm'
                                                    : 'text-muted hover:text-highlighted',
                                                isReadOnly ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
                                            ]"
                                            :disabled="isReadOnly"
                                            :title="template.description"
                                            @click="form.template = template.key"
                                        >
                                            <UIcon :name="template.icon" class="size-4" />
                                            <span class="hidden sm:inline">{{ template.name }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Workflow Actions (compact bar) -->
                            <div class="mb-6 flex flex-wrap items-center gap-4">
                                <div v-if="availableTransitions.length > 0 && !isReadOnly" class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs text-muted">Actions:</span>
                                    <UButton
                                        v-for="transition in availableTransitions"
                                        :key="`${transition.from}-${transition.to}`"
                                        size="xs"
                                        :color="workflow.getStateColor(transition.to)"
                                        variant="soft"
                                        :loading="transitionLoading"
                                        @click="performQuickTransition(transition)"
                                    >
                                        <template #leading>
                                            <UIcon name="i-lucide-arrow-right" class="size-3" />
                                        </template>
                                        {{ transition.label }}
                                    </UButton>
                                </div>

                                <!-- Author Selector (for users with assign-author permission) -->
                                <div v-if="props.canAssignAuthor && authorOptions.length > 0" class="flex items-center gap-2 ml-auto">
                                    <span class="text-xs text-muted">Author:</span>
                                    <USelectMenu
                                        v-model="form.author_id"
                                        :items="authorOptions"
                                        value-key="value"
                                        placeholder="Select author"
                                        size="xs"
                                        :disabled="isReadOnly"
                                        searchable
                                        :search-input="{ placeholder: 'Search authors...' }"
                                        class="w-40"
                                    />
                                </div>

                                <!-- Show Author Toggle -->
                                <label class="flex items-center gap-2 cursor-pointer" :class="{ 'ml-auto': !props.canAssignAuthor }">
                                    <USwitch
                                        v-model="form.show_author"
                                        size="sm"
                                        :disabled="isReadOnly"
                                    />
                                    <span class="text-xs font-medium text-muted">Show author</span>
                                </label>
                            </div>

                            <!-- Cover Image with Anchor Picker -->
                            <div class="mb-6">
                                <div v-if="selectedFeaturedMedia" class="space-y-3">
                                    <!-- Image with anchor picker overlay -->
                                    <ImageAnchorPicker
                                        v-model="form.featured_image_anchor"
                                        :image-url="selectedFeaturedMedia.url || selectedFeaturedMedia.thumbnail_url"
                                        :disabled="isReadOnly"
                                    />
                                    <!-- Actions -->
                                    <div v-if="!isReadOnly" class="flex items-center justify-between">
                                        <span class="text-xs text-muted">Click on the image to set the focal point</span>
                                        <div class="flex gap-1.5">
                                            <UButton
                                                color="neutral"
                                                variant="ghost"
                                                icon="i-lucide-image-plus"
                                                size="xs"
                                                @click="openCoverPicker"
                                            >
                                                Change
                                            </UButton>
                                            <UButton
                                                color="error"
                                                variant="ghost"
                                                icon="i-lucide-trash-2"
                                                size="xs"
                                                @click="removeFeaturedMedia"
                                            >
                                                Remove
                                            </UButton>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    v-else-if="!isReadOnly"
                                    type="button"
                                    class="w-full h-40 border border-dashed border-default rounded-lg flex flex-col items-center justify-center gap-2 hover:border-primary hover:bg-primary/5 transition-colors"
                                    @click="openCoverPicker"
                                >
                                    <UIcon name="i-lucide-image-plus" class="size-8 text-muted" />
                                    <span class="text-sm text-muted">Add cover image</span>
                                </button>
                                <div v-else class="w-full h-40 border border-dashed border-default rounded-lg flex flex-col items-center justify-center gap-2">
                                    <UIcon name="i-lucide-image-off" class="size-8 text-muted" />
                                    <span class="text-sm text-muted">No cover image</span>
                                </div>
                            </div>

                            <!-- Cover Video (Optional) -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Cover Video</span>
                                    <span class="text-xs text-muted">(Optional)</span>
                                </div>
                                <div v-if="selectedCoverVideo" class="space-y-3">
                                    <!-- Video thumbnail -->
                                    <div class="relative aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                        <img
                                            v-if="selectedCoverVideo.thumbnail_url"
                                            :src="selectedCoverVideo.thumbnail_url"
                                            :alt="selectedCoverVideo.title || 'Cover video'"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <UIcon name="i-lucide-video" class="size-12 text-gray-500" />
                                        </div>
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                            <div class="w-12 h-12 rounded-full bg-white/90 flex items-center justify-center">
                                                <UIcon name="i-lucide-play" class="size-6 text-gray-900 ml-0.5" />
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Video title and actions -->
                                    <div v-if="!isReadOnly" class="flex items-center justify-between">
                                        <span class="text-xs text-muted truncate max-w-[200px]">{{ selectedCoverVideo.title || 'Untitled video' }}</span>
                                        <div class="flex gap-1.5">
                                            <UButton
                                                color="neutral"
                                                variant="ghost"
                                                icon="i-lucide-video"
                                                size="xs"
                                                @click="openCoverVideoPicker"
                                            >
                                                Change
                                            </UButton>
                                            <UButton
                                                color="error"
                                                variant="ghost"
                                                icon="i-lucide-trash-2"
                                                size="xs"
                                                @click="removeCoverVideo"
                                            >
                                                Remove
                                            </UButton>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    v-else-if="!isReadOnly"
                                    type="button"
                                    class="w-full h-24 border border-dashed border-default rounded-lg flex flex-col items-center justify-center gap-2 hover:border-primary hover:bg-primary/5 transition-colors"
                                    @click="openCoverVideoPicker"
                                >
                                    <UIcon name="i-lucide-video" class="size-6 text-muted" />
                                    <span class="text-xs text-muted">Add cover video</span>
                                </button>
                                <div v-else-if="!selectedCoverVideo" class="w-full h-24 border border-dashed border-default rounded-lg flex flex-col items-center justify-center gap-2">
                                    <UIcon name="i-lucide-video-off" class="size-6 text-muted" />
                                    <span class="text-xs text-muted">No cover video</span>
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
                            <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

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
                            <div class="flex items-center justify-between mb-3 h-4">
                                <p v-if="form.errors.title" class="text-error text-xs">{{ form.errors.title }}</p>
                                <span v-else />
                                <span
                                    v-if="form.title.length > 50"
                                    :class="['text-xs transition-opacity', form.title.length > 70 ? 'text-error' : 'text-muted/60']"
                                >
                                    {{ 70 - form.title.length }}
                                </span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

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
                            <div class="flex items-center justify-end mb-3 h-4">
                                <span
                                    v-if="(form.subtitle?.length || 0) > 90"
                                    :class="['text-xs', (form.subtitle?.length || 0) > 120 ? 'text-error' : 'text-muted/60']"
                                >
                                    {{ 120 - (form.subtitle?.length || 0) }}
                                </span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

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
                            <div class="flex items-center justify-end h-4 mb-3">
                                <span
                                    v-if="(form.excerpt?.length || 0) > 120"
                                    :class="['text-xs', (form.excerpt?.length || 0) > 160 ? 'text-error' : 'text-muted/60']"
                                >
                                    {{ 160 - (form.excerpt?.length || 0) }}
                                </span>
                            </div>

                            <!-- Introduction (Recipe only - right after excerpt) -->
                            <template v-if="form.post_type === 'recipe'">
                                <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
                                <textarea
                                    ref="introductionTextarea"
                                    :value="(form.custom_fields?.introduction as string) ?? ''"
                                    :placeholder="isRtl ? 'ރެސިޕީގެ ތައާރަފް ލިޔޭ...' : 'Write a longer introduction for this recipe...'"
                                    rows="3"
                                    :dir="textDirection"
                                    :readonly="isReadOnly"
                                    :class="[
                                        'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 mb-3 resize-none',
                                        textAlign,
                                        isRtl ? 'font-dhivehi text-dhivehi-base leading-relaxed placeholder:font-dhivehi' : 'text-base leading-relaxed',
                                        isReadOnly ? 'cursor-not-allowed opacity-70' : '',
                                    ]"
                                    @input="(e: Event) => form.custom_fields = { ...form.custom_fields, introduction: (e.target as HTMLTextAreaElement).value }"
                                    @keydown="onDhivehiKeyDown"
                                />
                            </template>

                            <!-- Separator before Content Editor -->
                            <div class="border-t border-gray-200 dark:border-gray-700 my-4 mb-8"></div>

                            <!-- Content Editor -->
                            <BlockEditor
                                v-model="form.content"
                                :placeholder="form.post_type === 'recipe' ? 'Write the preparation steps...' : 'Tell your story...'"
                                :rtl="isRtl"
                                :dhivehi-enabled="dhivehiEnabled"
                                :dhivehi-layout="dhivehiLayout"
                                :on-select-media="handleEditorSelectMedia"
                                :on-select-posts="handleEditorSelectPosts"
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
                                        <!-- Skip introduction field - it's rendered separately above the content editor -->
                                        <template v-if="field.name !== 'introduction'">
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
                                                        :model-value="getRepeaterInput(field.name)"
                                                        placeholder="Add new item..."
                                                        class="flex-1"
                                                        :disabled="isReadOnly"
                                                        @update:model-value="setRepeaterInput(field.name, $event as string)"
                                                        @keyup.enter.prevent="() => {
                                                            const value = getRepeaterInput(field.name);
                                                            if (value.trim()) {
                                                                form.custom_fields = {
                                                                    ...form.custom_fields,
                                                                    [field.name]: [...((form.custom_fields?.[field.name] as string[]) ?? []), value.trim()]
                                                                };
                                                                clearRepeaterInput(field.name);
                                                            }
                                                        }"
                                                    />
                                                    <UButton
                                                        color="neutral"
                                                        variant="soft"
                                                        icon="i-lucide-plus"
                                                        :disabled="isReadOnly"
                                                        @click="() => {
                                                            const value = getRepeaterInput(field.name);
                                                            if (value.trim()) {
                                                                form.custom_fields = {
                                                                    ...form.custom_fields,
                                                                    [field.name]: [...((form.custom_fields?.[field.name] as string[]) ?? []), value.trim()]
                                                                };
                                                                clearRepeaterInput(field.name);
                                                            }
                                                        }"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Grouped Repeater Field (for sectioned ingredients) -->
                                        <div v-else-if="field.type === 'grouped-repeater'">
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="text-sm font-medium">{{ field.label }}</label>
                                                <UButton
                                                    size="xs"
                                                    :color="isBulkMode(field.name) ? 'primary' : 'neutral'"
                                                    variant="ghost"
                                                    :icon="isBulkMode(field.name) ? 'i-lucide-list' : 'i-lucide-file-text'"
                                                    :disabled="isReadOnly"
                                                    @click="toggleBulkMode(field.name)"
                                                >
                                                    {{ isBulkMode(field.name) ? 'Form mode' : 'Bulk mode' }}
                                                </UButton>
                                            </div>

                                            <!-- Bulk Mode -->
                                            <div v-if="isBulkMode(field.name)" class="space-y-2">
                                                <UTextarea
                                                    :model-value="getBulkText(field.name)"
                                                    :rows="12"
                                                    autoresize
                                                    size="sm"
                                                    class="w-full font-mono text-xs"
                                                    placeholder="## Section Name
- 1 cup flour
- 2 eggs
- 1 tsp vanilla

## Another Section
- 1/2 cup sugar
- 1 tbsp butter"
                                                    :disabled="isReadOnly"
                                                    @update:model-value="setBulkText(field.name, $event as string)"
                                                    @blur="applyBulkText(field.name)"
                                                />
                                                <p class="text-xs text-muted">
                                                    Use <code class="bg-elevated px-1 rounded">## Section</code> for headers and <code class="bg-elevated px-1 rounded">- item</code> for ingredients. Changes apply on blur.
                                                </p>
                                            </div>

                                            <!-- Form Mode -->
                                            <div v-else class="space-y-3">
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
                                                            v-for="(item, itemIndex) in (section.items || [])"
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
                                                                    const newItems = [...(sections[sectionIndex].items || [])];
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
                                                                    const newItems = [...(sections[sectionIndex].items || [])];
                                                                    newItems.splice(itemIndex, 1);
                                                                    sections[sectionIndex] = { ...sections[sectionIndex], items: newItems };
                                                                    form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                }"
                                                            />
                                                        </div>
                                                        <!-- Add item to section -->
                                                        <div class="flex gap-1.5">
                                                            <UInput
                                                                :model-value="getGroupedItemInput(field.name, sectionIndex)"
                                                                placeholder="Add item..."
                                                                size="sm"
                                                                class="flex-1 min-w-0"
                                                                :disabled="isReadOnly"
                                                                @update:model-value="setGroupedItemInput(field.name, sectionIndex, $event as string)"
                                                                @keyup.enter.prevent="() => {
                                                                    const value = getGroupedItemInput(field.name, sectionIndex);
                                                                    if (value.trim()) {
                                                                        const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                        sections[sectionIndex] = {
                                                                            ...sections[sectionIndex],
                                                                            items: [...(sections[sectionIndex].items || []), value.trim()]
                                                                        };
                                                                        form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                        clearGroupedItemInput(field.name, sectionIndex);
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
                                                                    const value = getGroupedItemInput(field.name, sectionIndex);
                                                                    if (value.trim()) {
                                                                        const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                        sections[sectionIndex] = {
                                                                            ...sections[sectionIndex],
                                                                            items: [...(sections[sectionIndex].items || []), value.trim()]
                                                                        };
                                                                        form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                        clearGroupedItemInput(field.name, sectionIndex);
                                                                    }
                                                                }"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Add new section -->
                                                <div class="flex gap-1.5">
                                                    <UInput
                                                        :model-value="getGroupedSectionInput(field.name)"
                                                        placeholder="Add section (e.g., Pasta, Sauce)..."
                                                        size="sm"
                                                        class="flex-1 min-w-0"
                                                        :disabled="isReadOnly"
                                                        @update:model-value="setGroupedSectionInput(field.name, $event as string)"
                                                        @keyup.enter.prevent="() => {
                                                            const value = getGroupedSectionInput(field.name);
                                                            if (value.trim()) {
                                                                const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                sections.push({ section: value.trim(), items: [] });
                                                                form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                clearGroupedSectionInput(field.name);
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
                                                            const value = getGroupedSectionInput(field.name);
                                                            if (value.trim()) {
                                                                const sections = [...((form.custom_fields?.[field.name] as { section: string; items: string[] }[]) ?? [])];
                                                                sections.push({ section: value.trim(), items: [] });
                                                                form.custom_fields = { ...form.custom_fields, [field.name]: sections };
                                                                clearGroupedSectionInput(field.name);
                                                            }
                                                        }"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        </template>
                                    </template>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-10 pt-6 border-t border-default flex items-center justify-between">
                                <button
                                    type="button"
                                    class="text-sm text-muted hover:text-highlighted flex items-center gap-1.5 transition-colors"
                                    @click="editorialSlideoverOpen = true"
                                >
                                    <UIcon name="i-lucide-panel-right" class="size-4" />
                                    Editorial
                                </button>
                                <button
                                    type="button"
                                    class="text-sm text-muted hover:text-error flex items-center gap-1.5 transition-colors"
                                    @click="openDeleteModal"
                                >
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                    Delete
                                </button>
                            </div>

                            <!-- Tags Section -->
                            <div class="mt-8">
                                <label class="block text-sm font-medium text-highlighted mb-2">Tags</label>

                                <!-- Custom Tag Input -->
                                <div class="space-y-3">
                                    <!-- Tag Input with inline suggestions -->
                                    <div v-if="!isReadOnly" class="relative z-10">
                                        <div class="flex items-center gap-2 p-2 border border-muted rounded-lg bg-default focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all">
                                            <UIcon name="i-lucide-tag" class="size-4 text-muted shrink-0" />
                                            <div class="flex-1 flex flex-wrap items-center gap-2">
                                                <!-- Selected Tags as inline pills -->
                                                <span
                                                    v-for="tagId in form.tags"
                                                    :key="tagId"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded text-sm font-medium"
                                                >
                                                    {{ getTagLabel(tagId) }}
                                                    <button
                                                        type="button"
                                                        class="hover:bg-primary/20 rounded p-0.5 transition-colors"
                                                        @click="removeTag(tagId)"
                                                    >
                                                        <UIcon name="i-lucide-x" class="size-3" />
                                                    </button>
                                                </span>
                                                <!-- Text input -->
                                                <input
                                                    ref="tagInputRef"
                                                    v-model="tagSearchQuery"
                                                    type="text"
                                                    class="flex-1 min-w-[120px] bg-transparent border-none outline-none text-sm placeholder:text-muted"
                                                    placeholder="Type to add tag..."
                                                    @input="onTagInput"
                                                    @keydown.enter.prevent="onTagEnter"
                                                    @keydown.backspace="onTagBackspace"
                                                    @focus="showTagSuggestions = true"
                                                    @blur="onTagInputBlur"
                                                />
                                            </div>
                                        </div>

                                        <!-- Suggestions dropdown -->
                                        <div
                                            v-if="showTagSuggestions && (tagSuggestions.length > 0 || isSearchingTags || (tagSearchQuery.trim() && !tagExistsInSuggestions))"
                                            class="absolute z-50 w-full mt-1 bg-default border border-muted rounded-lg shadow-lg max-h-48 overflow-y-auto"
                                        >
                                            <!-- Loading state -->
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
                                                @mousedown.prevent="selectTagSuggestion(tag.id, tag.name)"
                                            >
                                                <UIcon name="i-lucide-tag" class="size-3.5 text-muted" />
                                                <span>{{ tag.name }}</span>
                                            </button>

                                            <!-- Create new tag option -->
                                            <button
                                                v-if="tagSearchQuery.trim() && !tagExistsInSuggestions && !isSearchingTags"
                                                type="button"
                                                class="w-full px-3 py-2 text-left text-sm hover:bg-muted/50 flex items-center gap-2 text-primary border-t border-muted transition-colors"
                                                @mousedown.prevent="createAndAddTag"
                                            >
                                                <UIcon name="i-lucide-plus" class="size-3.5" />
                                                <span>Create "{{ tagSearchQuery.trim() }}"</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Read-only: just show pills -->
                                    <div v-else-if="form.tags.length > 0" class="flex flex-wrap gap-2">
                                        <span
                                            v-for="tagId in form.tags"
                                            :key="tagId"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 text-primary rounded-full text-sm font-medium"
                                        >
                                            <UIcon name="i-lucide-tag" class="size-3.5" />
                                            {{ getTagLabel(tagId) }}
                                        </span>
                                    </div>

                                    <!-- Empty State for read-only -->
                                    <p v-else-if="isReadOnly" class="text-sm text-muted">
                                        No tags added.
                                    </p>
                                </div>
                            </div>

                            <!-- Additional Settings -->
                            <div class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-4">
                                <!-- Sponsor (if available) -->
                                <div v-if="sponsorOptions.length > 0" class="flex items-center gap-3">
                                    <UIcon name="i-lucide-handshake" class="size-5 text-muted" />
                                    <USelectMenu
                                        v-model="form.sponsor_id"
                                        :items="sponsorOptions"
                                        value-key="value"
                                        placeholder="Add sponsor"
                                        searchable
                                        size="md"
                                        :disabled="isReadOnly"
                                        :ui="{ content: 'min-w-56' }"
                                        class="min-w-48"
                                    />
                                </div>

                                <!-- Allow Comments Toggle -->
                                <div class="flex items-center gap-3">
                                    <UIcon name="i-lucide-message-circle" class="size-5 text-muted" />
                                    <span class="text-sm text-muted">Comments</span>
                                    <USwitch
                                        v-model="form.allow_comments"
                                        :disabled="isReadOnly"
                                    />
                                </div>
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
            default-category="media"
            @select="handleMediaSelect"
            @update:open="handleMediaPickerClose"
        />

        <!-- Post Picker Modal for Editor Block -->
        <EditorPostPickerModal
            v-model:open="postPickerOpen"
            @select="handlePostSelect"
            @update:open="handlePostPickerClose"
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

        <!-- Make Version Live Confirmation Modal -->
        <UModal v-model:open="showMakeLiveModal">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <div class="size-10 rounded-full flex items-center justify-center bg-primary/10">
                                <UIcon name="i-lucide-rocket" class="size-5 text-primary" />
                            </div>
                            <div>
                                <h3 class="font-semibold">Make Version Live</h3>
                                <p class="text-sm text-muted">Restore this version as the active version</p>
                            </div>
                        </div>
                    </template>

                    <p class="text-sm">
                        Are you sure you want to make <strong>{{ currentVersionLabel }}</strong> the live version?
                        This will create a new version with this content and set it as the active published version.
                    </p>

                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <UButton color="neutral" variant="ghost" @click="showMakeLiveModal = false">
                                Cancel
                            </UButton>
                            <UButton color="primary" :loading="revertingVersion" @click="confirmMakeVersionLive">
                                <UIcon name="i-lucide-rocket" class="size-4 mr-1" />
                                Make Live
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
