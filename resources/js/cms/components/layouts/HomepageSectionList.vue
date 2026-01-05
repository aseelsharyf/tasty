<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import draggable from 'vuedraggable';
import DataSourceConfig from './DataSourceConfig.vue';
import PostPickerModal from './PostPickerModal.vue';
import SectionPreview from './SectionPreview.vue';
import MediaPickerModal from '../MediaPickerModal.vue';
import type { HomepageSection, SectionTypeDefinition, HomepageSectionSlot, PostSearchResult } from '../../types';

const props = defineProps<{
    modelValue: HomepageSection[];
    sectionTypes: Record<string, SectionTypeDefinition>;
}>();

const emit = defineEmits<{
    'update:modelValue': [sections: HomepageSection[]];
    remove: [sectionId: string];
    duplicate: [sectionId: string];
    toggle: [sectionId: string];
    reorder: [sections: HomepageSection[]];
    'update:section': [section: HomepageSection];
}>();

const sections = computed({
    get: () => props.modelValue,
    set: (value) => emit('reorder', value),
});

// Track active tab per section
const activeTabs = ref<Record<string, string>>({});

// Expanded slot state per section
const expandedSlots = ref<Record<string, number | null>>({});

// Post picker state
const showPostPicker = ref(false);
const editingSlotIndex = ref<number | null>(null);
const editingSectionId = ref<string | null>(null);
const editingSectionType = ref<string | null>(null);
const postCache = ref<Record<number, PostSearchResult>>({});

// Media picker state
const showMediaPicker = ref(false);
const mediaPickerSectionId = ref<string | null>(null);
const mediaPickerSlotIndex = ref<number | null>(null);
const mediaPickerFieldKey = ref<string | null>(null);
const mediaPickerConfigKey = ref<string | null>(null);

// Dynamic preview posts per section (for dynamic slots)
const dynamicPreviewPosts = ref<Record<string, PostSearchResult[]>>({});

// Compute all assigned post IDs across all sections (excluding the currently editing slot)
const excludedPostIds = computed(() => {
    const ids: number[] = [];
    for (const section of props.modelValue) {
        for (const slot of section.slots) {
            // Skip the slot currently being edited
            if (section.id === editingSectionId.value && slot.index === editingSlotIndex.value) {
                continue;
            }
            if (slot.mode === 'manual' && slot.postId) {
                ids.push(slot.postId);
            }
        }
    }
    return ids;
});

// Fetch posts data for all assigned postIds on mount
async function fetchAssignedPosts() {
    const postIds = new Set<number>();

    // Collect all postIds from all sections
    for (const section of props.modelValue) {
        for (const slot of section.slots) {
            if (slot.postId && !postCache.value[slot.postId]) {
                postIds.add(slot.postId);
            }
        }
    }

    if (postIds.size === 0) return;

    try {
        // Fetch posts data from API
        const response = await fetch(`/cms/layouts/homepage/posts-batch?ids=${Array.from(postIds).join(',')}`);
        if (response.ok) {
            const posts: PostSearchResult[] = await response.json();
            for (const post of posts) {
                postCache.value[post.id] = post;
            }
        }
    } catch (error) {
        console.error('Failed to fetch assigned posts:', error);
    }
}

// Fetch preview posts for dynamic slots based on data source
async function fetchDynamicPreview(section: HomepageSection) {
    const sectionType = getSectionType(section.type);
    if (!sectionType?.supportsDynamic) return;

    // Count how many dynamic slots need posts
    const dynamicSlotCount = section.slots.filter(s => s.mode === 'dynamic').length;
    if (dynamicSlotCount === 0) return;

    try {
        const params = new URLSearchParams();
        params.set('limit', String(dynamicSlotCount));

        if (section.dataSource.action === 'byCategory' && section.dataSource.params?.slug) {
            params.set('category', section.dataSource.params.slug as string);
        } else if (section.dataSource.action === 'byTag' && section.dataSource.params?.slug) {
            params.set('tag', section.dataSource.params.slug as string);
        }

        const response = await fetch(`/cms/layouts/homepage/search-posts?${params.toString()}`);
        if (response.ok) {
            const data = await response.json();
            dynamicPreviewPosts.value[section.id] = data.posts || [];
        }
    } catch (error) {
        console.error('Failed to fetch dynamic preview:', error);
    }
}

// Fetch all dynamic previews
async function fetchAllDynamicPreviews() {
    for (const section of props.modelValue) {
        await fetchDynamicPreview(section);
    }
}

onMounted(() => {
    fetchAssignedPosts();
    fetchAllDynamicPreviews();
});

