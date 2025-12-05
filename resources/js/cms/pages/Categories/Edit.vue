<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CategoryForm from '../../components/CategoryForm.vue';
import type { Category, ParentOption, Language } from '../../types';

const props = defineProps<{
    category: Category;
    parentOptions: ParentOption[];
    languages: Language[];
}>();

function onSuccess() {
    router.visit('/cms/categories');
}

function onCancel() {
    router.visit('/cms/categories');
}
</script>

<template>
    <Head :title="`Edit ${category.name}`" />

    <DashboardLayout>
        <UDashboardPanel id="categories-edit">
            <template #header>
                <UDashboardNavbar :title="`Edit: ${category.name}`">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-xl mx-auto">
                    <CategoryForm
                        :category="category"
                        :parent-options="parentOptions"
                        :languages="languages"
                        mode="edit"
                        @success="onSuccess"
                        @cancel="onCancel"
                    />

                    <!-- Statistics -->
                    <div v-if="category.posts_count !== undefined" class="mt-6 flex items-center gap-2 text-sm text-muted">
                        <UIcon name="i-lucide-file-text" class="size-4" />
                        <span>{{ category.posts_count }} posts in this category</span>
                    </div>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
