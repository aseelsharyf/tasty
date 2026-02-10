<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Role } from '../../types';
import type { BreadcrumbItem } from '@nuxt/ui';

interface UserTypeOption {
    value: string;
    label: string;
}

const props = defineProps<{
    roles: Role[];
    userTypes: UserTypeOption[];
}>();

const { cmsPath } = useCmsPath();

const form = useForm({
    name: '',
    email: '',
    username: '',
    password: '',
    password_confirmation: '',
    avatar: null as File | null,
    roles: [] as string[],
    type: 'staff',
});

const usernameManuallyEdited = ref(false);

function slugify(text: string): string {
    return text
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

watch(() => form.name, (newName) => {
    if (!usernameManuallyEdited.value) {
        form.username = slugify(newName);
    }
});

function onUsernameInput() {
    usernameManuallyEdited.value = true;
}

const roleOptions = computed(() => {
    return props.roles.map((role) => ({
        label: role.name,
        value: role.name,
    }));
});

const typeOptions = computed(() => {
    return props.userTypes.map((type) => ({
        label: type.label,
        value: type.value,
    }));
});

const showPassword = ref(false);
const avatarPreview = ref<string | null>(null);

function handleAvatarChange(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        form.avatar = input.files[0];
        avatarPreview.value = URL.createObjectURL(input.files[0]);
    }
}

function removeAvatar() {
    form.avatar = null;
    avatarPreview.value = null;
}

function onSubmit() {
    form.post(cmsPath('/users'), {
        forceFormData: true,
    });
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { label: 'Users', to: cmsPath('/users') },
    { label: 'Create' },
]);
</script>

<template>
    <Head title="Create User" />

    <DashboardLayout>
        <UDashboardPanel id="users-create" :ui="{ body: 'lg:py-12' }">
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
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-8 w-full lg:max-w-2xl mx-auto">
                    <UForm
                        id="create-user"
                        :state="form"
                        @submit="onSubmit"
                    >
                        <UPageCard
                            title="Create User"
                            description="Add a new user to the system."
                            variant="naked"
                            orientation="horizontal"
                            class="mb-4"
                        >
                            <div class="flex gap-2 lg:ms-auto">
                                <UButton
                                    :to="cmsPath('/users')"
                                    color="neutral"
                                    variant="ghost"
                                >
                                    Cancel
                                </UButton>
                                <UButton
                                    form="create-user"
                                    type="submit"
                                    :loading="form.processing"
                                >
                                    Create User
                                </UButton>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Avatar"
                            description="Upload a profile picture."
                            variant="subtle"
                            class="mb-4"
                        >
                            <div class="flex items-center gap-4">
                                <UAvatar :src="avatarPreview" alt="Avatar preview" size="xl" />
                                <div class="flex flex-col gap-2">
                                    <label>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="handleAvatarChange"
                                        >
                                        <UButton
                                            as="span"
                                            color="neutral"
                                            variant="outline"
                                            size="xs"
                                            icon="i-lucide-upload"
                                            class="cursor-pointer"
                                        >
                                            Upload
                                        </UButton>
                                    </label>
                                    <UButton
                                        v-if="avatarPreview"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                        icon="i-lucide-x"
                                        @click="removeAvatar"
                                    >
                                        Remove
                                    </UButton>
                                </div>
                            </div>
                            <p v-if="form.errors.avatar" class="text-error text-sm mt-2">
                                {{ form.errors.avatar }}
                            </p>
                        </UPageCard>

                        <UPageCard
                            title="Profile"
                            description="Basic user information."
                            variant="subtle"
                            class="mb-4"
                        >
                            <div class="flex flex-col gap-4">
                                <UFormField
                                    name="name"
                                    label="Name"
                                    :error="form.errors.name"
                                    required
                                >
                                    <UInput
                                        v-model="form.name"
                                        placeholder="John Doe"
                                        autocomplete="off"
                                        class="w-full"
                                        :disabled="form.processing"
                                    />
                                </UFormField>

                                <UFormField
                                    name="email"
                                    label="Email"
                                    :error="form.errors.email"
                                    required
                                >
                                    <UInput
                                        v-model="form.email"
                                        type="email"
                                        placeholder="john@example.com"
                                        autocomplete="off"
                                        class="w-full"
                                        :disabled="form.processing"
                                    />
                                </UFormField>

                                <UFormField
                                    name="username"
                                    label="Username"
                                    :error="form.errors.username"
                                >
                                    <UInput
                                        v-model="form.username"
                                        placeholder="john-doe"
                                        autocomplete="off"
                                        class="w-full"
                                        :disabled="form.processing"
                                        @input="onUsernameInput"
                                    >
                                        <template #leading>
                                            <span class="text-muted">@</span>
                                        </template>
                                    </UInput>
                                    <template #hint>
                                        <span class="text-xs text-muted">Auto-generated from name. Only lowercase letters, numbers, and hyphens.</span>
                                    </template>
                                </UFormField>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Type & Roles"
                            description="Classify and assign permissions."
                            variant="subtle"
                            class="mb-4"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <UFormField
                                    name="type"
                                    label="User Type"
                                    :error="form.errors.type"
                                >
                                    <USelectMenu
                                        v-model="form.type"
                                        :items="typeOptions"
                                        placeholder="Select type..."
                                        value-key="value"
                                        class="w-full"
                                        :disabled="form.processing"
                                    />
                                </UFormField>

                                <UFormField
                                    name="roles"
                                    label="Roles"
                                    :error="form.errors.roles"
                                >
                                    <USelectMenu
                                        v-model="form.roles"
                                        :items="roleOptions"
                                        multiple
                                        placeholder="Select roles..."
                                        value-key="value"
                                        class="w-full"
                                        :disabled="form.processing"
                                    />
                                </UFormField>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Security"
                            description="Set the user's password."
                            variant="subtle"
                        >
                            <div class="flex flex-col gap-4">
                                <UFormField
                                    name="password"
                                    label="Password"
                                    :error="form.errors.password"
                                    required
                                >
                                    <UInput
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Enter password"
                                        autocomplete="new-password"
                                        class="w-full"
                                        :disabled="form.processing"
                                    >
                                        <template #trailing>
                                            <UButton
                                                type="button"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                                @click="showPassword = !showPassword"
                                            />
                                        </template>
                                    </UInput>
                                </UFormField>

                                <UFormField
                                    name="password_confirmation"
                                    label="Confirm Password"
                                    :error="form.errors.password_confirmation"
                                    required
                                >
                                    <UInput
                                        v-model="form.password_confirmation"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Confirm password"
                                        autocomplete="new-password"
                                        class="w-full"
                                        :disabled="form.processing"
                                    />
                                </UFormField>
                            </div>
                        </UPageCard>
                    </UForm>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