// Re-fetch when sections change (e.g., after adding a section)
watch(() => props.modelValue, () => {
    fetchAssignedPosts();
}, { deep: true });

// Get the effective post for a slot (manual post or dynamic preview)
function getEffectiveSlotPost(section: HomepageSection, slotIndex: number): PostSearchResult | null {
    const slot = section.slots[slotIndex];
    if (!slot) return null;

    // Manual or static with postId
    if (slot.postId && postCache.value[slot.postId]) {
        return postCache.value[slot.postId];
    }

    // Dynamic slot - get from preview
    if (slot.mode === 'dynamic') {
        const dynamicPosts = dynamicPreviewPosts.value[section.id] || [];
        // Find the dynamic slot index (position among dynamic slots only)
        let dynamicIndex = 0;
        for (let i = 0; i < slotIndex; i++) {
            if (section.slots[i].mode === 'dynamic') {
                dynamicIndex++;
            }
        }
        return dynamicPosts[dynamicIndex] || null;
    }

    return null;
}

// Get combined post cache including dynamic previews for a section
function getSectionPostCache(section: HomepageSection): Record<number, PostSearchResult> {
    const cache: Record<number, PostSearchResult> = { ...postCache.value };

    // Add dynamic preview posts to cache with negative IDs to avoid conflicts
    const dynamicPosts = dynamicPreviewPosts.value[section.id] || [];
    let dynamicIndex = 0;
    for (let i = 0; i < section.slots.length; i++) {
        const slot = section.slots[i];
        if (slot.mode === 'dynamic' && dynamicPosts[dynamicIndex]) {
            // Use the actual post ID from dynamic preview
            const post = dynamicPosts[dynamicIndex];
            cache[post.id] = post;
            dynamicIndex++;
        }
    }

    return cache;
}

// Get slots with dynamic preview posts injected
function getSlotsWithDynamicPreview(section: HomepageSection): HomepageSectionSlot[] {
    const dynamicPosts = dynamicPreviewPosts.value[section.id] || [];
    let dynamicIndex = 0;

    return section.slots.map(slot => {
        if (slot.mode === 'dynamic' && dynamicPosts[dynamicIndex]) {
            const post = dynamicPosts[dynamicIndex];
            dynamicIndex++;
            // Return slot with injected postId for preview purposes
            return { ...slot, postId: post.id };
        }
        return slot;
    });
}

function getActiveTab(sectionId: string, sectionType: SectionTypeDefinition): string {
    if (activeTabs.value[sectionId]) {
        return activeTabs.value[sectionId];
    }
    // Default to slots tab first, then others
    if (sectionType.slotCount > 0) return 'slots';
    if (sectionType.supportsDynamic) return 'data-source';
    if (Object.keys(sectionType.configSchema).length > 0) return 'settings';
    return 'slots';
}

function setActiveTab(sectionId: string, tab: string) {
    activeTabs.value[sectionId] = tab;
}

function getExpandedSlot(sectionId: string): number | null {
    return expandedSlots.value[sectionId] ?? null;
}

function toggleSlotExpanded(sectionId: string, slotIndex: number) {
    if (expandedSlots.value[sectionId] === slotIndex) {
        expandedSlots.value[sectionId] = null;
    } else {
        expandedSlots.value[sectionId] = slotIndex;
    }
}

function getSectionTypeName(type: string): string {
    return props.sectionTypes[type]?.name || type;
}

function getSectionTypeIcon(type: string): string {
    return props.sectionTypes[type]?.icon || 'i-lucide-layout-template';
}

function getSectionType(type: string): SectionTypeDefinition | null {
    return props.sectionTypes[type] || null;
}

function getSectionDescription(section: HomepageSection): string {
    const type = props.sectionTypes[section.type];
    if (!type) return '';

    const parts: string[] = [];

    // Show data source action
    if (type.supportedActions.length > 0 && section.dataSource.action) {
        const actionLabels: Record<string, string> = {
            recent: 'Recent posts',
            trending: 'Trending posts',
            byTag: `Tag: ${section.dataSource.params?.slug || 'any'}`,
            byCategory: `Category: ${section.dataSource.params?.slug || 'any'}`,
        };
        parts.push(actionLabels[section.dataSource.action] || section.dataSource.action);
    }

    // Show slot count
    if (type.slotCount > 0) {
        const manualSlots = section.slots.filter(s => s.mode === 'manual').length;
        const staticSlots = section.slots.filter(s => s.mode === 'static').length;
        if (manualSlots > 0) {
            parts.push(`${manualSlots} manual`);
        }
        if (staticSlots > 0) {
            parts.push(`${staticSlots} static`);
        }
    }

    return parts.join(' · ');
}

