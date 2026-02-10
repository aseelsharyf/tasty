<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BadgeForm from '../../components/BadgeForm.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Badge, Language } from '../../types';

const props = defineProps<{
    badge: Badge;
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
    <Head :title="`Edit ${badge.name}`" />

    <DashboardLayout>
        <UDashboardPanel id="badges-edit">
            <template #header>
                <UDashboardNavbar :ui="{ title: 'truncate max-w-[200px] sm:max-w-none' }">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #title>
                        <span class="hidden sm:inline">Edit:</span>
                        <span class="truncate">{{ badge.name }}</span>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-xl mx-auto">
                    <BadgeForm
                        :badge="badge"
                        :languages="languages"
                        mode="edit"
                        @success="onSuccess"
                        @cancel="onCancel"
                    />
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
