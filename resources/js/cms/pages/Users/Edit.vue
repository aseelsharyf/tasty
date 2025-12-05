<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import type { User, Role } from '../../types';
import type { BreadcrumbItem } from '@nuxt/ui';

const props = defineProps<{
    user: User;
    roles: Role[];
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    username: props.user.username,
    password: '',
    password_confirmation: '',
    avatar: null as File | null,
    remove_avatar: false,
    roles: [...(props.user.roles || [])],
});

const roleOptions = computed(() => {
    return props.roles.map((role) => ({
        label: role.name,
        value: role.name,
    }));
});

const showPassword = ref(false);
const avatarPreview = ref<string | null>(null);
const avatarRemoved = ref(false);

const displayAvatar = computed(() => {
    if (avatarRemoved.value) return null;
    return avatarPreview.value || props.user.avatar_url || null;
});

const hasExistingAvatar = computed(() => {
    return props.user.avatar_url && !avatarRemoved.value && !avatarPreview.value;
});

function handleAvatarChange(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        form.avatar = input.files[0];
        form.remove_avatar = false;
        avatarPreview.value = URL.createObjectURL(input.files[0]);
        avatarRemoved.value = false;
    }
}

function removeAvatar() {
    form.avatar = null;
    avatarPreview.value = null;
    if (props.user.avatar_url) {
        form.remove_avatar = true;
        avatarRemoved.value = true;
    }
}

function undoRemoveAvatar() {
    form.remove_avatar = false;
    avatarRemoved.value = false;
}

function onSubmit() {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/cms/users/${props.user.uuid}`, {
        forceFormData: true,
    });
}

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Users', to: '/cms/users' },
    { label: props.user.name },
];
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <DashboardLayout>
        <UDashboardPanel id="users-edit" :ui="{ body: 'lg:py-12' }">
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
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-2xl mx-auto">
                    <UForm
                        id="edit-user"
                        :state="form"
                        @submit="onSubmit"
                    >
                        <UPageCard
                            variant="naked"
                            orientation="horizontal"
                            class="mb-4"
                        >
                            <div class="flex items-center gap-4">
                                <UAvatar :src="displayAvatar" :alt="user.name" size="lg" />
                                <div>
                                    <h1 class="text-xl font-semibold text-highlighted">{{ user.name }}</h1>
                                    <p class="text-muted text-sm">{{ user.email }}</p>
                                </div>
                            </div>

                            <div class="flex gap-2 lg:ms-auto">
                                <UButton
                                    :to="'/cms/users'"
                                    color="neutral"
                                    variant="ghost"
                                >
                                    Cancel
                                </UButton>
                                <UButton
                                    form="edit-user"
                                    type="submit"
                                    :loading="form.processing"
                                >
                                    Save Changes
                                </UButton>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Avatar"
                            description="Upload a profile picture."
                            variant="subtle"
                            class="mb-4"
                        >
                            <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                <div class="flex-1">
                                    <p class="text-sm text-muted">
                                        Upload a profile picture. Max size: 2MB. Supported formats: JPG, PNG, GIF.
                                    </p>
                                </div>
                                <div class="w-full sm:max-w-xs">
                                    <div class="flex items-center gap-4">
                                        <UAvatar :src="displayAvatar" :alt="user.name" size="xl" />
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
                                                v-if="avatarPreview || hasExistingAvatar"
                                                color="error"
                                                variant="ghost"
                                                size="xs"
                                                icon="i-lucide-trash"
                                                @click="removeAvatar"
                                            >
                                                Remove
                                            </UButton>
                                            <UButton
                                                v-if="avatarRemoved"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                icon="i-lucide-undo"
                                                @click="undoRemoveAvatar"
                                            >
                                                Undo
                                            </UButton>
                                        </div>
                                    </div>
                                    <p v-if="avatarRemoved" class="text-warning text-sm mt-2">
                                        Avatar will be removed when you save.
                                    </p>
                                    <p v-if="form.errors.avatar" class="text-error text-sm mt-2">
                                        {{ form.errors.avatar }}
                                    </p>
                                </div>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Profile"
                            description="Update user profile information."
                            variant="subtle"
                            class="mb-4"
                        >
                            <UFormField
                                name="name"
                                label="Name"
                                description="The user's full name."
                                :error="form.errors.name"
                                required
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <UInput
                                    v-model="form.name"
                                    placeholder="John Doe"
                                    autocomplete="off"
                                    :disabled="form.processing"
                                />
                            </UFormField>
                            <USeparator />
                            <UFormField
                                name="email"
                                label="Email"
                                description="Used to sign in and receive notifications."
                                :error="form.errors.email"
                                required
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <UInput
                                    v-model="form.email"
                                    type="email"
                                    placeholder="john@example.com"
                                    autocomplete="off"
                                    :disabled="form.processing"
                                />
                            </UFormField>
                            <USeparator />
                            <UFormField
                                name="username"
                                label="Username"
                                description="SEO-friendly URL slug."
                                :error="form.errors.username"
                                required
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <UInput
                                    v-model="form.username"
                                    placeholder="john-doe"
                                    autocomplete="off"
                                    :disabled="form.processing"
                                >
                                    <template #leading>
                                        <span class="text-muted">@</span>
                                    </template>
                                </UInput>
                                <template #hint>
                                    <span class="text-xs text-muted">Only lowercase letters, numbers, and hyphens</span>
                                </template>
                            </UFormField>
                        </UPageCard>

                        <UPageCard
                            title="Roles"
                            description="Manage user roles and permissions."
                            variant="subtle"
                            class="mb-4"
                        >
                            <UFormField
                                name="roles"
                                label="Assigned Roles"
                                description="Select the roles for this user."
                                :error="form.errors.roles"
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <USelectMenu
                                    v-model="form.roles"
                                    :items="roleOptions"
                                    multiple
                                    placeholder="Select roles..."
                                    value-key="value"
                                    :disabled="form.processing"
                                />
                            </UFormField>
                        </UPageCard>

                        <UPageCard
                            title="Security"
                            description="Update password. Leave blank to keep current password."
                            variant="subtle"
                        >
                            <UFormField
                                name="password"
                                label="New Password"
                                description="Must be at least 8 characters."
                                :error="form.errors.password"
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <UInput
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Enter new password"
                                    autocomplete="new-password"
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
                            <USeparator />
                            <UFormField
                                name="password_confirmation"
                                label="Confirm Password"
                                description="Re-enter the new password."
                                :error="form.errors.password_confirmation"
                                class="flex max-sm:flex-col justify-between items-start gap-4"
                                :ui="{ container: 'w-full sm:max-w-xs' }"
                            >
                                <UInput
                                    v-model="form.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Confirm new password"
                                    autocomplete="new-password"
                                    :disabled="form.processing"
                                />
                            </UFormField>
                        </UPageCard>
                    </UForm>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