function getSlotLabel(sectionType: SectionTypeDefinition, index: number): string {
    return sectionType.slotLabels?.[index] || `Slot ${index + 1}`;
}

function updateSection(section: HomepageSection, updates: Partial<HomepageSection>) {
    const index = sections.value.findIndex(s => s.id === section.id);
    if (index > -1) {
        // Deep clone the section to ensure complete isolation
        const clonedSection: HomepageSection = JSON.parse(JSON.stringify(section));
        const updatedSection = { ...clonedSection, ...updates };

        // Deep clone all sections to ensure no shared references
        const newSections = sections.value.map((s, idx) =>
            idx === index ? updatedSection : JSON.parse(JSON.stringify(s))
        );
        emit('update:modelValue', newSections);
    }
}

function updateSectionConfig(section: HomepageSection, key: string, value: unknown) {
    const newConfig = { ...section.config, [key]: value };
    updateSection(section, { config: newConfig });
}

function updateSlot(section: HomepageSection, slotIndex: number, updates: Partial<HomepageSectionSlot>) {
    // Deep clone all slots to ensure complete isolation
    const newSlots = section.slots.map((slot, idx) => {
        const clonedSlot = JSON.parse(JSON.stringify(slot));
        return idx === slotIndex ? { ...clonedSlot, ...updates } : clonedSlot;
    });
    updateSection(section, { slots: newSlots });
}

function updateSlotContent(section: HomepageSection, slotIndex: number, key: string, value: string) {
    const slot = section.slots[slotIndex];
    const newContent = { ...(slot.content || {}), [key]: value };
    updateSlot(section, slotIndex, { content: newContent });
}

function cycleSlotMode(section: HomepageSection, slotIndex: number) {
    const slot = section.slots[slotIndex];
    const sectionType = getSectionType(section.type);

    let nextMode: 'dynamic' | 'manual' | 'static';

    if (sectionType?.supportsDynamic) {
        // Cycle: dynamic -> manual -> static -> dynamic
        if (slot.mode === 'dynamic') nextMode = 'manual';
        else if (slot.mode === 'manual') nextMode = 'static';
        else nextMode = 'dynamic';
    } else {
        // Only static and manual available
        nextMode = slot.mode === 'static' ? 'manual' : 'static';
    }

    updateSlot(section, slotIndex, {
        mode: nextMode,
        postId: null,
        content: nextMode === 'static' ? (slot.content || {}) : undefined
    });
}

function openPostPicker(sectionId: string, slotIndex: number, sectionType: string) {
    editingSectionId.value = sectionId;
    editingSlotIndex.value = slotIndex;
    editingSectionType.value = sectionType;
    showPostPicker.value = true;
}

function selectPost(post: PostSearchResult) {
    if (editingSectionId.value === null || editingSlotIndex.value === null) return;

    const section = sections.value.find(s => s.id === editingSectionId.value);
    if (!section) return;

    postCache.value[post.id] = post;
    updateSlot(section, editingSlotIndex.value, { mode: 'manual', postId: post.id });

    showPostPicker.value = false;
    editingSectionId.value = null;
    editingSlotIndex.value = null;
    editingSectionType.value = null;
}

function clearSlotPost(section: HomepageSection, slotIndex: number) {
    updateSlot(section, slotIndex, { postId: null });
}

function canAddSlot(section: HomepageSection): boolean {
    const sectionType = getSectionType(section.type);
    if (!sectionType) return false;
    // maxSlots of 0 means unlimited
    if (sectionType.maxSlots === 0) return true;
    return section.slots.length < sectionType.maxSlots;
}

function canRemoveSlot(section: HomepageSection): boolean {
    const sectionType = getSectionType(section.type);
    if (!sectionType) return false;
    return section.slots.length > sectionType.minSlots;
}

function addSlot(section: HomepageSection) {
    const sectionType = getSectionType(section.type);
    if (!sectionType || !canAddSlot(section)) return;

    const slotSchema = sectionType.slotSchema;
    const defaultContent: Record<string, string> = {};
    for (const key in slotSchema) {
        defaultContent[key] = '';
    }

    const newSlot: HomepageSectionSlot = {
        index: section.slots.length,
        mode: sectionType.supportsDynamic ? 'dynamic' : 'static',
        postId: null,
        content: defaultContent,
    };

    const newSlots = [...section.slots, newSlot];
    updateSection(section, { slots: newSlots });
}

