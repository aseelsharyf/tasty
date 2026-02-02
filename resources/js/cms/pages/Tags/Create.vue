<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import TagForm from '../../components/TagForm.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Language } from '../../types';

defineProps<{
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
