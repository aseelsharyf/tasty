<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import HomepageSectionList from '../../components/layouts/HomepageSectionList.vue';
import SectionTypePicker from '../../components/layouts/SectionTypePicker.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { BreadcrumbItem } from '@nuxt/ui';
import type { HomepageSection, HomepageConfiguration, SectionTypeDefinition } from '../../types';

interface TagInfo {
    id: number;
    uuid: string;
    name: string;
    slug: string;
}

const props = defineProps<{
    tag: TagInfo;
    configuration: HomepageConfiguration;
    sectionTypes: Record<string, SectionTypeDefinition>;
    useCustomLayout: boolean;
    hasExistingLayout: boolean;
}>();

const { cmsPath } = useCmsPath();

const form = useForm({
    sections: JSON.parse(JSON.stringify(props.configuration.sections)) as HomepageSection[],
    useCustomLayout: props.useCustomLayout,
});

// State
const showAddSectionModal = ref(false);
const isSaving = ref(false);

const hasChanges = computed(() => {
    const sectionsChanged = JSON.stringify(form.sections) !== JSON.stringify(props.configuration.sections);
    const layoutModeChanged = form.useCustomLayout !== props.useCustomLayout;
    return sectionsChanged || layoutModeChanged;
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { label: 'Layouts', to: cmsPath('/layouts') },
    { label: 'Tags' },
    { label: props.tag.name },
]);

const toast = useToast();

// Context for filtering posts by this tag
const layoutContext = computed(() => ({
    type: 'tag' as const,
    id: props.tag.id,
    name: props.tag.name,
    slug: props.tag.slug,
}));

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
        dataSource: {
            ...definition.defaultDataSource,
            // Default to filtering by this tag
            action: 'byTag',
            params: { slugs: [props.tag.slug] },
        },
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
    form.put(cmsPath(`/layouts/tags/${props.tag.uuid}`), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ title: 'Saved', description: 'Tag layout updated successfully.', color: 'success' });
            isSaving.value = false;
        },
        onError: (errors) => {
            toast.add({ title: 'Error', description: 'Failed to save tag layout.', color: 'error' });
            console.error('Tag layout save error:', errors);
            isSaving.value = false;
        },
    });
}

function resetChanges() {
    form.sections = JSON.parse(JSON.stringify(props.configuration.sections));
    form.useCustomLayout = props.useCustomLayout;
}
</script>

<template>
    <Head :title="`${tag.name} Layout`" />

    <DashboardLayout>
        <UDashboardPanel id="layouts-tag">
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
                            <h1 class="text-xl font-semibold text-highlighted">
                                {{ tag.name }} Layout
                            </h1>
                            <p class="text-sm text-muted mt-1">
                                Configure the layout for the <strong>{{ tag.name }}</strong> tag page.
                            </p>
                        </div>
                    </div>

                    <!-- Layout Mode Toggle -->
                    <div class="rounded-xl border border-default bg-elevated/50 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center size-10 rounded-lg" :class="form.useCustomLayout ? 'bg-primary/10' : 'bg-muted/20'">
                                    <UIcon
                                        :name="form.useCustomLayout ? 'i-lucide-layout-template' : 'i-lucide-layout-list'"
                                        class="size-5"
                                        :class="form.useCustomLayout ? 'text-primary' : 'text-muted'"
                                    />
                                </div>
                                <div>
                                    <div class="font-medium text-highlighted">
                                        {{ form.useCustomLayout ? 'Custom Layout' : 'Default Layout' }}
                                    </div>
                                    <div class="text-sm text-muted">
                                        {{ form.useCustomLayout
                                            ? 'Using a custom section-based layout for this tag.'
                                            : 'Using the default tag template with paginated posts.'
                                        }}
                                    </div>
                                </div>
                            </div>
                            <USwitch v-model="form.useCustomLayout" />
                        </div>
                    </div>

                    <!-- Section Builder (only when custom layout enabled) -->
                    <template v-if="form.useCustomLayout">
                        <!-- Add Section Header -->
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-medium text-highlighted">Sections</h2>
                            <UButton
                                icon="i-lucide-plus"
                                size="sm"
                                @click="showAddSectionModal = true"
                            >
                                Add Section
                            </UButton>
                        </div>

                        <!-- Section List -->
                        <HomepageSectionList
                            :model-value="form.sections"
                            :section-types="sectionTypes"
                            :context="layoutContext"
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
                                Add sections to build the {{ tag.name }} tag page layout.
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
                    </template>

                    <!-- Default Layout Info -->
                    <div v-else class="rounded-xl border border-default bg-elevated/30 p-8 text-center">
                        <UIcon name="i-lucide-layout-list" class="size-12 text-muted mx-auto mb-4" />
                        <h3 class="text-lg font-medium text-highlighted">Default Layout Active</h3>
                        <p class="text-sm text-muted mt-2 max-w-md mx-auto">
                            This tag page will use the default template which displays a paginated list of posts.
                            Enable custom layout above to create a section-based page design.
                        </p>
                    </div>
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
