<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import HomepageSectionList from '../../components/layouts/HomepageSectionList.vue';
import SectionTypePicker from '../../components/layouts/SectionTypePicker.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { BreadcrumbItem } from '@nuxt/ui';
import type { HomepageSection, HomepageConfiguration, SectionTypeDefinition } from '../../types';

const props = defineProps<{
    configuration: HomepageConfiguration;
    sectionTypes: Record<string, SectionTypeDefinition>;
}>();

const { cmsPath } = useCmsPath();

const form = useForm({
    sections: JSON.parse(JSON.stringify(props.configuration.sections)) as HomepageSection[],
});

// State
const showAddSectionModal = ref(false);
const isSaving = ref(false);

const hasChanges = computed(() => {
    return JSON.stringify(form.sections) !== JSON.stringify(props.configuration.sections);
});

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Layouts' },
    { label: 'Homepage' },
];

const toast = useToast();

// Methods
function addSection(type: string, position?: number) {
    const definition = props.sectionTypes[type];
    if (!definition) return;

    const newSection: HomepageSection = {
        id: crypto.randomUUID(),
        type,
        order: position ?? form.sections.length,
        enabled: true,
        config: { ...definition.defaultConfig },
        dataSource: { ...definition.defaultDataSource },
        slots: definition.defaultSlots.map(s => ({ ...s })),
    };

    if (position !== undefined) {
        // Shift orders for sections after the insertion point
        form.sections.forEach(s => {
            if (s.order >= position) s.order++;
        });
        form.sections.splice(position, 0, newSection);
    } else {
        form.sections.push(newSection);
    }

    showAddSectionModal.value = false;
}

function removeSection(sectionId: string) {
    const index = form.sections.findIndex(s => s.id === sectionId);
    if (index > -1) {
        form.sections.splice(index, 1);
        // Reorder remaining sections
        form.sections.forEach((s, i) => {
            s.order = i;
        });
    }
    if (editingSectionId.value === sectionId) {
        editingSectionId.value = null;
    }
}

function duplicateSection(sectionId: string) {
    const section = form.sections.find(s => s.id === sectionId);
    if (!section) return;

    const newSection: HomepageSection = {
        ...JSON.parse(JSON.stringify(section)),
        id: crypto.randomUUID(),
        order: section.order + 1,
    };

    // Shift orders for sections after
    form.sections.forEach(s => {
        if (s.order > section.order) s.order++;
    });

    const insertIndex = form.sections.findIndex(s => s.id === sectionId) + 1;
    form.sections.splice(insertIndex, 0, newSection);
}

function toggleSection(sectionId: string) {
    const section = form.sections.find(s => s.id === sectionId);
    if (section) {
        section.enabled = !section.enabled;
    }
}

function reorderSections(newOrder: HomepageSection[]) {
    form.sections = newOrder.map((s, i) => ({ ...s, order: i }));
}

function updateSections(sections: HomepageSection[]) {
    form.sections = sections;
}

function saveChanges() {
    isSaving.value = true;
    form.put(cmsPath('/layouts/homepage'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ title: 'Saved', description: 'Homepage layout updated successfully.', color: 'success' });
            isSaving.value = false;
        },
        onError: (errors) => {
            toast.add({ title: 'Error', description: 'Failed to save homepage layout.', color: 'error' });
            console.error('Homepage layout save error:', errors);
            isSaving.value = false;
        },
    });
}

function resetChanges() {
    form.sections = JSON.parse(JSON.stringify(props.configuration.sections));
}
</script>

<template>
    <Head title="Homepage Layout" />

    <DashboardLayout>
        <UDashboardPanel id="layouts-homepage">
            <template #header>
                <UDashboardNavbar>
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #title>
                        <UBreadcrumb :items="breadcrumbs" />
                    </template>

                    <template #trailing>
                        <div class="flex items-center gap-2">
                            <UButton
                                v-if="hasChanges"
                                color="neutral"
                                variant="ghost"
                                @click="resetChanges"
                                :disabled="isSaving"
                            >
                                Discard
                            </UButton>
                            <UButton
                                @click="saveChanges"
                                :loading="isSaving"
                                :disabled="!hasChanges"
                            >
                                Save Changes
                            </UButton>
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-6 max-w-7xl mx-auto py-6 px-4 lg:px-6">
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-semibold text-highlighted">Homepage Sections</h1>
                            <p class="text-sm text-muted mt-1">
                                Drag and drop to reorder sections. Click to configure each section.
                            </p>
                        </div>
                        <UButton
                            icon="i-lucide-plus"
                            class="shrink-0"
                            @click="showAddSectionModal = true"
                        >
                            Add Section
                        </UButton>
                    </div>

                    <!-- Section List -->
                    <HomepageSectionList
                        :model-value="form.sections"
                        :section-types="sectionTypes"
                        @update:model-value="updateSections"
                        @remove="removeSection"
                        @duplicate="duplicateSection"
                        @toggle="toggleSection"
                        @reorder="reorderSections"
                    />

                    <!-- Empty State -->
                    <div
                        v-if="form.sections.length === 0"
                        class="flex flex-col items-center justify-center py-16 text-center"
                    >
                        <div class="flex items-center justify-center size-16 rounded-full bg-primary/10 mb-4">
                            <UIcon name="i-lucide-layout-template" class="size-8 text-primary" />
                        </div>
                        <h3 class="text-lg font-medium text-highlighted">No sections yet</h3>
                        <p class="text-sm text-muted mt-1 mb-4">
                            Add sections to build your homepage layout.
                        </p>
                        <UButton
                            icon="i-lucide-plus"
                            @click="showAddSectionModal = true"
                        >
                            Add First Section
                        </UButton>
                    </div>

                    <!-- Add Section Button (at bottom) -->
                    <button
                        v-if="form.sections.length > 0"
                        type="button"
                        class="w-full p-4 border-2 border-dashed border-default rounded-xl text-muted hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-2"
                        @click="showAddSectionModal = true"
                    >
                        <UIcon name="i-lucide-plus" class="size-5" />
                        <span>Add Section</span>
                    </button>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Add Section Modal -->
        <SectionTypePicker
            v-model:open="showAddSectionModal"
            :section-types="sectionTypes"
            @select="addSection"
        />
    </DashboardLayout>
</template>
