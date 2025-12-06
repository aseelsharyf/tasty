<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

interface User {
    id: number;
    name: string;
    avatar_url: string | null;
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
}

interface WorkflowState {
    key: string;
    label: string;
    color: string;
    icon: string;
}

const props = defineProps<{
    contentType: string;
    contentUuid: string;
    currentVersionUuid?: string | null;
    workflowStates: WorkflowState[];
}>();

const emit = defineEmits<{
    (e: 'revert', version: Version): void;
    (e: 'compare', versionA: Version, versionB: Version): void;
}>();

const loading = ref(false);
const versions = ref<Version[]>([]);
const selectedForCompare = ref<Version | null>(null);
const expandedVersions = ref<Set<number>>(new Set());
const reverting = ref<number | null>(null);

async function fetchHistory() {
    if (!props.contentUuid) return;

    loading.value = true;
    try {
        const response = await axios.get(`/cms/workflow/${props.contentType}/${props.contentUuid}/history`);
        versions.value = response.data.versions;
    } catch (error) {
        console.error('Failed to fetch version history:', error);
    } finally {
        loading.value = false;
    }
}

async function revertToVersion(version: Version) {
    if (!confirm(`Revert to version ${version.version_number}? This will create a new version with the content from version ${version.version_number}.`)) {
        return;
    }

    reverting.value = version.id;
    try {
        await axios.post(`/cms/workflow/versions/${version.uuid}/revert`);
        emit('revert', version);
        await fetchHistory();
    } catch (error: any) {
        console.error('Failed to revert:', error);
        alert(error.response?.data?.message || 'Failed to revert to this version');
    } finally {
        reverting.value = null;
    }
}

function toggleCompare(version: Version) {
    if (selectedForCompare.value?.id === version.id) {
        selectedForCompare.value = null;
    } else if (selectedForCompare.value) {
        emit('compare', selectedForCompare.value, version);
        selectedForCompare.value = null;
    } else {
        selectedForCompare.value = version;
    }
}

// Get dropdown items for a version
function getVersionDropdownItems(version: Version) {
    const items = [];

    items.push({
        label: selectedForCompare.value ? 'Compare with this' : 'Compare',
        icon: 'i-lucide-git-compare',
        onSelect: () => toggleCompare(version),
    });

    if (version.uuid !== props.currentVersionUuid) {
        items.push({
            label: 'Revert to this version',
            icon: 'i-lucide-rotate-ccw',
            disabled: reverting.value !== null,
            onSelect: () => revertToVersion(version),
        });
    }

    return items;
}

function toggleExpand(versionId: number) {
    if (expandedVersions.value.has(versionId)) {
        expandedVersions.value.delete(versionId);
    } else {
        expandedVersions.value.add(versionId);
    }
}

function getStateConfig(key: string): WorkflowState {
    return props.workflowStates.find(s => s.key === key) || {
        key,
        label: key,
        color: 'neutral',
        icon: 'i-lucide-circle',
    };
}

function getStateColor(key: string): string {
    const state = getStateConfig(key);
    const colorMap: Record<string, string> = {
        neutral: 'neutral',
        warning: 'warning',
        info: 'info',
        success: 'success',
        error: 'error',
        primary: 'primary',
    };
    return colorMap[state.color] || 'neutral';
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function formatRelativeDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 7) return formatDate(dateString);
    if (days > 0) return `${days}d ago`;
    if (hours > 0) return `${hours}h ago`;
    if (minutes > 0) return `${minutes}m ago`;
    return 'just now';
}

