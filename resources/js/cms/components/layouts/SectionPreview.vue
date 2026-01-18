<script setup lang="ts">
import { computed } from 'vue';
import type { PreviewSchema, PreviewSchemaArea, HomepageSectionSlot, PostSearchResult, ProductSearchResult } from '../../types';

const props = defineProps<{
    schema: PreviewSchema;
    sectionType: string;
    slots?: HomepageSectionSlot[];
    config?: Record<string, unknown>;
    postCache?: Record<number, PostSearchResult>;
    productCache?: Record<number, ProductSearchResult>;
    interactive?: boolean;
}>();

const emit = defineEmits<{
    'slot-click': [slotIndex: number];
}>();

// Dynamically adjust schema based on actual slot count
const adjustedSchema = computed<PreviewSchema>(() => {
    if (!props.slots || props.slots.length === 0) {
        return props.schema;
    }

    const actualSlotCount = props.slots.length;

    // Deep clone the schema
    const schema: PreviewSchema = JSON.parse(JSON.stringify(props.schema));

    // Find expandable areas (grid or scroll areas with slot children)
    for (const area of schema.areas) {
        if (area.children && (area.gridCols || area.scroll)) {
            const slotChildren = area.children.filter(c => typeof c.slotIndex === 'number');
            const nonSlotChildren = area.children.filter(c => typeof c.slotIndex !== 'number');

            if (slotChildren.length > 0) {
                const maxSchemaSlotIndex = Math.max(...slotChildren.map(c => c.slotIndex!));
                const minSchemaSlotIndex = Math.min(...slotChildren.map(c => c.slotIndex!));

                // If we have more slots than schema defines, add more slot areas
                if (actualSlotCount > maxSchemaSlotIndex + 1) {
                    for (let i = maxSchemaSlotIndex + 1; i < actualSlotCount; i++) {
                        const templateChild = slotChildren[slotChildren.length - 1];
                        slotChildren.push({
                            ...templateChild,
                            id: `slot-${i}`,
                            label: `${i + 1}`,
                            slotIndex: i,
                        });
                    }
                }
                // If we have fewer slots, remove extra slot areas
                else if (actualSlotCount <= maxSchemaSlotIndex) {
                    const slotsToKeep = slotChildren.filter(c => c.slotIndex! < actualSlotCount);
                    slotChildren.splice(0, slotChildren.length, ...slotsToKeep);
                }

                // Rebuild children array
                area.children = [...nonSlotChildren, ...slotChildren];
            }
        }
    }

    return schema;
});

function getWidthClass(width?: string): string {
    switch (width) {
        case '1/4': return 'w-1/4';
        case '1/3': return 'w-1/3';
        case '1/2': return 'w-1/2';
        case '2/3': return 'w-2/3';
        case '3/4': return 'w-3/4';
        case 'full':
        default: return 'w-full';
    }
}

function getHeightClass(height?: string): string {
    switch (height) {
        case '1/2': return 'h-1/2';
        case '1/3': return 'h-1/3';
        case '2/3': return 'h-2/3';
        case 'auto': return 'h-auto';
        case 'full':
        default: return 'h-full';
    }
}

function getGridColsClass(cols?: number): string {
    switch (cols) {
        case 2: return 'grid-cols-2';
        case 3: return 'grid-cols-3';
        case 4: return 'grid-cols-4';
        default: return 'grid-cols-2';
    }
}

function getLayoutClass(layout: string): string {
    switch (layout) {
        case 'split-horizontal':
        case 'intro-scroll':
        case 'featured-grid':
            return 'flex flex-row';
        case 'grid':
            return 'flex flex-col';
        case 'single':
        default:
            return 'flex flex-col';
    }
}

function hasSlotIndex(area: PreviewSchemaArea): boolean {
    return typeof area.slotIndex === 'number';
}

function isSlotValid(slotIndex: number | undefined): boolean {
    if (slotIndex === undefined || !props.slots) return false;
    return slotIndex < props.slots.length;
}

function getSlotData(slotIndex: number | undefined) {
    if (slotIndex === undefined || !props.slots) return null;
    return props.slots[slotIndex] || null;
}

function getPostForSlot(slotIndex: number | undefined): PostSearchResult | ProductSearchResult | null {
    const slot = getSlotData(slotIndex);
    if (!slot) return null;

    // Check for product first (for product sections like add-to-cart)
    if (slot.productId && props.productCache) {
        return props.productCache[slot.productId] || null;
    }

    // Then check for post
    if (slot.postId && props.postCache) {
        return props.postCache[slot.postId] || null;
    }

    return null;
}

function getSlotImage(slotIndex: number | undefined): string | null {
    // Check for manual post
    const post = getPostForSlot(slotIndex);
    if (post?.image) return post.image;

    // Check for static content image
    const slot = getSlotData(slotIndex);
    if (slot?.mode === 'static' && slot.content?.image) {
        return slot.content.image;
    }

    return null;
}

