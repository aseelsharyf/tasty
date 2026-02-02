<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CategoryForm from '../../components/CategoryForm.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { ParentOption, Language } from '../../types';

defineProps<{
    parentOptions: ParentOption[];
    languages: Language[];
}>();

const { cmsPath } = useCmsPath();

function onSuccess() {
    router.visit(cmsPath('/categories'));
}

function onCancel() {
    router.visit(cmsPath('/categories'));
}
</script>

<template>
    <Head title="Create Category" />

    <DashboardLayout>
        <UDashboardPanel id="categories-create">
            <template #header>
                <UDashboardNavbar title="Create Category">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-xl mx-auto">
                    <CategoryForm
                        :parent-options="parentOptions"
                        :languages="languages"
                        mode="create"
                        @success="onSuccess"
                        @cancel="onCancel"
                    />
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