function removeSlot(section: HomepageSection, slotIndex: number) {
    if (!canRemoveSlot(section)) return;

    const newSlots = section.slots
        .filter((_, idx) => idx !== slotIndex)
        .map((slot, idx) => ({ ...slot, index: idx }));

    // Reset expanded slot if we removed it
    const currentExpanded = expandedSlots.value[section.id];
    if (currentExpanded === slotIndex) {
        expandedSlots.value[section.id] = null;
    } else if (currentExpanded !== null && currentExpanded > slotIndex) {
        expandedSlots.value[section.id] = currentExpanded - 1;
    }

    updateSection(section, { slots: newSlots });
}

function getColorOptions() {
    return [
        { label: 'Yellow', value: 'yellow' },
        { label: 'White', value: 'white' },
        { label: 'Blue Black', value: 'blue-black' },
        { label: 'Gray', value: '#F3F4F6' },
        { label: 'Transparent', value: 'transparent' },
    ];
}

function getModeLabel(mode: string): string {
    const labels: Record<string, string> = {
        dynamic: 'Dynamic',
        manual: 'Manual',
        static: 'Static',
    };
    return labels[mode] || mode;
}

function getModeColor(mode: string): 'primary' | 'info' | 'success' | 'neutral' {
    const colors: Record<string, 'primary' | 'info' | 'success' | 'neutral'> = {
        dynamic: 'info',
        manual: 'primary',
        static: 'success',
    };
    return colors[mode] || 'neutral';
}

function getTabs(sectionType: SectionTypeDefinition) {
    const tabs: Array<{ label: string; slot: string; icon: string }> = [];

    // Slots tab first (default)
    if (sectionType.slotCount > 0) {
        tabs.push({ label: 'Slots', slot: 'slots', icon: 'i-lucide-layout-grid' });
    }
    if (sectionType.supportsDynamic) {
        tabs.push({ label: 'Data Source', slot: 'data-source', icon: 'i-lucide-database' });
    }
    if (Object.keys(sectionType.configSchema).length > 0) {
        tabs.push({ label: 'Settings', slot: 'settings', icon: 'i-lucide-settings-2' });
    }

    return tabs;
}

// Media picker functions
function openMediaPicker(sectionId: string, slotIndex: number | null, fieldKey: string | null, configKey: string | null) {
    mediaPickerSectionId.value = sectionId;
    mediaPickerSlotIndex.value = slotIndex;
    mediaPickerFieldKey.value = fieldKey;
    mediaPickerConfigKey.value = configKey;
    showMediaPicker.value = true;
}

function handleMediaSelect(items: any[]) {
    if (items.length === 0) return;

    const mediaItem = items[0];
    const imageUrl = mediaItem.url || mediaItem.thumbnail_url || '';

    if (mediaPickerSectionId.value === null) return;

    const section = sections.value.find(s => s.id === mediaPickerSectionId.value);
    if (!section) return;

    // Config field (e.g., introImage)
    if (mediaPickerConfigKey.value !== null) {
        updateSectionConfig(section, mediaPickerConfigKey.value, imageUrl);
    }
    // Slot content field (e.g., image in static mode)
    else if (mediaPickerSlotIndex.value !== null && mediaPickerFieldKey.value !== null) {
        updateSlotContent(section, mediaPickerSlotIndex.value, mediaPickerFieldKey.value, imageUrl);
    }

    // Reset state
    showMediaPicker.value = false;
    mediaPickerSectionId.value = null;
    mediaPickerSlotIndex.value = null;
    mediaPickerFieldKey.value = null;
    mediaPickerConfigKey.value = null;
}

// Handle slot click from preview - open post picker directly
function handlePreviewSlotClick(section: HomepageSection, slotIndex: number) {
    const slot = section.slots[slotIndex];
    if (!slot) return;

    // If slot is in manual mode or we want to switch to manual, open post picker
    if (slot.mode === 'manual' || slot.mode === 'dynamic') {
        // Switch to manual mode and open picker
        if (slot.mode === 'dynamic') {
            updateSlot(section, slotIndex, { mode: 'manual', postId: null });
        }
        openPostPicker(section.id, slotIndex, section.type);
    } else if (slot.mode === 'static') {
        // For static, expand the slot in the accordion
        setActiveTab(section.id, 'slots');
        toggleSlotExpanded(section.id, slotIndex);
    }
}
</script>