function getSlotTitle(slotIndex: number | undefined): string | null {
    // Check for manual post
    const post = getPostForSlot(slotIndex);
    if (post?.title) return post.title;

    // Check for static content
    const slot = getSlotData(slotIndex);
    if (slot?.mode === 'static') {
        return slot.content?.title || slot.content?.name || slot.content?.kicker || null;
    }

    return null;
}

function getSlotMode(slotIndex: number | undefined): string | null {
    const slot = getSlotData(slotIndex);
    return slot?.mode || null;
}

// Get config image for non-slot areas (like intro image)
function getConfigImage(areaId: string): string | null {
    if (!props.config) return null;

    // Map area IDs to config keys
    const configKeyMap: Record<string, string[]> = {
        'intro': ['introImage', 'image'],
        'header': ['headerImage', 'image'],
        'background': ['backgroundImage', 'bgImage', 'image'],
    };

    const possibleKeys = configKeyMap[areaId] || [areaId + 'Image', areaId];

    for (const key of possibleKeys) {
        const value = props.config[key];
        if (typeof value === 'string' && value.length > 0) {
            return value;
        }
    }

    return null;
}

function handleSlotClick(slotIndex: number | undefined) {
    if (props.interactive && slotIndex !== undefined && isSlotValid(slotIndex)) {
        emit('slot-click', slotIndex);
    }
}
</script>

