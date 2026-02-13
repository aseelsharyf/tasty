<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import {
    useWorkflow,
    getWorkflowStateColor,
    formatRelativeDate,
    formatDate,
    type WorkflowConfig,
} from '../composables/useWorkflow';
import { useCmsPath } from '../composables/useCmsPath';

const toast = useToast();
const { cmsPath } = useCmsPath();

// === Type Definitions ===

interface User {
    id: number;
    name: string;
    avatar_url: string | null;
}

interface ContentSnapshot {
    title?: string;
    subtitle?: string;
    excerpt?: string;
    content?: unknown;
    [key: string]: unknown;
}

interface Comment {
    id: number;
    uuid: string;
    content: string;
    block_id: string | null;
    type: 'general' | 'revision_request' | 'approval';
    is_resolved: boolean;
    user: User;
    resolved_by: User | null;
    resolved_at: string | null;
    created_at: string;
}

interface Transition {
    id: number;
    from_status: string;
    to_status: string;
    comment: string | null;
    performed_by: User | null;
    created_at: string;
}

interface Version {
    id: number;
    uuid: string;
    version_number: number;
    workflow_status: string;
    is_active: boolean;
    version_note: string | null;
    created_by: User | null;
    created_at: string;
    transitions: Transition[];
    content_snapshot?: ContentSnapshot;
}

interface WorkflowTransitionConfig {
    from: string;
    to: string;
    roles: string[];
    label: string;
}

interface Category {
    id: number;
    name: string;
    children?: Category[];
}

interface Tag {
    id: number;
    name: string;
}

interface Sponsor {
    id: number;
    name: string;
}

interface PostTypeField {
    name: string;
    label: string;
    type: 'text' | 'number' | 'textarea' | 'select' | 'toggle' | 'repeater';
    suffix?: string;
    options?: string[];
}

interface PostTypeConfig {
    key: string;
    label: string;
    icon?: string;
    fields?: PostTypeField[];
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

// === Props & Emits ===

const props = defineProps<{
    open: boolean;
    contentType: string;
    contentUuid: string;
    currentVersionUuid: string | null;
    workflowStatus: string;
    workflowConfig: WorkflowConfig;
    versionsList?: VersionListItem[];
    categories: Category[];
    tags: Tag[];
    sponsors: Sponsor[];
    postTypes: { value: string; label: string }[];
    currentPostType?: PostTypeConfig | null;
    // Form bindings for settings tab
    categoryId: number | null;
    featuredTagId: number | null;
    sponsorId: number | null;
    selectedTags: number[];
    postType: string;
    slug: string;
    metaTitle: string;
    metaDescription: string;
    customFields: Record<string, unknown>;
    isReadOnly: boolean;
    author?: { name: string } | null;
    publishedAt?: string | null;
    postStatus?: string; // The actual post status (draft/published/scheduled/archived)
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'transition', status: string): void;
    (e: 'refresh'): void;
    (e: 'version-switch', versionUuid: string): void;
    (e: 'update:categoryId', value: number | null): void;
    (e: 'update:featuredTagId', value: number | null): void;
    (e: 'update:sponsorId', value: number | null): void;
    (e: 'update:selectedTags', value: number[]): void;
    (e: 'update:postType', value: string): void;
    (e: 'update:slug', value: string): void;
    (e: 'update:metaTitle', value: string): void;
    (e: 'update:metaDescription', value: string): void;
    (e: 'update:customFields', value: Record<string, unknown>): void;
    (e: 'generate-slug'): void;
    (e: 'create-draft'): void;
}>();

// === Reactive State ===

const activeTab = ref('comments');
const tabs = [
    { key: 'comments', label: 'Comments', icon: 'i-lucide-message-square' },
    { key: 'history', label: 'History', icon: 'i-lucide-history' },
    { key: 'workflow', label: 'Workflow', icon: 'i-lucide-git-branch' },
];

// Comments state
const commentsLoading = ref(false);
const comments = ref<Comment[]>([]);
const newComment = ref('');
const commentType = ref<'general' | 'revision_request' | 'approval'>('general');
const submittingComment = ref(false);