watch(() => props.contentUuid, () => {
    if (props.contentUuid) {
        fetchHistory();
    }
}, { immediate: true });
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <UIcon name="i-lucide-history" class="size-4 text-muted" />
            <span class="text-xs font-medium text-muted uppercase tracking-wider">Version History</span>
            <UBadge v-if="versions.length > 0" color="neutral" variant="subtle" size="xs">
                {{ versions.length }}
            </UBadge>
        </div>

        <!-- Compare mode indicator -->
        <div v-if="selectedForCompare" class="p-2 rounded-lg bg-primary/10 border border-primary/20">
            <div class="flex items-center justify-between">
                <span class="text-xs text-primary">
                    <UIcon name="i-lucide-git-compare" class="size-3 mr-1 inline" />
                    Select another version to compare with v{{ selectedForCompare.version_number }}
                </span>
                <UButton
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    @click="selectedForCompare = null"
                >
                    Cancel
                </UButton>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="flex justify-center py-4">
            <UIcon name="i-lucide-loader-2" class="size-5 animate-spin text-muted" />
        </div>

        <!-- No content yet -->
        <div v-else-if="!contentUuid" class="text-xs text-muted text-center py-4">
            <UIcon name="i-lucide-info" class="size-4 mx-auto mb-1 opacity-50" />
            <p>Save to create first version</p>
        </div>

        <!-- Version Timeline -->
        <div v-else-if="versions.length > 0" class="space-y-2 max-h-80 overflow-y-auto">
            <div
                v-for="(version, index) in versions"
                :key="version.id"
                class="relative"
            >
                <!-- Timeline connector -->
                <div
                    v-if="index < versions.length - 1"
                    class="absolute left-3.5 top-8 bottom-0 w-px bg-default"
                />

                <div
                    :class="[
                        'relative p-3 rounded-lg border transition-colors',
                        version.uuid === currentVersionUuid
                            ? 'bg-primary/5 border-primary/30'
                            : 'bg-elevated/50 border-default hover:border-muted',
                        selectedForCompare?.id === version.id && 'ring-2 ring-primary',
                    ]"
                >
                    <!-- Version header -->
                    <div class="flex items-start gap-2">
                        <!-- Timeline dot -->
                        <div
                            :class="[
                                'size-7 rounded-full flex items-center justify-center shrink-0 text-xs font-semibold',
                                version.uuid === currentVersionUuid
                                    ? 'bg-primary text-white'
                                    : version.is_active
                                        ? 'bg-success text-white'
                                        : 'bg-elevated border border-default text-muted',
                            ]"
                        >
                            {{ version.version_number }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium">
                                    Version {{ version.version_number }}
                                </span>

                                <UBadge
                                    v-if="version.uuid === currentVersionUuid"
                                    color="primary"
                                    variant="subtle"
                                    size="xs"
                                >
                                    Current
                                </UBadge>

                                <UBadge
                                    v-if="version.is_active"
                                    color="success"
                                    variant="subtle"
                                    size="xs"
                                >
                                    <UIcon name="i-lucide-globe" class="size-2.5 mr-0.5" />
                                    Published
                                </UBadge>

                                <UBadge
                                    :color="getStateColor(version.workflow_status)"
                                    variant="subtle"
                                    size="xs"
                                >
                                    {{ getStateConfig(version.workflow_status).label }}
                                </UBadge>
                            </div>

                            <div class="flex items-center gap-2 mt-1 text-xs text-muted">
                                <span v-if="version.created_by">
                                    {{ version.created_by.name }}
                                </span>
                                <span>&middot;</span>
                                <span :title="formatDate(version.created_at)">
                                    {{ formatRelativeDate(version.created_at) }}
                                </span>
                            </div>

                            <p v-if="version.version_note" class="text-xs text-muted mt-1 italic">
                                "{{ version.version_note }}"
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1">
                            <UButton
                                v-if="version.transitions.length > 0"
                                color="neutral"
                                variant="ghost"
                                :icon="expandedVersions.has(version.id) ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'"
                                size="xs"
                                @click="toggleExpand(version.id)"
                            />

                            <UDropdownMenu :items="getVersionDropdownItems(version)">
                                <UButton
                                    color="neutral"
                                    variant="ghost"
                                    icon="i-lucide-more-vertical"
                                    size="xs"
                                />
                            </UDropdownMenu>
                        </div>
                    </div>

                    <!-- Transitions (expandable) -->
                    <div
                        v-if="expandedVersions.has(version.id) && version.transitions.length > 0"
                        class="mt-3 pt-3 border-t border-default space-y-2"
                    >
                        <p class="text-xs font-medium text-muted">Workflow Activity</p>
                        <div
                            v-for="transition in version.transitions"
                            :key="transition.id"
                            class="flex items-start gap-2 text-xs"
                        >
                            <UIcon name="i-lucide-arrow-right" class="size-3 mt-0.5 text-muted" />
                            <div class="flex-1">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <UBadge
                                        v-if="transition.from_status"
                                        :color="getStateColor(transition.from_status)"
                                        variant="subtle"
                                        size="xs"
                                    >
                                        {{ getStateConfig(transition.from_status).label }}
                                    </UBadge>
                                    <UIcon name="i-lucide-arrow-right" class="size-3 text-muted" />
                                    <UBadge
                                        :color="getStateColor(transition.to_status)"
                                        variant="subtle"
                                        size="xs"
                                    >
                                        {{ getStateConfig(transition.to_status).label }}
                                    </UBadge>
                                    <span class="text-muted ml-1">
                                        by {{ transition.performed_by?.name || 'Unknown' }}
                                    </span>
                                </div>
                                <p v-if="transition.comment" class="text-muted mt-0.5 italic">
                                    "{{ transition.comment }}"
                                </p>
                            </div>
                            <span class="text-muted shrink-0">
                                {{ formatRelativeDate(transition.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-xs text-muted text-center py-4">
            <UIcon name="i-lucide-history" class="size-5 mx-auto mb-1 opacity-30" />
            <p>No version history</p>
        </div>
    </div>
</template>