<template>
    <div class="space-y-4">
        <draggable
            v-model="sections"
            item-key="id"
            handle=".drag-handle"
            ghost-class="opacity-50"
            animation="150"
            class="space-y-4"
        >
            <template #item="{ element: section }">
                <div
                    :class="[
                        'group relative border rounded-xl overflow-hidden transition-all',
                        section.enabled ? 'border-default bg-default' : 'border-dashed border-muted bg-muted/30',
                    ]"
                >
                    <!-- Section Header -->
                    <div class="flex items-center gap-3 p-4 border-b border-default">
                        <!-- Drag Handle -->
                        <div class="drag-handle cursor-grab active:cursor-grabbing p-1 -m-1 text-muted hover:text-highlighted">
                            <UIcon name="i-lucide-grip-vertical" class="size-5" />
                        </div>

                        <!-- Section Icon -->
                        <div
                            :class="[
                                'flex items-center justify-center size-10 rounded-lg shrink-0',
                                section.enabled ? 'bg-primary/10' : 'bg-muted/50',
                            ]"
                        >
                            <UIcon
                                :name="getSectionTypeIcon(section.type)"
                                :class="[
                                    'size-5',
                                    section.enabled ? 'text-primary' : 'text-muted',
                                ]"
                            />
                        </div>

                        <!-- Section Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 :class="[
                                    'font-medium truncate',
                                    section.enabled ? 'text-highlighted' : 'text-muted',
                                ]">
                                    {{ getSectionTypeName(section.type) }}
                                </h3>
                                <UBadge
                                    v-if="!section.enabled"
                                    color="neutral"
                                    variant="subtle"
                                    size="xs"
                                >
                                    Hidden
                                </UBadge>
                            </div>
                            <p v-if="getSectionDescription(section)" class="text-xs text-muted truncate mt-0.5">
                                {{ getSectionDescription(section) }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1">
                            <!-- Toggle Visibility -->
                            <UButton
                                :icon="section.enabled ? 'i-lucide-eye' : 'i-lucide-eye-off'"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                @click="emit('toggle', section.id)"
                            />

                            <!-- More Actions -->
                            <UDropdownMenu
                                :items="[
                                    [
                                        {
                                            label: 'Duplicate',
                                            icon: 'i-lucide-copy',
                                            onSelect: () => emit('duplicate', section.id),
                                        },
                                    ],
                                    [
                                        {
                                            label: 'Delete',
                                            icon: 'i-lucide-trash',
                                            color: 'error' as const,
                                            onSelect: () => emit('remove', section.id),
                                        },
                                    ],
                                ]"
                            >
                                <UButton
                                    icon="i-lucide-more-vertical"
                                    color="neutral"
                                    variant="ghost"
                                    size="sm"
                                />
                            </UDropdownMenu>
                        </div>
                    </div>

                    <!-- Section Body (Always Visible) - 2 Row Layout -->
                    <div class="p-4 space-y-4">
                        <!-- Row 1: Interactive Preview -->
                        <div v-if="getSectionType(section.type)?.slotCount > 0" class="max-w-md">
                            <p class="text-xs text-muted mb-2">Click on a slot to assign a post</p>
                            <SectionPreview
                                :schema="getSectionType(section.type)!.previewSchema"
                                :section-type="section.type"
                                :slots="getSlotsWithDynamicPreview(section)"
                                :config="section.config"
                                :post-cache="getSectionPostCache(section)"
                                :interactive="true"
                                @slot-click="(idx) => handlePreviewSlotClick(section, idx)"
                            />
                        </div>

                        <!-- Row 2: Tabs -->
                        <div class="w-full">
                            <UTabs
                                v-if="getTabs(getSectionType(section.type)!).length > 0"
                                :model-value="getActiveTab(section.id, getSectionType(section.type)!)"
                                :items="getTabs(getSectionType(section.type)!)"
                                class="w-full"
                                @update:model-value="(v) => setActiveTab(section.id, v as string)"
                            >
                                <!-- Slots Tab -->
                                <template #slots>
                                    <div class="pt-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm text-muted">
                                                {{ section.slots.length }} slot{{ section.slots.length !== 1 ? 's' : '' }}
                                            </span>
                                            <UButton
                                                v-if="canAddSlot(section)"
                                                icon="i-lucide-plus"
                                                color="primary"
                                                variant="soft"
                                                size="xs"
                                                @click="addSlot(section)"
                                            >
                                                Add Slot
                                            </UButton>
                                        </div>

                                        <div class="space-y-2">
                                            <div
                                                v-for="(slot, slotIndex) in section.slots"
                                                :key="slotIndex"
                                                class="border border-default rounded-lg overflow-hidden"
                                            >
                                                <!-- Slot Header -->
                                                <div class="flex items-center gap-2 p-3 hover:bg-muted/30 transition-colors">
                                                    <button
                                                        type="button"
                                                        class="flex-1 flex items-center justify-between"
                                                        @click="toggleSlotExpanded(section.id, slotIndex)"
                                                    >
                                                        <div class="flex items-center gap-3">
                                                            <!-- Slot thumbnail -->
                                                            <div class="size-8 rounded bg-muted/30 overflow-hidden shrink-0">
                                                                <img
                                                                    v-if="slot.postId && postCache[slot.postId]?.image"
                                                                    :src="postCache[slot.postId]?.image"
                                                                    :alt="postCache[slot.postId]?.title"
                                                                    class="w-full h-full object-cover"
                                                                />
                                                                <img
                                                                    v-else-if="slot.mode === 'static' && slot.content?.image"
                                                                    :src="slot.content.image"
                                                                    alt=""
                                                                    class="w-full h-full object-cover"
                                                                />
                                                                <div v-else class="w-full h-full flex items-center justify-center">
                                                                    <UIcon name="i-lucide-image" class="size-4 text-muted" />
                                                                </div>
                                                            </div>
                                                            <span class="font-medium text-sm text-highlighted">
                                                                {{ getSlotLabel(getSectionType(section.type)!, slotIndex) }}
                                                            </span>
                                                            <UBadge
                                                                :color="getModeColor(slot.mode)"
                                                                variant="subtle"
                                                                size="xs"
                                                            >
                                                                {{ getModeLabel(slot.mode) }}
                                                            </UBadge>
                                                        </div>
                                                        <UIcon
                                                            :name="getExpandedSlot(section.id) === slotIndex ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'"
                                                            class="size-4 text-muted"
                                                        />
                                                    </button>
                                                    <UButton
                                                        v-if="canRemoveSlot(section)"
                                                        icon="i-lucide-trash-2"
                                                        color="error"
                                                        variant="ghost"
                                                        size="xs"
                                                        @click.stop="removeSlot(section, slotIndex)"
                                                    />
                                                </div>

                                                <!-- Slot Content -->
                                                <Transition
                                                    enter-active-class="transition-all duration-150 ease-out"
                                                    enter-from-class="opacity-0 max-h-0"
                                                    enter-to-class="opacity-100 max-h-[1000px]"
                                                    leave-active-class="transition-all duration-100 ease-in"
                                                    leave-from-class="opacity-100 max-h-[1000px]"
                                                    leave-to-class="opacity-0 max-h-0"
                                                >
                                                    <div v-if="getExpandedSlot(section.id) === slotIndex" class="border-t border-default p-3 space-y-4 overflow-hidden">
                                                        <!-- Mode Selector -->
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-sm text-muted">Mode:</span>
                                                            <UButton
                                                                :color="getModeColor(slot.mode)"
                                                                variant="soft"
                                                                size="xs"
                                                                @click="cycleSlotMode(section, slotIndex)"
                                                            >
                                                                {{ getModeLabel(slot.mode) }}
                                                                <UIcon name="i-lucide-repeat" class="size-3 ml-1" />
                                                            </UButton>
                                                            <span class="text-xs text-muted">
                                                                <template v-if="slot.mode === 'dynamic'">Auto-filled from data source</template>
                                                                <template v-else-if="slot.mode === 'manual'">Select a specific post</template>
                                                                <template v-else>Enter custom content</template>
                                                            </span>
                                                        </div>

                                                        <!-- Dynamic Mode -->
                                                        <div v-if="slot.mode === 'dynamic'" class="p-3 bg-info/10 border border-info/20 rounded-lg">
                                                            <div class="flex items-center gap-2 text-sm text-info">
                                                                <UIcon name="i-lucide-zap" class="size-4" />
                                                                <span>This slot will be automatically filled from the data source.</span>
                                                            </div>
                                                        </div>

                                                        <!-- Manual Mode -->
                                                        <div v-else-if="slot.mode === 'manual'">
                                                            <!-- No post selected -->
                                                            <div v-if="!slot.postId" class="flex items-center gap-3">
                                                                <button
                                                                    type="button"
                                                                    class="flex-1 p-3 border-2 border-dashed border-default rounded-lg text-muted hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-2"
                                                                    @click="openPostPicker(section.id, slotIndex, section.type)"
                                                                >
                                                                    <UIcon name="i-lucide-plus" class="size-4" />
                                                                    <span class="text-sm">Select Post</span>
                                                                </button>
                                                            </div>

                                                            <!-- Post selected -->
                                                            <div v-else class="flex items-center gap-3 p-3 bg-muted/30 rounded-lg">
                                                                <img
                                                                    v-if="postCache[slot.postId]?.image"
                                                                    :src="postCache[slot.postId]?.image"
                                                                    :alt="postCache[slot.postId]?.title"
                                                                    class="size-12 rounded-lg object-cover shrink-0"
                                                                />
                                                                <div v-else class="size-12 rounded-lg bg-muted/50 flex items-center justify-center shrink-0">
                                                                    <UIcon name="i-lucide-image" class="size-5 text-muted" />
                                                                </div>
                                                                <div class="flex-1 min-w-0">
                                                                    <p class="font-medium text-highlighted truncate">
                                                                        {{ postCache[slot.postId]?.title || `Post #${slot.postId}` }}
                                                                    </p>
                                                                    <p v-if="postCache[slot.postId]?.category" class="text-xs text-muted">
                                                                        {{ postCache[slot.postId]?.category }}
                                                                    </p>
                                                                </div>
                                                                <div class="flex items-center gap-1">
                                                                    <UButton
                                                                        icon="i-lucide-pencil"
                                                                        color="neutral"
                                                                        variant="ghost"
                                                                        size="xs"
                                                                        @click="openPostPicker(section.id, slotIndex, section.type)"
                                                                    />
                                                                    <UButton
                                                                        icon="i-lucide-x"
                                                                        color="neutral"
                                                                        variant="ghost"
                                                                        size="xs"
                                                                        @click="clearSlotPost(section, slotIndex)"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Static Mode - Content Fields -->
                                                        <div v-else-if="slot.mode === 'static'" class="space-y-4">
                                                            <template v-for="(field, fieldKey) in getSectionType(section.type)!.slotSchema" :key="fieldKey">
                                                                <!-- Textarea Field -->
                                                                <UFormField
                                                                    v-if="field.type === 'textarea'"
                                                                    :label="field.label"
                                                                >
                                                                    <UTextarea
                                                                        :model-value="slot.content?.[fieldKey as string] || ''"
                                                                        :placeholder="field.placeholder"
                                                                        :rows="2"
                                                                        class="w-full"
                                                                        @update:model-value="(v) => updateSlotContent(section, slotIndex, fieldKey as string, v as string)"
                                                                    />
                                                                </UFormField>

                                                                <!-- Media Field with Picker -->
                                                                <UFormField
                                                                    v-else-if="field.type === 'media'"
                                                                    :label="field.label"
                                                                >
                                                                    <div class="space-y-2">
                                                                        <!-- Preview -->
                                                                        <div
                                                                            v-if="slot.content?.[fieldKey as string]"
                                                                            class="relative w-32 aspect-video rounded-lg overflow-hidden border border-default"
                                                                        >
                                                                            <img
                                                                                :src="slot.content?.[fieldKey as string]"
                                                                                :alt="field.label"
                                                                                class="w-full h-full object-cover"
                                                                            />
                                                                            <button
                                                                                type="button"
                                                                                class="absolute top-1 right-1 p-1 bg-black/50 rounded-full hover:bg-black/70 transition-colors"
                                                                                @click="updateSlotContent(section, slotIndex, fieldKey as string, '')"
                                                                            >
                                                                                <UIcon name="i-lucide-x" class="size-3 text-white" />
                                                                            </button>
                                                                        </div>
                                                                        <!-- Input with picker button -->
                                                                        <div class="flex gap-2">
                                                                            <UInput
                                                                                :model-value="slot.content?.[fieldKey as string] || ''"
                                                                                :placeholder="field.placeholder || 'Enter image URL'"
                                                                                class="flex-1"
                                                                                @update:model-value="(v) => updateSlotContent(section, slotIndex, fieldKey as string, v as string)"
                                                                            />
                                                                            <UButton
                                                                                icon="i-lucide-image"
                                                                                color="neutral"
                                                                                variant="soft"
                                                                                @click="openMediaPicker(section.id, slotIndex, fieldKey as string, null)"
                                                                            />
                                                                        </div>
                                                                    </div>
                                                                </UFormField>

                                                                <!-- Text Field (default) -->
                                                                <UFormField
                                                                    v-else
                                                                    :label="field.label"
                                                                >
                                                                    <UInput
                                                                        :model-value="slot.content?.[fieldKey as string] || ''"
                                                                        :placeholder="field.placeholder"
                                                                        class="w-full"
                                                                        @update:model-value="(v) => updateSlotContent(section, slotIndex, fieldKey as string, v as string)"
                                                                    />
                                                                </UFormField>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </Transition>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Data Source Tab -->
                                <template #data-source>
                                    <div class="pt-4">
                                        <DataSourceConfig
                                            :action="section.dataSource.action"
                                            :params="section.dataSource.params"
                                            :supported-actions="getSectionType(section.type)!.supportedActions"
                                            @update:data-source="(v) => updateSection(section, { dataSource: v })"
                                        />
                                    </div>
                                </template>

                                <!-- Settings Tab -->
                                <template #settings>
                                    <div class="pt-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <template v-for="(field, key) in getSectionType(section.type)!.configSchema" :key="key">
                                                <!-- Select Field -->
                                                <UFormField
                                                    v-if="field.type === 'select'"
                                                    :label="field.label"
                                                >
                                                    <USelectMenu
                                                        :model-value="section.config[key]"
                                                        :items="(field.options || []).map(o => ({ label: o, value: o }))"
                                                        value-key="value"
                                                        class="w-full"
                                                        @update:model-value="(v) => updateSectionConfig(section, key as string, v)"
                                                    />
                                                </UFormField>

                                                <!-- Color Field -->
                                                <UFormField
                                                    v-else-if="field.type === 'color'"
                                                    :label="field.label"
                                                >
                                                    <USelectMenu
                                                        :model-value="section.config[key]"
                                                        :items="getColorOptions()"
                                                        value-key="value"
                                                        class="w-full"
                                                        @update:model-value="(v) => updateSectionConfig(section, key as string, v)"
                                                    >
                                                        <template #leading>
                                                            <div
                                                                class="size-4 rounded border border-default"
                                                                :style="{ backgroundColor: section.config[key] as string || '#fff' }"
                                                            />
                                                        </template>
                                                    </USelectMenu>
                                                </UFormField>

                                                <!-- Toggle Field -->
                                                <UFormField
                                                    v-else-if="field.type === 'toggle'"
                                                    :label="field.label"
                                                >
                                                    <USwitch
                                                        :model-value="section.config[key] as boolean"
                                                        @update:model-value="(v) => updateSectionConfig(section, key as string, v)"
                                                    />
                                                </UFormField>

                                                <!-- Number Field -->
                                                <UFormField
                                                    v-else-if="field.type === 'number'"
                                                    :label="field.label"
                                                >
                                                    <UInput
                                                        :model-value="section.config[key]"
                                                        type="number"
                                                        class="w-full"
                                                        @update:model-value="(v) => updateSectionConfig(section, key as string, Number(v))"
                                                    />
                                                </UFormField>

                                                <!-- Textarea Field -->
                                                <UFormField
                                                    v-else-if="field.type === 'textarea'"
                                                    :label="field.label"
                                                    class="md:col-span-2"
                                                >
                                                    <UTextarea
                                                        :model-value="section.config[key] as string"
                                                        :placeholder="field.placeholder"
                                                        :rows="2"
                                                        class="w-full"
                                                        @update:model-value="(v) => updateSectionConfig(section, key as string, v)"
                                                    />
                                                </UFormField>

                                                <!-- Media Field -->
                                                <UFormField
                                                    v-else-if="field.type === 'media'"
                                                    :label="field.label"
                                                >
                                                    <div class="flex gap-2">
                                                        <UInput
                                                            :model-value="section.config[key] as string"
                                                            :placeholder="field.placeholder || 'Enter image URL'"
                                                            class="flex-1"
                                                            @update:model-value="(v) => updateSectionConfig(section, key as string, v)"
                                                        />
                                                        <UButton
                                                            icon="i-lucide-image"
                                                            color="neutral"
                                                            variant="soft"
                                                            @click="openMediaPicker(section.id, null, null, key as string)"
                                                        />
                                                    </div>
                                                </UFormField>

                                                <!-- Text Field (default) -->
                                                <UFormField
                                                    v-else
                                                    :label="field.label"
                                                >
                                                    <UInput
                                                        :model-value="section.config[key] as string"
                                                        :placeholder="field.placeholder"
                                                        class="w-full"
                                                        @update:model-value="(v) => updateSectionConfig(section, key as string, v)"
                                                    />
                                                </UFormField>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </UTabs>

                            <!-- No configurable options -->
                            <div
                                v-else
                                class="p-4 bg-muted/30 rounded-lg"
                            >
                                <div class="flex items-center gap-2 text-sm text-muted">
                                    <UIcon name="i-lucide-info" class="size-4" />
                                    <span>This section has no configurable options.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>

        <!-- Post Picker Modal -->
        <PostPickerModal
            v-model:open="showPostPicker"
            :excluded-post-ids="excludedPostIds"
            :section-type="editingSectionType ?? undefined"
            @select="selectPost"
        />

        <!-- Media Picker Modal -->
        <MediaPickerModal
            v-model:open="showMediaPicker"
            type="images"
            @select="handleMediaSelect"
        />
    </div>
</template>
