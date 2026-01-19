<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import SlotAssignmentPanel from './SlotAssignmentPanel.vue';
import DataSourceConfig from './DataSourceConfig.vue';
import type { HomepageSection, SectionTypeDefinition, HomepageSectionSlot } from '../../types';

const props = defineProps<{
    section: HomepageSection | null;
    sectionType: SectionTypeDefinition | null;
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
    'update:open': [value: boolean];
    'update:config': [config: Record<string, unknown>];
    'update:data-source': [dataSource: { action: string; params: Record<string, unknown> }];
    'update:slots': [slots: HomepageSectionSlot[]];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => {
        emit('update:open', value);
        if (!value) {
            emit('close');
        }
    },
});

const activeTab = ref('config');

// Local copies for editing
const localConfig = ref<Record<string, unknown>>({});
const localDataSource = ref<{ action: string; params: Record<string, unknown> }>({ action: 'recent', params: {} });
const localSlots = ref<HomepageSectionSlot[]>([]);

// Sync local state when section changes
watch(() => props.section, (section) => {
    if (section) {
        localConfig.value = { ...section.config };
        localDataSource.value = { ...section.dataSource };
        localSlots.value = section.slots.map(s => ({ ...s }));
    }
}, { immediate: true });

// Emit changes immediately
watch(localConfig, (config) => {
    if (props.section) {
        emit('update:config', config);
    }
}, { deep: true });

watch(localDataSource, (dataSource) => {
    if (props.section) {
        emit('update:data-source', dataSource);
    }
}, { deep: true });

watch(localSlots, (slots) => {
    if (props.section) {
        emit('update:slots', slots);
    }
}, { deep: true });

const tabs = computed(() => {
    const items = [
        { label: 'Configuration', value: 'config', icon: 'i-lucide-settings-2' },
    ];

    if (props.sectionType?.supportedActions.length) {
        items.push({ label: 'Data Source', value: 'data-source', icon: 'i-lucide-database' });
    }

    if (props.sectionType?.slotCount) {
        items.push({ label: 'Slots', value: 'slots', icon: 'i-lucide-layout-grid' });
    }

    return items;
});

function getFieldComponent(type: string): string {
    switch (type) {
        case 'textarea':
            return 'UTextarea';
        case 'toggle':
            return 'USwitch';
        case 'number':
            return 'UInput';
        default:
            return 'UInput';
    }
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
</script>

<template>
    <USlideover v-model:open="isOpen" side="right" :ui="{ width: 'max-w-lg' }">
        <template #content>
            <div v-if="section && sectionType" class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-default">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center size-10 rounded-lg bg-primary/10">
                            <UIcon :name="sectionType.icon" class="size-5 text-primary" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-highlighted">{{ sectionType.name }}</h3>
                            <p class="text-xs text-muted">Configure section settings</p>
                        </div>
                    </div>
                    <UButton
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="emit('close')"
                    />
                </div>

                <!-- Tabs -->
                <div class="border-b border-default">
                    <nav class="flex gap-1 p-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.value"
                            type="button"
                            :class="[
                                'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                activeTab === tab.value
                                    ? 'bg-primary/10 text-primary'
                                    : 'text-muted hover:text-highlighted hover:bg-muted/50',
                            ]"
                            @click="activeTab = tab.value"
                        >
                            <UIcon :name="tab.icon" class="size-4" />
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-4">
                    <!-- Configuration Tab -->
                    <div v-if="activeTab === 'config'" class="space-y-4">
                        <template v-for="(field, key) in sectionType.configSchema" :key="key">
                            <!-- Select Field -->
                            <UFormField
                                v-if="field.type === 'select'"
                                :label="field.label"
                            >
                                <USelectMenu
                                    v-model="localConfig[key]"
                                    :items="(field.options || []).map(o => ({ label: o, value: o }))"
                                    value-key="value"
                                    class="w-full"
                                />
                            </UFormField>

                            <!-- Color Field -->
                            <UFormField
                                v-else-if="field.type === 'color'"
                                :label="field.label"
                            >
                                <USelectMenu
                                    v-model="localConfig[key]"
                                    :items="getColorOptions()"
                                    value-key="value"
                                    class="w-full"
                                >
                                    <template #leading>
                                        <div
                                            class="size-4 rounded border border-default"
                                            :style="{ backgroundColor: localConfig[key] as string || '#fff' }"
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
                                    v-model="localConfig[key] as boolean"
                                />
                            </UFormField>

                            <!-- Number Field -->
                            <UFormField
                                v-else-if="field.type === 'number'"
                                :label="field.label"
                            >
                                <UInput
                                    v-model.number="localConfig[key]"
                                    type="number"
                                    class="w-full"
                                />
                            </UFormField>

                            <!-- Textarea Field -->
                            <UFormField
                                v-else-if="field.type === 'textarea'"
                                :label="field.label"
                            >
                                <UTextarea
                                    v-model="localConfig[key] as string"
                                    :placeholder="field.placeholder"
                                    :rows="3"
                                    class="w-full"
                                />
                            </UFormField>

                            <!-- Media Field -->
                            <UFormField
                                v-else-if="field.type === 'media'"
                                :label="field.label"
                            >
                                <UInput
                                    v-model="localConfig[key] as string"
                                    :placeholder="field.placeholder || 'Enter image URL'"
                                    class="w-full"
                                />
                            </UFormField>

                            <!-- Text Field (default) -->
                            <UFormField
                                v-else
                                :label="field.label"
                            >
                                <UInput
                                    v-model="localConfig[key] as string"
                                    :placeholder="field.placeholder"
                                    class="w-full"
                                />
                            </UFormField>
                        </template>
                    </div>

                    <!-- Data Source Tab -->
                    <div v-else-if="activeTab === 'data-source'">
                        <DataSourceConfig
                            v-model:action="localDataSource.action"
                            v-model:params="localDataSource.params"
                            :supported-actions="sectionType.supportedActions"
                            :content-type="sectionType.contentType"
                        />
                    </div>

                    <!-- Slots Tab -->
                    <div v-else-if="activeTab === 'slots'">
                        <SlotAssignmentPanel
                            v-model="localSlots"
                            :slot-count="sectionType.slotCount"
                            :min-slots="sectionType.minSlots"
                            :max-slots="sectionType.maxSlots"
                            :section-type="section.type"
                            :content-type="sectionType.contentType"
                        />
                    </div>
                </div>
            </div>
        </template>
    </USlideover>
</template>
