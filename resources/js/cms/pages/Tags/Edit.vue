<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import TagForm from '../../components/TagForm.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Tag, Language } from '../../types';

const props = defineProps<{
    tag: Tag;
    languages: Language[];
}>();

const { cmsPath } = useCmsPath();

function onSuccess() {
    router.visit(cmsPath('/tags'));
}

function onCancel() {
    router.visit(cmsPath('/tags'));
}
</script>

<template>
    <Head :title="`Edit ${tag.name}`" />

    <DashboardLayout>
        <UDashboardPanel id="tags-edit">
            <template #header>
                <UDashboardNavbar :ui="{ title: 'truncate max-w-[200px] sm:max-w-none' }">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #title>
                        <span class="hidden sm:inline">Edit:</span>
                        <span class="truncate">{{ tag.name }}</span>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-xl mx-auto">
                    <TagForm
                        :tag="tag"
                        :languages="languages"
                        mode="edit"
                        @success="onSuccess"
                        @cancel="onCancel"
                    />

                    <!-- Statistics -->
                    <div v-if="tag.posts_count !== undefined" class="mt-6 flex items-center gap-2 text-sm text-muted">
                        <UIcon name="i-lucide-tags" class="size-4" />
                        <span>{{ tag.posts_count }} posts with this tag</span>
                    </div>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