<template>
    <div class="aspect-[16/9] bg-muted/10 rounded-lg border border-dashed border-default p-3 relative overflow-hidden">
        <div
            :class="[
                'h-full gap-1.5',
                getLayoutClass(adjustedSchema.layout)
            ]"
        >
            <!-- Render areas -->
            <template v-for="area in adjustedSchema.areas" :key="area.id">
                <!-- Area with children (containers) -->
                <div
                    v-if="area.children"
                    :class="[
                        'flex gap-1.5 min-w-0',
                        getWidthClass(area.width),
                        getHeightClass(area.height),
                        area.gridCols ? `grid ${getGridColsClass(area.gridCols)}` : 'flex-col',
                        area.scroll === 'horizontal' ? 'flex-row overflow-hidden' : ''
                    ]"
                >
                    <!-- Nested children -->
                    <template v-for="child in area.children" :key="child.id">
                        <div
                            v-if="!hasSlotIndex(child) || isSlotValid(child.slotIndex)"
                            :class="[
                                'rounded border border-dashed flex items-center justify-center text-[10px] font-medium min-h-[24px] px-1 relative overflow-hidden group/slot',
                                hasSlotIndex(child) && interactive && isSlotValid(child.slotIndex) ? 'cursor-pointer' : '',
                                hasSlotIndex(child)
                                    ? getSlotImage(child.slotIndex)
                                        ? 'border-primary/50 bg-transparent'
                                        : 'bg-primary/10 border-primary/30 text-primary hover:bg-primary/20'
                                    : getConfigImage(child.id)
                                        ? 'border-muted/50 bg-transparent'
                                        : 'bg-muted/20 border-muted/30 text-muted',
                                area.scroll === 'horizontal' ? 'flex-shrink-0 w-16' : '',
                                !area.gridCols && child.height ? getHeightClass(child.height) : ''
                            ]"
                            @click="handleSlotClick(child.slotIndex)"
                        >
                            <!-- Slot with image -->
                            <template v-if="hasSlotIndex(child) && getSlotImage(child.slotIndex)">
                                <img
                                    :src="getSlotImage(child.slotIndex)!"
                                    :alt="getSlotTitle(child.slotIndex) || ''"
                                    class="absolute inset-0 w-full h-full object-cover"
                                />
                                <!-- Hover overlay with title -->
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover/slot:opacity-100 transition-opacity flex items-center justify-center p-1">
                                    <span class="text-white text-[9px] text-center line-clamp-2">
                                        {{ getSlotTitle(child.slotIndex) || child.label }}
                                    </span>
                                </div>
                                <!-- Mode badge -->
                                <div
                                    v-if="getSlotMode(child.slotIndex)"
                                    class="absolute top-0.5 right-0.5 px-1 py-0.5 rounded text-[7px] font-semibold uppercase"
                                    :class="{
                                        'bg-info text-white': getSlotMode(child.slotIndex) === 'dynamic',
                                        'bg-primary text-white': getSlotMode(child.slotIndex) === 'manual',
                                        'bg-success text-white': getSlotMode(child.slotIndex) === 'static',
                                    }"
                                >
                                    {{ getSlotMode(child.slotIndex)?.charAt(0) }}
                                </div>
                            </template>
                            <!-- Non-slot area with config image (e.g., intro image) -->
                            <template v-else-if="!hasSlotIndex(child) && getConfigImage(child.id)">
                                <img
                                    :src="getConfigImage(child.id)!"
                                    :alt="child.label"
                                    class="absolute inset-0 w-full h-full object-cover"
                                />
                                <!-- Hover overlay with label -->
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover/slot:opacity-100 transition-opacity flex items-center justify-center p-1">
                                    <span class="text-white text-[9px] text-center">
                                        {{ child.label }}
                                    </span>
                                </div>
                            </template>
                            <!-- Dynamic mode slot -->
                            <template v-else-if="hasSlotIndex(child) && getSlotMode(child.slotIndex) === 'dynamic'">
                                <div class="absolute inset-0 bg-info/10 flex items-center justify-center">
                                    <UIcon name="i-lucide-zap" class="size-4 text-info" />
                                </div>
                                <div class="absolute top-0.5 right-0.5 px-1 py-0.5 rounded text-[7px] font-semibold uppercase bg-info text-white">D</div>
                            </template>
                            <!-- Empty slot or area -->
                            <template v-else>
                                <span class="truncate">{{ child.label }}</span>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Simple area (no children) -->
                <div
                    v-else
                    :class="[
                        'rounded border border-dashed flex items-center justify-center min-h-[40px] relative overflow-hidden group/slot',
                        hasSlotIndex(area) && interactive && isSlotValid(area.slotIndex) ? 'cursor-pointer' : '',
                        getWidthClass(area.width),
                        getHeightClass(area.height),
                        hasSlotIndex(area)
                            ? getSlotImage(area.slotIndex)
                                ? 'border-primary/50 bg-transparent'
                                : 'bg-primary/10 border-primary/30 hover:bg-primary/20'
                            : getConfigImage(area.id)
                                ? 'border-muted/50 bg-transparent'
                                : 'bg-muted/20 border-muted/30'
                    ]"
                    @click="handleSlotClick(area.slotIndex)"
                >
                    <!-- Slot with image -->
                    <template v-if="hasSlotIndex(area) && getSlotImage(area.slotIndex)">
                        <img
                            :src="getSlotImage(area.slotIndex)!"
                            :alt="getSlotTitle(area.slotIndex) || ''"
                            class="absolute inset-0 w-full h-full object-cover"
                        />
                        <!-- Hover overlay with title -->
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover/slot:opacity-100 transition-opacity flex items-center justify-center p-2">
                            <span class="text-white text-xs text-center line-clamp-2">
                                {{ getSlotTitle(area.slotIndex) || area.label }}
                            </span>
                        </div>
                        <!-- Mode badge -->
                        <div
                            v-if="getSlotMode(area.slotIndex)"
                            class="absolute top-1 right-1 px-1.5 py-0.5 rounded text-[8px] font-semibold uppercase"
                            :class="{
                                'bg-info text-white': getSlotMode(area.slotIndex) === 'dynamic',
                                'bg-primary text-white': getSlotMode(area.slotIndex) === 'manual',
                                'bg-success text-white': getSlotMode(area.slotIndex) === 'static',
                            }"
                        >
                            {{ getSlotMode(area.slotIndex)?.charAt(0) }}
                        </div>
                    </template>
                    <!-- Non-slot area with config image -->
                    <template v-else-if="!hasSlotIndex(area) && getConfigImage(area.id)">
                        <img
                            :src="getConfigImage(area.id)!"
                            :alt="area.label"
                            class="absolute inset-0 w-full h-full object-cover"
                        />
                        <!-- Hover overlay with label -->
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover/slot:opacity-100 transition-opacity flex items-center justify-center p-2">
                            <span class="text-white text-xs text-center">
                                {{ area.label }}
                            </span>
                        </div>
                    </template>
                    <!-- Dynamic mode slot -->
                    <template v-else-if="hasSlotIndex(area) && getSlotMode(area.slotIndex) === 'dynamic'">
                        <div class="absolute inset-0 bg-info/10 flex items-center justify-center">
                            <div class="text-center">
                                <UIcon name="i-lucide-zap" class="size-6 text-info" />
                                <p class="text-info text-[10px]">Dynamic</p>
                            </div>
                        </div>
                        <div class="absolute top-1 right-1 px-1.5 py-0.5 rounded text-[8px] font-semibold uppercase bg-info text-white">D</div>
                        <!-- Play button overlay for video -->
                        <div
                            v-if="area.showPlay"
                            class="absolute inset-0 flex items-center justify-center pointer-events-none"
                        >
                            <div class="size-10 rounded-full bg-white/80 flex items-center justify-center">
                                <UIcon name="i-lucide-play" class="size-5 text-primary ml-0.5" />
                            </div>
                        </div>
                    </template>
                    <!-- Empty slot or non-slot area -->
                    <template v-else>
                        <!-- Play button overlay for video -->
                        <div
                            v-if="area.showPlay"
                            class="absolute inset-0 flex items-center justify-center"
                        >
                            <div class="size-8 rounded-full bg-primary/20 flex items-center justify-center">
                                <UIcon name="i-lucide-play" class="size-4 text-primary ml-0.5" />
                            </div>
                        </div>

                        <span
                            :class="[
                                'text-[10px] font-medium truncate px-1',
                                hasSlotIndex(area) ? 'text-primary' : 'text-muted'
                            ]"
                        >
                            {{ area.label }}
                        </span>
                    </template>
                </div>
            </template>
        </div>
    </div>
</template>
