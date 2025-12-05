<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '../../layouts/DashboardLayout.vue';

const props = defineProps<{
    settings: {
        app_name: string;
        app_url: string;
    };
}>();

const form = useForm({
    app_name: props.settings.app_name,
    app_url: props.settings.app_url,
});

function onSubmit() {
    form.put('/cms/settings');
}
</script>

<template>
    <Head title="Settings" />

    <DashboardLayout>
        <UDashboardPanel id="settings" :ui="{ body: 'lg:py-12' }">
            <template #header>
                <UDashboardNavbar title="Settings">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-2xl mx-auto">
                    <UForm
                        id="settings"
                        :state="form"
                        @submit="onSubmit"
                    >
                        <UPageCard
                            title="General"
                            description="Manage your application settings."
                            variant="naked"
                            orientation="horizontal"
                            class="mb-4"
                        >
                            <UButton
                                form="settings"
                                label="Save changes"
                                color="neutral"
                                type="submit"
                                :loading="form.processing"
                                class="w-fit lg:ms-auto"
                            />
                        </UPageCard>

                        <UPageCard variant="subtle">
                            <UFormField
                                name="app_name"
                                label="Application Name"
                                description="The name of your application shown in the browser tab and emails."
                                :error="form.errors.app_name"
                                required
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <UInput
                                    v-model="form.app_name"
                                    autocomplete="off"
                                    :disabled="form.processing"
                                />
                            </UFormField>
                            <USeparator />
                            <UFormField
                                name="app_url"
                                label="Application URL"
                                description="The URL of your application. Used for generating links."
                                :error="form.errors.app_url"
                                required
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <UInput
                                    v-model="form.app_url"
                                    type="url"
                                    autocomplete="off"
                                    :disabled="form.processing"
                                />
                            </UFormField>
                        </UPageCard>
                    </UForm>

                    <UPageCard
                        title="System Information"
                        description="Technical details about your installation."
                        variant="subtle"
                    >
                        <div class="flex max-sm:flex-col justify-between items-start gap-4">
                            <div>
                                <p class="text-sm font-medium text-highlighted">Laravel Version</p>
                                <p class="text-sm text-muted">The framework version.</p>
                            </div>
                            <div class="text-sm font-medium text-highlighted sm:text-right">
                                12.x
                            </div>
                        </div>
                        <USeparator />
                        <div class="flex max-sm:flex-col justify-between items-start gap-4">
                            <div>
                                <p class="text-sm font-medium text-highlighted">PHP Version</p>
                                <p class="text-sm text-muted">The PHP runtime version.</p>
                            </div>
                            <div class="text-sm font-medium text-highlighted sm:text-right">
                                8.4
                            </div>
                        </div>
                        <USeparator />
                        <div class="flex max-sm:flex-col justify-between items-start gap-4">
                            <div>
                                <p class="text-sm font-medium text-highlighted">Environment</p>
                                <p class="text-sm text-muted">Current running environment.</p>
                            </div>
                            <UBadge color="success" variant="subtle">
                                Local
                            </UBadge>
                        </div>
                    </UPageCard>

                    <UPageCard
                        title="Quick Links"
                        description="Helpful resources and documentation."
                        variant="subtle"
                    >
                        <div class="flex flex-wrap gap-2">
                            <UButton
                                href="/"
                                target="_blank"
                                color="neutral"
                                variant="soft"
                                icon="i-lucide-external-link"
                            >
                                View Website
                            </UButton>
                            <UButton
                                href="https://laravel.com/docs"
                                target="_blank"
                                color="neutral"
                                variant="soft"
                                icon="i-lucide-book-open"
                            >
                                Laravel Docs
                            </UButton>
                            <UButton
                                href="https://ui.nuxt.com"
                                target="_blank"
                                color="neutral"
                                variant="soft"
                                icon="i-lucide-palette"
                            >
                                Nuxt UI Docs
                            </UButton>
                        </div>
                    </UPageCard>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
