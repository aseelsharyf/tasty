<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import CollectionForm from '../../components/CollectionForm.vue';
import type { Language, Collection } from '../../types';

defineProps<{
    collection: Collection;
    languages: Language[];
    allCategories: { id: number; name: string; slug: string }[];
    allTags: { id: number; name: string; slug: string }[];
}>();
</script>

<template>
    <Head :title="`Edit: ${collection.name}`" />

    <DashboardLayout>
        <UDashboardPanel id="collection-edit">
            <template #header>
                <UDashboardNavbar>
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <CollectionForm
                    :collection="collection"
                    :languages="languages"
                    :all-categories="allCategories"
                    :all-tags="allTags"
                    mode="edit"
                />
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
