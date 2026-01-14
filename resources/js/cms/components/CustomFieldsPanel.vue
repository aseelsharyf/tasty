<script setup lang="ts">
import { computed } from 'vue';

interface PostTypeField {
    name: string;
    label: string;
    type: 'text' | 'number' | 'textarea' | 'select' | 'toggle' | 'repeater' | 'grouped-repeater';
    suffix?: string;
    options?: string[];
}

interface GroupedRepeaterSection {
    section: string;
    items: string[];
}

interface PostTypeConfig {
    key: string;
    label: string;
    icon?: string;
    fields: PostTypeField[];
}

const props = defineProps<{
    postType: PostTypeConfig | null;
    customFields: Record<string, unknown>;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:customFields': [value: Record<string, unknown>];
}>();

// Get field value with type safety
function getFieldValue<T>(name: string, defaultValue: T): T {
    return (props.customFields?.[name] as T) ?? defaultValue;
}

// Set field value
function setFieldValue(name: string, value: unknown) {
    emit('update:customFields', {
        ...props.customFields,
        [name]: value,
    });
}

// For repeater fields - add item
function addRepeaterItem(name: string, value: string) {
    if (!value.trim()) return;
    const items = getFieldValue<string[]>(name, []);
    setFieldValue(name, [...items, value.trim()]);
}

// For repeater fields - remove item
function removeRepeaterItem(name: string, index: number) {
    const items = getFieldValue<string[]>(name, []);
    const newItems = [...items];
    newItems.splice(index, 1);
    setFieldValue(name, newItems);
}

// For repeater fields - update item
function updateRepeaterItem(name: string, index: number, value: string) {
    const items = getFieldValue<string[]>(name, []);
    const newItems = [...items];
    newItems[index] = value;
    setFieldValue(name, newItems);
}

// For grouped-repeater fields - add section
function addGroupedSection(name: string, sectionName: string) {
    if (!sectionName.trim()) return;
    const sections = getFieldValue<GroupedRepeaterSection[]>(name, []);
    setFieldValue(name, [...sections, { section: sectionName.trim(), items: [] }]);
}

// For grouped-repeater fields - remove section
function removeGroupedSection(name: string, sectionIndex: number) {
    const sections = getFieldValue<GroupedRepeaterSection[]>(name, []);
    const newSections = [...sections];
    newSections.splice(sectionIndex, 1);
    setFieldValue(name, newSections);
}

// For grouped-repeater fields - update section name
function updateGroupedSectionName(name: string, sectionIndex: number, sectionName: string) {
    const sections = getFieldValue<GroupedRepeaterSection[]>(name, []);
    const newSections = [...sections];
    newSections[sectionIndex] = { ...newSections[sectionIndex], section: sectionName };
    setFieldValue(name, newSections);
}

// For grouped-repeater fields - add item to section
function addGroupedItem(name: string, sectionIndex: number, value: string) {
    if (!value.trim()) return;
    const sections = getFieldValue<GroupedRepeaterSection[]>(name, []);
    const newSections = [...sections];
    newSections[sectionIndex] = {
        ...newSections[sectionIndex],
        items: [...(newSections[sectionIndex].items || []), value.trim()]
    };
    setFieldValue(name, newSections);
}

// For grouped-repeater fields - remove item from section
function removeGroupedItem(name: string, sectionIndex: number, itemIndex: number) {
    const sections = getFieldValue<GroupedRepeaterSection[]>(name, []);
    const newSections = [...sections];
    const newItems = [...(newSections[sectionIndex].items || [])];
    newItems.splice(itemIndex, 1);
    newSections[sectionIndex] = { ...newSections[sectionIndex], items: newItems };
    setFieldValue(name, newSections);
}

// For grouped-repeater fields - update item in section
function updateGroupedItem(name: string, sectionIndex: number, itemIndex: number, value: string) {
    const sections = getFieldValue<GroupedRepeaterSection[]>(name, []);
    const newSections = [...sections];
    const newItems = [...(newSections[sectionIndex].items || [])];
    newItems[itemIndex] = value;
    newSections[sectionIndex] = { ...newSections[sectionIndex], items: newItems };
    setFieldValue(name, newSections);
}

