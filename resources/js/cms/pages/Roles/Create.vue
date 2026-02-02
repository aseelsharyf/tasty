<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { GroupedPermissions } from '../../types';
import type { BreadcrumbItem } from '@nuxt/ui';

const props = defineProps<{
    permissions: GroupedPermissions;
}>();

const { cmsPath } = useCmsPath();

const form = useForm({
    name: '',
    permissions: [] as string[],
});

function onSubmit() {
    form.post(cmsPath('/roles'));
}

function togglePermission(permissionName: string) {
    const index = form.permissions.indexOf(permissionName);
    if (index === -1) {
        form.permissions.push(permissionName);
    } else {
        form.permissions.splice(index, 1);
    }
}

function toggleModule(module: string) {
    const modulePermissions = props.permissions[module].map((p) => p.name);
    const allSelected = modulePermissions.every((p) => form.permissions.includes(p));

    if (allSelected) {
        form.permissions = form.permissions.filter((p) => !modulePermissions.includes(p));
    } else {
        const newPermissions = modulePermissions.filter((p) => !form.permissions.includes(p));
        form.permissions.push(...newPermissions);
    }
}

function isModuleFullySelected(module: string): boolean {
    const modulePermissions = props.permissions[module].map((p) => p.name);
    return modulePermissions.every((p) => form.permissions.includes(p));
}

function isModulePartiallySelected(module: string): boolean {
    const modulePermissions = props.permissions[module].map((p) => p.name);
    const selectedCount = modulePermissions.filter((p) => form.permissions.includes(p)).length;
    return selectedCount > 0 && selectedCount < modulePermissions.length;
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { label: 'Roles', to: cmsPath('/roles') },
    { label: 'Create' },
]);
</script>

<template>
    <Head title="Create Role" />

    <DashboardLayout>
        <UDashboardPanel id="roles-create" :ui="{ body: 'lg:py-12' }">
            <template #header>
                <UDashboardNavbar>
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>

                    <template #title>
                        <UBreadcrumb :items="breadcrumbs" />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-3xl mx-auto">
                    <UForm
                        id="create-role"
                        :state="form"
                        @submit="onSubmit"
                    >
                        <UPageCard
                            title="Create Role"
                            description="Define a new role with specific permissions."
                            variant="naked"
                            orientation="horizontal"
                            class="mb-4"
                        >
                            <div class="flex gap-2 lg:ms-auto">
                                <UButton
                                    :to="cmsPath('/roles')"
                                    color="neutral"
                                    variant="ghost"
                                >
                                    Cancel
                                </UButton>
                                <UButton
                                    form="create-role"
                                    type="submit"
                                    :loading="form.processing"
                                >
                                    Create Role
                                </UButton>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Role Details"
                            description="Basic information about the role."
                            variant="subtle"
                            class="mb-4"
                        >
                            <UFormField
                                name="name"
                                label="Role Name"
                                description="A unique name for this role."
                                :error="form.errors.name"
                                required
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <UInput
                                    v-model="form.name"
                                    placeholder="e.g., Content Manager"
                                    autocomplete="off"
                                    :disabled="form.processing"
                                />
                            </UFormField>
                        </UPageCard>

                        <UPageCard
                            title="Permissions"
                            description="Select the permissions this role should have."
                            variant="subtle"
                        >
                            <div class="space-y-6">
                                <div
                                    v-for="(perms, module) in permissions"
                                    :key="module"
                                    class="space-y-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <UCheckbox
                                            :model-value="isModuleFullySelected(module as string)"
                                            :indeterminate="isModulePartiallySelected(module as string)"
                                            @update:model-value="toggleModule(module as string)"
                                        />
                                        <span class="font-medium text-highlighted capitalize">
                                            {{ module }}
                                        </span>
                                    </div>
                                    <div class="ml-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                                        <div
                                            v-for="permission in perms"
                                            :key="permission.name"
                                            class="flex items-center gap-2"
                                        >
                                            <UCheckbox
                                                :model-value="form.permissions.includes(permission.name)"
                                                @update:model-value="togglePermission(permission.name)"
                                            />
                                            <span class="text-sm text-muted">
                                                {{ permission.label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p v-if="form.errors.permissions" class="text-error text-sm mt-4">
                                {{ form.errors.permissions }}
                            </p>
                        </UPageCard>
                    </UForm>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
