<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BadgeForm from '../../components/BadgeForm.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Language } from '../../types';

defineProps<{
    languages: Language[];
}>();

const { cmsPath } = useCmsPath();

function onSuccess() {
    router.visit(cmsPath('/settings/badges'));
}

function onCancel() {
    router.visit(cmsPath('/settings/badges'));
}
</script>

<template>
    <Head title="Create Badge" />

    <DashboardLayout>
        <UDashboardPanel id="badges-create">
            <template #header>
                <UDashboardNavbar title="Create Badge">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-xl mx-auto">
                    <BadgeForm
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