// Versions state
const versionsLoading = ref(false);
const versions = ref<Version[]>([]);
const expandedVersions = ref<Set<number>>(new Set());
const reverting = ref<number | null>(null);
const creatingDraft = ref(false);

// Workflow state
const transitionsLoading = ref(false);
const availableTransitions = ref<WorkflowTransitionConfig[]>([]);
const transitionModalOpen = ref(false);
const selectedTransition = ref<WorkflowTransitionConfig | null>(null);
const transitionComment = ref('');
const submittingTransition = ref(false);

// === Computed ===

const unresolvedCount = computed(() => comments.value.filter(c => !c.is_resolved).length);

const currentState = computed(() => {
    return props.workflowConfig.states.find(s => s.key === props.workflowStatus) || {
        key: props.workflowStatus,
        label: props.workflowStatus,
        color: 'neutral',
        icon: 'i-lucide-circle',
    };
});

// Check if post is currently published (read-only mode)
// Use postStatus prop (the actual post status) with fallback to workflowStatus for backwards compatibility
const isPublished = computed(() => props.postStatus === 'published' || props.workflowStatus === 'published');

// Get the active (published) version UUID from versionsList
const activeVersionUuid = computed(() => {
    return props.versionsList?.find(v => v.is_active)?.uuid || null;
});

// Check if there's already a draft version (other than the current active one)
const existingDraftVersion = computed(() => {
    return props.versionsList?.find(v => v.is_draft && !v.is_active) || null;
});

// Check if current version is parked (can be published)
const isCurrentVersionApproved = computed(() => {
    return props.workflowStatus === 'parked';
});

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
    props.sponsors?.map((sponsor) => ({ label: sponsor.name, value: sponsor.id })) || []
);

// === Methods ===

// Use workflow composable for helpers
const workflow = useWorkflow(props.workflowConfig);
const { getStateColor, getStateLabel, getStateIcon } = workflow;

// Comments methods
async function fetchComments() {
    if (!props.currentVersionUuid) return;
    commentsLoading.value = true;
    try {
        const response = await axios.get(cmsPath(`/workflow/versions/${props.currentVersionUuid}/comments`));
        comments.value = response.data.comments;
    } catch (error) {
        console.error('Failed to fetch comments:', error);
    } finally {
        commentsLoading.value = false;
    }
}

async function addComment() {
    if (!props.currentVersionUuid || !newComment.value.trim()) return;
    submittingComment.value = true;
    try {
        const response = await axios.post(cmsPath(`/workflow/versions/${props.currentVersionUuid}/comments`), {
            content: newComment.value,
            type: commentType.value,
        });
        comments.value.unshift(response.data.comment);
        newComment.value = '';
        commentType.value = 'general';
    } catch (error) {
        console.error('Failed to add comment:', error);
    } finally {
        submittingComment.value = false;
    }
}

async function resolveComment(comment: Comment) {
    try {
        await axios.post(cmsPath(`/workflow/comments/${comment.uuid}/resolve`));
        comment.is_resolved = true;
    } catch (error) {
        console.error('Failed to resolve comment:', error);
    }
}

async function unresolveComment(comment: Comment) {
    try {
        await axios.post(cmsPath(`/workflow/comments/${comment.uuid}/unresolve`));
        comment.is_resolved = false;
    } catch (error) {
        console.error('Failed to unresolve comment:', error);
    }
}

function getCommentTypeIcon(type: string): string {
    return { general: 'i-lucide-message-circle', revision_request: 'i-lucide-alert-circle', approval: 'i-lucide-check-circle' }[type] || 'i-lucide-message-circle';
}

function getCommentTypeColor(type: string): string {
    return { general: 'neutral', revision_request: 'warning', approval: 'success' }[type] || 'neutral';
}

// Version history methods
async function fetchVersions() {
    if (!props.contentUuid) return;
    versionsLoading.value = true;
    try {
        const response = await axios.get(cmsPath(`/workflow/${props.contentType}/${props.contentUuid}/history`));
        versions.value = response.data.versions;
    } catch (error) {
        console.error('Failed to fetch versions:', error);
    } finally {
        versionsLoading.value = false;
    }
}

