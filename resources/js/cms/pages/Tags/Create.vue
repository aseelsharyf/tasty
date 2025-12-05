<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import TagForm from '../../components/TagForm.vue';

function onSuccess() {
    router.visit('/cms/tags');
}

function onCancel() {
    router.visit('/cms/tags');
}
</script>

<template>
    <Head title="Create Tag" />

    <DashboardLayout>
        <UDashboardPanel id="tags-create">
            <template #header>
                <UDashboardNavbar title="Create Tag">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-xl mx-auto">
                    <TagForm
                        mode="create"
                        @success="onSuccess"
                        @cancel="onCancel"
                    />
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