// Has any fields to show
const hasFields = computed(() => {
    return props.postType?.fields && props.postType.fields.length > 0;
});

// Get icon for post type
const postTypeIcon = computed(() => {
    return props.postType?.icon || 'i-lucide-file-text';
});

// Convert options to select items
function getSelectItems(options?: string[]) {
    if (!options) return [];
    return options.map(opt => ({ label: opt, value: opt }));
}
</script>

<template>
    <div v-if="hasFields">
        <div class="h-px bg-default" />
        <div>
            <div class="flex items-center gap-2 mb-3">
                <UIcon :name="postTypeIcon" class="size-4 text-muted" />
                <span class="text-xs font-medium text-muted uppercase tracking-wider">{{ postType?.label }} Fields</span>
            </div>
            <div class="space-y-3">
                <template v-for="field in postType?.fields" :key="field.name">
                    <!-- Text Field -->
                    <div v-if="field.type === 'text'">
                        <label class="text-xs text-muted mb-1 block">{{ field.label }}</label>
                        <UInput
                            :model-value="getFieldValue<string>(field.name, '')"
                            size="sm"
                            class="w-full"
                            :disabled="disabled"
                            @update:model-value="setFieldValue(field.name, $event)"
                        />
                    </div>

                    <!-- Number Field -->
                    <div v-else-if="field.type === 'number'">
                        <label class="text-xs text-muted mb-1 block">
                            {{ field.label }}
                            <span v-if="field.suffix" class="text-muted/60">({{ field.suffix }})</span>
                        </label>
                        <UInput
                            :model-value="getFieldValue<number | null>(field.name, null)"
                            type="number"
                            min="0"
                            size="sm"
                            class="w-full"
                            :disabled="disabled"
                            @update:model-value="setFieldValue(field.name, $event)"
                        />
                    </div>

                    <!-- Textarea Field -->
                    <div v-else-if="field.type === 'textarea'">
                        <label class="text-xs text-muted mb-1 block">{{ field.label }}</label>
                        <UTextarea
                            :model-value="getFieldValue<string>(field.name, '')"
                            size="sm"
                            class="w-full"
                            :rows="3"
                            :disabled="disabled"
                            @update:model-value="setFieldValue(field.name, $event)"
                        />
                    </div>

                    <!-- Select Field -->
                    <div v-else-if="field.type === 'select'">
                        <label class="text-xs text-muted mb-1 block">{{ field.label }}</label>
                        <USelectMenu
                            :model-value="getFieldValue<string>(field.name, '')"
                            :items="getSelectItems(field.options)"
                            value-key="value"
                            size="sm"
                            class="w-full"
                            :disabled="disabled"
                            @update:model-value="setFieldValue(field.name, $event)"
                        />
                    </div>

                    <!-- Toggle Field -->
                    <div v-else-if="field.type === 'toggle'" class="flex items-center justify-between">
                        <label class="text-xs text-muted">{{ field.label }}</label>
                        <USwitch
                            :model-value="getFieldValue<boolean>(field.name, false)"
                            size="sm"
                            :disabled="disabled"
                            @update:model-value="setFieldValue(field.name, $event)"
                        />
                    </div>

                    <!-- Repeater Field -->
                    <div v-else-if="field.type === 'repeater'">
                        <label class="text-xs text-muted mb-1 block">{{ field.label }}</label>
                        <div class="space-y-1.5">
                            <div
                                v-for="(item, index) in getFieldValue<string[]>(field.name, [])"
                                :key="index"
                                class="flex gap-1.5"
                            >
                                <UInput
                                    :model-value="item"
                                    size="sm"
                                    class="flex-1 min-w-0"
                                    :disabled="disabled"
                                    @update:model-value="updateRepeaterItem(field.name, index, $event as string)"
                                />
                                <UButton
                                    size="sm"
                                    color="neutral"
                                    variant="ghost"
                                    icon="i-lucide-x"
                                    :disabled="disabled"
                                    @click="removeRepeaterItem(field.name, index)"
                                />
                            </div>
                            <div class="flex gap-1.5">
                                <UInput
                                    :id="`repeater-new-${field.name}`"
                                    placeholder="Add..."
                                    size="sm"
                                    class="flex-1 min-w-0"
                                    :disabled="disabled"
                                    @keyup.enter.prevent="(e: KeyboardEvent) => {
                                        const input = e.target as HTMLInputElement;
                                        addRepeaterItem(field.name, input.value);
                                        input.value = '';
                                    }"
                                />
                                <UButton
                                    size="sm"
                                    color="neutral"
                                    variant="soft"
                                    icon="i-lucide-plus"
                                    :disabled="disabled"
                                    @click="() => {
                                        const input = document.getElementById(`repeater-new-${field.name}`) as HTMLInputElement;
                                        if (input) {
                                            addRepeaterItem(field.name, input.value);
                                            input.value = '';
                                        }
                                    }"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Grouped Repeater Field (for sectioned ingredients) -->
                    <div v-else-if="field.type === 'grouped-repeater'">
                        <label class="text-xs text-muted mb-2 block">{{ field.label }}</label>
                        <div class="space-y-3">
                            <!-- Sections -->
                            <div
                                v-for="(section, sectionIndex) in getFieldValue<GroupedRepeaterSection[]>(field.name, [])"
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
                                        :disabled="disabled"
                                        @update:model-value="updateGroupedSectionName(field.name, sectionIndex, $event as string)"
                                    />
                                    <UButton
                                        size="sm"
                                        color="error"
                                        variant="ghost"
                                        icon="i-lucide-trash-2"
                                        :disabled="disabled"
                                        @click="removeGroupedSection(field.name, sectionIndex)"
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
                                            :disabled="disabled"
                                            @update:model-value="updateGroupedItem(field.name, sectionIndex, itemIndex, $event as string)"
                                        />
                                        <UButton
                                            size="sm"
                                            color="neutral"
                                            variant="ghost"
                                            icon="i-lucide-x"
                                            :disabled="disabled"
                                            @click="removeGroupedItem(field.name, sectionIndex, itemIndex)"
                                        />
                                    </div>
                                    <!-- Add item to section -->
                                    <div class="flex gap-1.5">
                                        <UInput
                                            :id="`grouped-item-new-${field.name}-${sectionIndex}`"
                                            placeholder="Add item..."
                                            size="sm"
                                            class="flex-1 min-w-0"
                                            :disabled="disabled"
                                            @keyup.enter.prevent="(e: KeyboardEvent) => {
                                                const input = e.target as HTMLInputElement;
                                                addGroupedItem(field.name, sectionIndex, input.value);
                                                input.value = '';
                                            }"
                                        />
                                        <UButton
                                            size="sm"
                                            color="neutral"
                                            variant="soft"
                                            icon="i-lucide-plus"
                                            :disabled="disabled"
                                            @click="() => {
                                                const input = document.getElementById(`grouped-item-new-${field.name}-${sectionIndex}`) as HTMLInputElement;
                                                if (input) {
                                                    addGroupedItem(field.name, sectionIndex, input.value);
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
                                    :disabled="disabled"
                                    @keyup.enter.prevent="(e: KeyboardEvent) => {
                                        const input = e.target as HTMLInputElement;
                                        addGroupedSection(field.name, input.value);
                                        input.value = '';
                                    }"
                                />
                                <UButton
                                    size="sm"
                                    color="primary"
                                    variant="soft"
                                    icon="i-lucide-folder-plus"
                                    :disabled="disabled"
                                    @click="() => {
                                        const input = document.getElementById(`grouped-section-new-${field.name}`) as HTMLInputElement;
                                        if (input) {
                                            addGroupedSection(field.name, input.value);
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
</template>