async function revertToVersion(version: Version) {
    if (!confirm(`Revert to version ${version.version_number}?`)) return;
    reverting.value = version.id;
    try {
        await axios.post(cmsPath(`/workflow/versions/${version.uuid}/revert`));
        toast.add({ title: 'Reverted', description: `Reverted to version ${version.version_number}`, color: 'success' });
        emit('refresh');
        await fetchVersions();
    } catch (error: any) {
        toast.add({ title: 'Error', description: error.response?.data?.message || 'Failed to revert', color: 'error' });
    } finally {
        reverting.value = null;
    }
}

function toggleExpand(versionId: number) {
    if (expandedVersions.value.has(versionId)) {
        expandedVersions.value.delete(versionId);
    } else {
        expandedVersions.value.add(versionId);
    }
}

// Create a new draft from an existing version
async function createDraftFromVersion(version: Version) {
    creatingDraft.value = true;
    try {
        await axios.post(cmsPath(`/workflow/versions/${version.uuid}/revert`));
        toast.add({ title: 'Draft Created', description: `New draft created from version ${version.version_number}`, color: 'success' });
        emit('create-draft');
        emit('refresh');
        await fetchVersions();
    } catch (error: any) {
        toast.add({ title: 'Error', description: error.response?.data?.message || 'Failed to create draft', color: 'error' });
    } finally {
        creatingDraft.value = false;
    }
}

// Create a new draft from the current published version
async function createNewDraft() {
    // If there's already an existing draft, switch to it instead of creating a new one
    if (existingDraftVersion.value) {
        toast.add({
            title: 'Draft Already Exists',
            description: 'Switching to existing draft version.',
            color: 'info'
        });
        emit('version-switch', existingDraftVersion.value.uuid);
        emit('update:open', false);
        return;
    }

    // Use the active (published) version to create the draft
    const sourceVersionUuid = activeVersionUuid.value || props.currentVersionUuid;
    if (!sourceVersionUuid) return;

    const sourceVersion = versions.value.find(v => v.uuid === sourceVersionUuid);
    if (sourceVersion) {
        await createDraftFromVersion(sourceVersion);
    }
}

// Preview a version's content - navigate via URL
function openVersionPreview(version: Version) {
    emit('version-switch', version.uuid);
    emit('update:open', false); // Close slideover after switching
}

// Get dropdown items for a version
function getVersionDropdownItems(version: Version) {
    const items = [];

    // Allow viewing/switching to any version except the current one
    if (version.uuid !== props.currentVersionUuid) {
        items.push({
            label: 'View this version',
            icon: 'i-lucide-eye',
            onSelect: () => openVersionPreview(version),
        });
    }

    if (version.uuid !== props.currentVersionUuid) {
        items.push({
            label: 'Create Draft From This',
            icon: 'i-lucide-file-plus',
            onSelect: () => createDraftFromVersion(version),
        });
    }

    if (version.uuid !== props.currentVersionUuid && !isPublished.value) {
        items.push({
            label: 'Revert to this',
            icon: 'i-lucide-rotate-ccw',
            onSelect: () => revertToVersion(version),
        });
    }

    return items;
}

// Workflow methods
async function fetchTransitions() {
    if (!props.currentVersionUuid) return;
    transitionsLoading.value = true;
    try {
        const response = await axios.get(cmsPath(`/workflow/versions/${props.currentVersionUuid}/transitions`));
        availableTransitions.value = response.data.transitions;
    } catch (error) {
        console.error('Failed to fetch transitions:', error);
        availableTransitions.value = props.workflowConfig.transitions.filter(t => t.from === props.workflowStatus);
    } finally {
        transitionsLoading.value = false;
    }
}

function openTransitionModal(transition: WorkflowTransitionConfig) {
    selectedTransition.value = transition;
    transitionComment.value = '';
    transitionModalOpen.value = true;
}

