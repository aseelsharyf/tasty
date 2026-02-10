<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { User, Role, Badge } from '../../types';
import type { BreadcrumbItem } from '@nuxt/ui';

interface UserTypeOption {
    value: string;
    label: string;
}

interface AvailableBadge {
    id: number;
    name: string;
    slug: string;
    icon?: string | null;
    color: string;
}

const props = defineProps<{
    user: User;
    roles: Role[];
    availableBadges: AvailableBadge[];
    userTypes: UserTypeOption[];
}>();

const { cmsPath } = useCmsPath();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    username: props.user.username,
    password: '',
    password_confirmation: '',
    avatar: null as File | null,
    remove_avatar: false,
    roles: [...(props.user.roles || [])],
    type: props.user.type || 'user',
    badges: [...(props.user.badges || [])] as number[],
});

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

const badgeOptions = computed(() => {
    return props.availableBadges.map((badge) => ({
        label: badge.name,
        value: badge.id,
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
    })).post(cmsPath(`/users/${props.user.uuid}`), {
        forceFormData: true,
    });
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { label: 'Users', to: cmsPath('/users') },
    { label: props.user.name },
]);
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
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-8 w-full lg:max-w-2xl mx-auto">
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
                                    :to="cmsPath('/users')"
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
                        </UPageCard>

                        <UPageCard
                            title="Profile"
                            description="Update user profile information."
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
                                    required
                                >
                                    <UInput
                                        v-model="form.username"
                                        placeholder="john-doe"
                                        autocomplete="off"
                                        class="w-full"
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
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Type & Roles"
                            description="Manage user type, roles, and permissions."
                            variant="subtle"
                            class="mb-4"
                        >
                            <div class="flex flex-col gap-4">
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
                                        label="Assigned Roles"
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

                                <UFormField
                                    v-if="availableBadges.length > 0"
                                    name="badges"
                                    label="Assigned Badges"
                                    :error="form.errors.badges"
                                >
                                    <USelectMenu
                                        v-model="form.badges"
                                        :items="badgeOptions"
                                        multiple
                                        placeholder="Select badges..."
                                        value-key="value"
                                        class="w-full"
                                        :disabled="form.processing"
                                    />
                                </UFormField>
                            </div>
                        </UPageCard>

                        <UPageCard
                            v-if="user.google_id"
                            title="Connected Accounts"
                            description="External accounts linked to this user."
                            variant="subtle"
                            class="mb-4"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 bg-white rounded-lg border border-default">
                                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-highlighted">Google</p>
                                        <p class="text-sm text-muted">Connected via Google Sign-In</p>
                                    </div>
                                </div>
                                <UBadge color="success" variant="subtle">Connected</UBadge>
                            </div>
                        </UPageCard>

                        <UPageCard
                            title="Security"
                            description="Update password. Leave blank to keep current password."
                            variant="subtle"
                        >
                            <div class="flex flex-col gap-4">
                                <UFormField
                                    name="password"
                                    label="New Password"
                                    :error="form.errors.password"
                                >
                                    <UInput
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Enter new password"
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
                                >
                                    <UInput
                                        v-model="form.password_confirmation"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Confirm new password"
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
