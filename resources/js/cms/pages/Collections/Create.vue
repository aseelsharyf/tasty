<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CollectionForm from '../../components/CollectionForm.vue';
import type { Language } from '../../types';

defineProps<{
    languages: Language[];
    allCategories: { id: number; name: string; slug: string }[];
    allTags: { id: number; name: string; slug: string }[];
}>();
</script>

<template>
    <Head title="Create Collection" />

    <DashboardLayout>
        <UDashboardPanel id="collection-create">
            <template #header>
                <UDashboardNavbar>
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <CollectionForm
                    :languages="languages"
                    :all-categories="allCategories"
                    :all-tags="allTags"
                    mode="create"
                />
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