async function performTransition() {
    if (!props.currentVersionUuid || !selectedTransition.value) return;
    submittingTransition.value = true;
    try {
        await axios.post(cmsPath(`/workflow/versions/${props.currentVersionUuid}/transition`), {
            to_status: selectedTransition.value.to,
            comment: transitionComment.value || null,
        });
        transitionModalOpen.value = false;
        toast.add({ title: 'Success', description: `Transitioned to ${getStateLabel(selectedTransition.value.to)}`, color: 'success' });
        emit('transition', selectedTransition.value.to);
        emit('refresh');
    } catch (error: any) {
        toast.add({ title: 'Error', description: error.response?.data?.message || 'Transition failed', color: 'error' });
    } finally {
        submittingTransition.value = false;
    }
}

// Custom fields helpers
function updateCustomField(key: string, value: unknown) {
    emit('update:customFields', { ...props.customFields, [key]: value });
}

// For repeater fields - add item
function addRepeaterItem(name: string, value: string) {
    if (!value.trim()) return;
    const items = getCustomField<string[]>(name, []);
    updateCustomField(name, [...items, value.trim()]);
}

// For repeater fields - remove item
function removeRepeaterItem(name: string, index: number) {
    const items = getCustomField<string[]>(name, []);
    const newItems = [...items];
    newItems.splice(index, 1);
    updateCustomField(name, newItems);
}

// For repeater fields - update item
function updateRepeaterItem(name: string, index: number, value: string) {
    const items = getCustomField<string[]>(name, []);
    const newItems = [...items];
    newItems[index] = value;
    updateCustomField(name, newItems);
}

function getCustomField<T>(key: string, defaultValue: T): T {
    return (props.customFields?.[key] as T) ?? defaultValue;
}

// Watch for tab changes to load data
watch(activeTab, (tab) => {
    if (tab === 'comments' && comments.value.length === 0) fetchComments();
    if (tab === 'history' && versions.value.length === 0) fetchVersions();
    if (tab === 'workflow' && availableTransitions.value.length === 0) fetchTransitions();
});

// Load initial data when opened
watch(() => props.open, (open) => {
    if (open) {
        fetchComments();
        fetchVersions();
        fetchTransitions();
    }
});

// Reload when version changes
watch(() => props.currentVersionUuid, () => {
    if (props.open) {
        fetchComments();
        fetchTransitions();
    }
});

const commentTypeOptions = [
    { label: 'General', value: 'general', icon: 'i-lucide-message-circle' },
    { label: 'Revision', value: 'revision_request', icon: 'i-lucide-alert-circle' },
    { label: 'Approval', value: 'approval', icon: 'i-lucide-check-circle' },
];
</script>

