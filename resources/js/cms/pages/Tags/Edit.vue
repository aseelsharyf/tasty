<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import TagForm from '../../components/TagForm.vue';
import type { Tag } from '../../types';

const props = defineProps<{
    tag: Tag;
}>();

function onSuccess() {
    router.visit('/cms/tags');
}

function onCancel() {
    router.visit('/cms/tags');
}
</script>

<template>
    <Head :title="`Edit ${tag.name}`" />

    <DashboardLayout>
        <UDashboardPanel id="tags-edit">
            <template #header>
                <UDashboardNavbar :title="`Edit: ${tag.name}`">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-xl mx-auto">
                    <TagForm
                        :tag="tag"
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
