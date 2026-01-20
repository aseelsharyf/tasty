<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useSettingsNav } from '../../composables/useSettingsNav';

interface Section {
    type: string;
    name: string;
    icon: string;
    description: string;
}

interface Category {
    id: number;
    name: string;
    slug: string;
    depth: number;
}

interface CategoryMapping {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    sections: Section[];
    mappings: Record<string, CategoryMapping[]>;
    categories: Category[];
}>();

const { mainNav } = useSettingsNav();

// Initialize form with current mappings (convert to array of IDs)
const initialMappings: Record<string, number[]> = {};
for (const section of props.sections) {
    initialMappings[section.type] = props.mappings[section.type]?.map(c => c.id) || [];
}

const form = useForm({
    mappings: initialMappings,
});

const categoryOptions = computed(() => {
    return props.categories.map(cat => ({
        label: cat.depth > 0 ? '─'.repeat(cat.depth) + ' ' + cat.name : cat.name,
        value: cat.id,
    }));
});

function getSelectedCategories(sectionType: string): number[] {
    return form.mappings[sectionType] || [];
}

function toggleCategory(sectionType: string, categoryId: number) {
    const current = form.mappings[sectionType] || [];
    const index = current.indexOf(categoryId);

    if (index === -1) {
        form.mappings[sectionType] = [...current, categoryId];
    } else {
        form.mappings[sectionType] = current.filter(id => id !== categoryId);
    }
}

function clearRestrictions(sectionType: string) {
    form.mappings[sectionType] = [];
}

function getCategoryName(categoryId: number): string {
    const cat = props.categories.find(c => c.id === categoryId);
    return cat?.name || '';
}

function onSubmit() {
    form.put('/cms/settings/section-categories', {
        preserveScroll: true,
    });
}

function hasRestrictions(sectionType: string): boolean {
    return (form.mappings[sectionType]?.length || 0) > 0;
}
</script>

<template>
    <Head title="Section Category Restrictions" />

    <DashboardLayout>
        <UDashboardPanel id="section-categories-settings" :ui="{ body: 'lg:py-12' }">
            <template #header>
                <UDashboardNavbar title="Settings">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="mainNav" highlight class="-mx-1 flex-1 overflow-x-auto" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-3xl mx-auto">
                    <UPageCard
                        title="Category Restrictions by Section Type"
                        description="Configure which categories are allowed for each homepage section type. When no categories are selected, all categories are allowed."
                        variant="naked"
                        orientation="horizontal"
                        class="mb-4"
                    >
                        <div class="flex gap-2 w-fit lg:ms-auto">
                            <UButton
                                label="Save Changes"
                                color="neutral"
                                :loading="form.processing"
                                @click="onSubmit"
                            />
                        </div>
                    </UPageCard>

                    <!-- Section List -->
                    <div class="space-y-4">
                        <UPageCard
                            v-for="section in sections"
                            :key="section.type"
                            variant="subtle"
                        >
                            <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                <!-- Section Info -->
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="shrink-0 size-10 rounded-lg bg-default flex items-center justify-center">
                                        <UIcon :name="section.icon" class="size-5 text-muted" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-highlighted">{{ section.name }}</p>
                                        <p class="text-xs text-muted">{{ section.type }}</p>
                                    </div>
                                </div>

                                <!-- Category Selection -->
                                <div class="w-full sm:max-w-xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-muted">
                                            <template v-if="hasRestrictions(section.type)">
                                                {{ getSelectedCategories(section.type).length }} categories allowed
                                            </template>
                                            <template v-else>
                                                All categories allowed
                                            </template>
                                        </span>
                                        <UButton
                                            v-if="hasRestrictions(section.type)"
                                            label="Clear"
                                            color="neutral"
                                            variant="ghost"
                                            size="xs"
                                            @click="clearRestrictions(section.type)"
                                        />
                                    </div>

                                    <!-- Category Dropdown -->
                                    <USelectMenu
                                        :model-value="getSelectedCategories(section.type)"
                                        :items="categoryOptions"
                                        multiple
                                        placeholder="Select categories to restrict..."
                                        value-key="value"
                                        class="w-full"
                                        @update:model-value="(val) => form.mappings[section.type] = val"
                                    />
                                </div>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Info -->
                    <UAlert
                        color="info"
                        variant="subtle"
                        icon="i-lucide-info"
                        title="How Category Restrictions Work"
                        description="When categories are selected for a section type, only posts from those categories can be used in that section. This applies to both manually selected posts and dynamically loaded posts. Leave empty to allow all categories."
                    />
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