<template>
    <USlideover
        :open="open"
        side="right"
        :ui="{ width: 'max-w-md' }"
        @update:open="emit('update:open', $event)"
    >
        <template #content>
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-default">
                    <h2 class="font-semibold text-highlighted">Editorial</h2>
                    <UButton
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="emit('update:open', false)"
                    />
                </div>

                <!-- Tabs -->
                <div class="border-b border-default">
                    <div class="flex">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            :class="[
                                'flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition-colors relative',
                                activeTab === tab.key
                                    ? 'text-primary'
                                    : 'text-muted hover:text-highlighted',
                            ]"
                            @click="activeTab = tab.key"
                        >
                            <UIcon :name="tab.icon" class="size-4" />
                            <span class="hidden sm:inline">{{ tab.label }}</span>
                            <!-- Unread indicator for comments -->
                            <span
                                v-if="tab.key === 'comments' && unresolvedCount > 0"
                                class="absolute top-1.5 right-1/4 size-2 bg-warning rounded-full"
                            />
                            <!-- Active indicator -->
                            <div
                                v-if="activeTab === tab.key"
                                class="absolute bottom-0 left-2 right-2 h-0.5 bg-primary rounded-full"
                            />
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Comments Tab -->
                    <div v-show="activeTab === 'comments'" class="p-4 space-y-4">
                        <!-- Comment Input (chat-style at top) -->
                        <div class="space-y-2">
                            <UTextarea
                                v-model="newComment"
                                placeholder="Write a comment..."
                                :rows="2"
                                :disabled="!currentVersionUuid"
                                class="w-full"
                            />
                            <div class="flex items-center gap-2">
                                <USelectMenu
                                    v-model="commentType"
                                    :items="commentTypeOptions"
                                    value-key="value"
                                    size="xs"
                                    class="w-28"
                                    :disabled="!currentVersionUuid"
                                />
                                <UButton
                                    size="xs"
                                    icon="i-lucide-send"
                                    :disabled="!newComment.trim() || !currentVersionUuid"
                                    :loading="submittingComment"
                                    class="ml-auto"
                                    @click="addComment"
                                >
                                    Send
                                </UButton>
                            </div>
                        </div>

                        <!-- Comments List (chat-style) -->
                        <div v-if="commentsLoading" class="flex justify-center py-8">
                            <UIcon name="i-lucide-loader-2" class="size-6 animate-spin text-muted" />
                        </div>

                        <div v-else-if="comments.length === 0" class="text-center py-8">
                            <UIcon name="i-lucide-message-square" class="size-10 text-muted mx-auto mb-2" />
                            <p class="text-sm text-muted">No comments yet</p>
                            <p class="text-xs text-dimmed mt-1">Start the conversation</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="comment in comments"
                                :key="comment.id"
                                :class="[
                                    'p-3 rounded-lg',
                                    comment.is_resolved ? 'bg-success/5 border border-success/20' : 'bg-elevated/50 border border-default',
                                ]"
                            >
                                <div class="flex items-start gap-2">
                                    <UAvatar :src="comment.user.avatar_url || undefined" :alt="comment.user.name" size="sm" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-medium">{{ comment.user.name }}</span>
                                            <UBadge :color="getCommentTypeColor(comment.type)" variant="subtle" size="xs">
                                                <UIcon :name="getCommentTypeIcon(comment.type)" class="size-2.5 mr-0.5" />
                                                {{ comment.type.replace('_', ' ') }}
                                            </UBadge>
                                            <span class="text-xs text-muted">{{ formatRelativeDate(comment.created_at) }}</span>
                                        </div>
                                        <p class="text-sm mt-1 whitespace-pre-wrap">{{ comment.content }}</p>
                                        <div v-if="comment.is_resolved" class="flex items-center gap-1 mt-2 text-xs text-success">
                                            <UIcon name="i-lucide-check-circle" class="size-3" />
                                            Resolved by {{ comment.resolved_by?.name }}
                                        </div>
                                    </div>
                                    <UButton
                                        v-if="!comment.is_resolved"
                                        color="success"
                                        variant="ghost"
                                        icon="i-lucide-check"
                                        size="xs"
                                        @click="resolveComment(comment)"
                                    />
                                    <UButton
                                        v-else
                                        color="neutral"
                                        variant="ghost"
                                        icon="i-lucide-rotate-ccw"
                                        size="xs"
                                        @click="unresolveComment(comment)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div v-show="activeTab === 'history'" class="p-4 space-y-3">
                        <div v-if="versionsLoading" class="flex justify-center py-8">
                            <UIcon name="i-lucide-loader-2" class="size-6 animate-spin text-muted" />
                        </div>

                        <div v-else-if="versions.length === 0" class="text-center py-8">
                            <UIcon name="i-lucide-history" class="size-10 text-muted mx-auto mb-2" />
                            <p class="text-sm text-muted">No version history</p>
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="(version, index) in versions"
                                :key="version.id"
                                class="relative"
                            >
                                <!-- Timeline connector -->
                                <div v-if="index < versions.length - 1" class="absolute left-3.5 top-10 bottom-0 w-px bg-default" />

                                <div
                                    :class="[
                                        'relative p-3 rounded-lg border transition-colors',
                                        version.uuid === currentVersionUuid
                                            ? 'bg-primary/5 border-primary/30'
                                            : 'bg-elevated/50 border-default',
                                    ]"
                                >
                                    <div class="flex items-start gap-2">
                                        <div
                                            :class="[
                                                'size-7 rounded-full flex items-center justify-center shrink-0 text-xs font-bold',
                                                version.uuid === currentVersionUuid ? 'bg-primary text-white'
                                                    : version.is_active ? 'bg-success text-white'
                                                    : 'bg-muted/20 text-muted',
                                            ]"
                                        >
                                            {{ version.version_number }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-sm font-medium">v{{ version.version_number }}</span>
                                                <UBadge v-if="version.uuid === currentVersionUuid" color="primary" variant="subtle" size="xs">Current</UBadge>
                                                <UBadge v-if="version.is_active" color="success" variant="subtle" size="xs">Live</UBadge>
                                                <UBadge :color="getStateColor(version.workflow_status)" variant="subtle" size="xs">
                                                    {{ getStateLabel(version.workflow_status) }}
                                                </UBadge>
                                            </div>
                                            <p class="text-xs text-muted mt-0.5">
                                                {{ version.created_by?.name }} &middot; {{ formatRelativeDate(version.created_at) }}
                                            </p>
                                            <p v-if="version.version_note" class="text-xs text-muted mt-1 italic">"{{ version.version_note }}"</p>
                                        </div>
                                        <UDropdownMenu
                                            v-if="getVersionDropdownItems(version).length > 0"
                                            :items="getVersionDropdownItems(version)"
                                        >
                                            <UButton icon="i-lucide-more-vertical" color="neutral" variant="ghost" size="xs" />
                                        </UDropdownMenu>
                                    </div>

                                    <!-- Expandable transitions -->
                                    <div v-if="expandedVersions.has(version.id) && version.transitions.length > 0" class="mt-3 pt-3 border-t border-default space-y-1">
                                        <div v-for="t in version.transitions" :key="t.id" class="flex items-center gap-2 text-xs">
                                            <UBadge v-if="t.from_status" :color="getStateColor(t.from_status)" variant="subtle" size="xs">{{ getStateLabel(t.from_status) }}</UBadge>
                                            <UIcon name="i-lucide-arrow-right" class="size-3 text-muted" />
                                            <UBadge :color="getStateColor(t.to_status)" variant="subtle" size="xs">{{ getStateLabel(t.to_status) }}</UBadge>
                                            <span class="text-muted ml-auto">{{ t.performed_by?.name }}</span>
                                        </div>
                                    </div>

                                    <UButton
                                        v-if="version.transitions.length > 0"
                                        variant="ghost"
                                        size="xs"
                                        color="neutral"
                                        class="mt-2"
                                        @click="toggleExpand(version.id)"
                                    >
                                        {{ expandedVersions.has(version.id) ? 'Hide' : 'Show' }} activity
                                    </UButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Tab -->
                    <div v-show="activeTab === 'workflow'" class="p-4 space-y-4">
                        <!-- Current Status -->
                        <div class="p-4 rounded-lg bg-elevated/50 border border-default">
                            <p class="text-xs text-muted mb-2">Current Status</p>
                            <div class="flex items-center gap-3">
                                <div :class="['size-10 rounded-full flex items-center justify-center', `bg-${getStateColor(workflowStatus)}/10`]">
                                    <UIcon :name="currentState.icon" :class="['size-5', `text-${getStateColor(workflowStatus)}`]" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ currentState.label }}</p>
                                    <p class="text-xs text-muted">{{ workflowConfig.name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Published Post Info -->
                        <div v-if="isPublished" class="p-4 rounded-lg bg-elevated border border-default">
                            <div class="flex items-start gap-3">
                                <UIcon name="i-lucide-globe" class="size-5 text-primary shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">This post is published</p>
                                    <p class="text-xs text-muted mt-1">
                                        Changes you save will update the live post directly.
                                    </p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <UButton
                                            v-if="availableTransitions.some(t => t.to === 'draft')"
                                            size="sm"
                                            color="error"
                                            variant="soft"
                                            icon="i-lucide-globe-lock"
                                            @click="openTransitionModal(availableTransitions.find(t => t.to === 'draft')!)"
                                        >
                                            Unpublish
                                        </UButton>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parked Version - Publish Action -->
                        <div v-else-if="isCurrentVersionApproved" class="p-4 rounded-lg bg-success/10 border border-success/20">
                            <div class="flex items-start gap-3">
                                <UIcon name="i-lucide-archive" class="size-5 text-success shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-success">Parked — Ready to Publish</p>
                                    <p class="text-xs text-muted mt-1">
                                        This version has been approved and parked for later publishing.
                                    </p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <UButton
                                            v-if="availableTransitions.some(t => t.to === 'published')"
                                            size="sm"
                                            color="success"
                                            icon="i-lucide-globe"
                                            @click="openTransitionModal(availableTransitions.find(t => t.to === 'published')!)"
                                        >
                                            Publish Now
                                        </UButton>
                                        <UButton
                                            v-else-if="transitionsLoading"
                                            size="sm"
                                            color="neutral"
                                            variant="soft"
                                            icon="i-lucide-loader-2"
                                            disabled
                                        >
                                            <UIcon name="i-lucide-loader-2" class="animate-spin mr-1" />
                                            Loading...
                                        </UButton>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Available Actions (for non-published posts) -->
                        <div v-else-if="availableTransitions.length > 0">
                            <p class="text-xs text-muted mb-2">Available Actions</p>
                            <div class="space-y-2">
                                <button
                                    v-for="t in availableTransitions"
                                    :key="`${t.from}-${t.to}`"
                                    class="w-full p-3 rounded-lg border border-default hover:border-primary/50 hover:bg-primary/5 transition-colors text-left flex items-center gap-3"
                                    @click="openTransitionModal(t)"
                                >
                                    <UIcon name="i-lucide-arrow-right" class="size-4 text-muted" />
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ t.label }}</p>
                                        <p class="text-xs text-muted">Move to {{ getStateLabel(t.to) }}</p>
                                    </div>
                                    <UBadge :color="getStateColor(t.to)" variant="subtle" size="sm">
                                        {{ getStateLabel(t.to) }}
                                    </UBadge>
                                </button>
                            </div>
                        </div>

                        <div v-else-if="!currentVersionUuid" class="text-center py-8">
                            <UIcon name="i-lucide-info" class="size-8 text-muted mx-auto mb-2" />
                            <p class="text-sm text-muted">Save to enable workflow</p>
                        </div>

                        <div v-else class="text-center py-8">
                            <UIcon name="i-lucide-check-circle" class="size-8 text-success mx-auto mb-2" />
                            <p class="text-sm text-muted">No actions available</p>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </USlideover>

    <!-- Transition Modal -->
    <UModal v-model:open="transitionModalOpen">
        <template #content>
            <UCard>
                <template #header>
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-git-branch" class="size-5 text-primary" />
                        <h3 class="font-semibold">{{ selectedTransition?.label }}</h3>
                    </div>
                </template>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-elevated/50">
                        <UBadge :color="getStateColor(selectedTransition?.from || '')" variant="soft">
                            {{ getStateLabel(selectedTransition?.from || '') }}
                        </UBadge>
                        <UIcon name="i-lucide-arrow-right" class="size-4 text-muted" />
                        <UBadge :color="getStateColor(selectedTransition?.to || '')" variant="soft">
                            {{ getStateLabel(selectedTransition?.to || '') }}
                        </UBadge>
                    </div>

                    <UFormField label="Comment (optional)">
                        <UTextarea v-model="transitionComment" placeholder="Add a note..." :rows="3" class="w-full" />
                    </UFormField>
                </div>

                <template #footer>
                    <div class="flex justify-end gap-2">
                        <UButton color="neutral" variant="ghost" @click="transitionModalOpen = false">Cancel</UButton>
                        <UButton color="primary" :loading="submittingTransition" @click="performTransition">
                            {{ selectedTransition?.label }}
                        </UButton>
                    </div>
                </template>
            </UCard>
        </template>
    </UModal>

</template>
