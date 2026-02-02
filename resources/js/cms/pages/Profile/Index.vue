<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { NavigationMenuItem } from '@nuxt/ui';

interface ProfileUser {
    id: number;
    uuid: string;
    name: string;
    email: string;
    username: string;
    avatar_url: string | null;
    roles: string[];
    created_at: string;
}

const props = defineProps<{
    tab: string;
    user: ProfileUser;
}>();

const { cmsPath } = useCmsPath();

// Tab navigation - URL based
const activeTab = computed(() => props.tab || 'profile');

const links = computed<NavigationMenuItem[][]>(() => [[
    { label: 'Profile', icon: 'i-lucide-user', to: cmsPath('/profile/profile'), active: activeTab.value === 'profile' },
    { label: 'Avatar', icon: 'i-lucide-image', to: cmsPath('/profile/avatar'), active: activeTab.value === 'avatar' },
    { label: 'Security', icon: 'i-lucide-shield', to: cmsPath('/profile/security'), active: activeTab.value === 'security' },
]]);

// Profile form
const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    username: props.user.username,
});

function updateProfile() {
    profileForm.put(cmsPath('/profile'), {
        preserveScroll: true,
    });
}

// Avatar handling
const avatarPreview = ref<string | null>(null);
const avatarInput = ref<HTMLInputElement>();
const uploadingAvatar = ref(false);
const removingAvatar = ref(false);

const displayAvatar = computed(() => {
    return avatarPreview.value || props.user.avatar_url || null;
});

function triggerAvatarUpload() {
    avatarInput.value?.click();
}

function handleAvatarChange(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Preview
        avatarPreview.value = URL.createObjectURL(file);

        // Upload immediately
        uploadingAvatar.value = true;
        const formData = new FormData();
        formData.append('avatar', file);

        router.post(cmsPath('/profile/avatar'), formData, {
            preserveScroll: true,
            onSuccess: () => {
                avatarPreview.value = null;
            },
            onFinish: () => {
                uploadingAvatar.value = false;
            },
        });
    }
}

function removeAvatar() {
    removingAvatar.value = true;
    router.delete(cmsPath('/profile/avatar'), {
        preserveScroll: true,
        onFinish: () => {
            removingAvatar.value = false;
            avatarPreview.value = null;
        },
    });
}

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);

function updatePassword() {
    passwordForm.put(cmsPath('/profile/password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
}

// Format date
function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Profile" />

    <DashboardLayout>
        <UDashboardPanel id="profile" :ui="{ body: 'lg:py-12' }">
            <template #header>
                <UDashboardNavbar title="Profile">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="links" highlight class="-mx-1 flex-1" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-2xl mx-auto">
                    <!-- Profile Section -->
                    <template v-if="activeTab === 'profile'">
                        <UForm id="profile-form" :state="profileForm" @submit="updateProfile">
                            <UPageCard
                                title="Profile Information"
                                description="Update your account details."
                                variant="naked"
                                orientation="horizontal"
                                class="mb-4"
                            >
                                <UButton
                                    form="profile-form"
                                    label="Save changes"
                                    color="neutral"
                                    type="submit"
                                    :loading="profileForm.processing"
                                    class="w-fit lg:ms-auto"
                                />
                            </UPageCard>

                            <UPageCard variant="subtle">
                                <UFormField
                                    name="name"
                                    label="Name"
                                    description="Your full name."
                                    :error="profileForm.errors.name"
                                    required
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                    :ui="{ container: 'w-full sm:max-w-xs' }"
                                >
                                    <UInput
                                        v-model="profileForm.name"
                                        placeholder="John Doe"
                                        autocomplete="name"
                                        :disabled="profileForm.processing"
                                    />
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="email"
                                    label="Email"
                                    description="Your email address for signing in and notifications."
                                    :error="profileForm.errors.email"
                                    required
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                    :ui="{ container: 'w-full sm:max-w-xs' }"
                                >
                                    <UInput
                                        v-model="profileForm.email"
                                        type="email"
                                        placeholder="john@example.com"
                                        autocomplete="email"
                                        :disabled="profileForm.processing"
                                    />
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="username"
                                    label="Username"
                                    description="Your public URL slug."
                                    :error="profileForm.errors.username"
                                    required
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                    :ui="{ container: 'w-full sm:max-w-xs' }"
                                >
                                    <UInput
                                        v-model="profileForm.username"
                                        placeholder="john-doe"
                                        autocomplete="username"
                                        :disabled="profileForm.processing"
                                    >
                                        <template #leading>
                                            <span class="text-muted">@</span>
                                        </template>
                                    </UInput>
                                    <template #help>
                                        <span class="text-xs text-muted">Only lowercase letters, numbers, and hyphens</span>
                                    </template>
                                </UFormField>

                                <USeparator />

                                <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-highlighted">Role</p>
                                        <p class="text-sm text-muted">Your assigned roles.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <UBadge
                                            v-for="role in user.roles"
                                            :key="role"
                                            color="primary"
                                            variant="subtle"
                                        >
                                            {{ role }}
                                        </UBadge>
                                    </div>
                                </div>

                                <USeparator />

                                <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-highlighted">Member Since</p>
                                        <p class="text-sm text-muted">When you joined.</p>
                                    </div>
                                    <p class="text-sm font-medium text-highlighted">{{ formatDate(user.created_at) }}</p>
                                </div>
                            </UPageCard>
                        </UForm>
                    </template>

                    <!-- Avatar Section -->
                    <template v-if="activeTab === 'avatar'">
                        <UPageCard
                            title="Profile Picture"
                            description="Upload a photo to personalize your account."
                            variant="naked"
                            orientation="horizontal"
                            class="mb-4"
                        />

                        <UPageCard variant="subtle">
                            <div class="flex flex-col items-center gap-6 py-4">
                                <!-- Current Avatar Display -->
                                <div class="relative">
                                    <UAvatar
                                        :src="displayAvatar"
                                        :alt="user.name"
                                        size="3xl"
                                        :ui="{ size: { '3xl': 'size-32 text-4xl' } }"
                                    />
                                    <div
                                        v-if="uploadingAvatar"
                                        class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full"
                                    >
                                        <UIcon name="i-lucide-loader-2" class="size-8 text-white animate-spin" />
                                    </div>
                                </div>

                                <div class="text-center">
                                    <p class="text-sm text-muted mb-4">
                                        Upload a profile picture. Max size: 2MB.<br>
                                        Supported formats: JPG, PNG, GIF, WebP.
                                    </p>

                                    <div class="flex flex-wrap justify-center gap-2">
                                        <UButton
                                            label="Upload Photo"
                                            icon="i-lucide-upload"
                                            color="neutral"
                                            :loading="uploadingAvatar"
                                            @click="triggerAvatarUpload"
                                        />
                                        <UButton
                                            v-if="user.avatar_url && !avatarPreview"
                                            label="Remove"
                                            icon="i-lucide-trash-2"
                                            color="error"
                                            variant="ghost"
                                            :loading="removingAvatar"
                                            @click="removeAvatar"
                                        />
                                    </div>

                                    <input
                                        ref="avatarInput"
                                        type="file"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                        class="hidden"
                                        @change="handleAvatarChange"
                                    />
                                </div>
                            </div>
                        </UPageCard>
                    </template>

                    <!-- Security Section -->
                    <template v-if="activeTab === 'security'">
                        <UForm id="password-form" :state="passwordForm" @submit="updatePassword">
                            <UPageCard
                                title="Change Password"
                                description="Update your password to keep your account secure."
                                variant="naked"
                                orientation="horizontal"
                                class="mb-4"
                            >
                                <UButton
                                    form="password-form"
                                    label="Update Password"
                                    color="neutral"
                                    type="submit"
                                    :loading="passwordForm.processing"
                                    class="w-fit lg:ms-auto"
                                />
                            </UPageCard>

                            <UPageCard variant="subtle">
                                <UFormField
                                    name="current_password"
                                    label="Current Password"
                                    description="Enter your current password to confirm."
                                    :error="passwordForm.errors.current_password"
                                    required
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                    :ui="{ container: 'w-full sm:max-w-xs' }"
                                >
                                    <UInput
                                        v-model="passwordForm.current_password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        placeholder="Enter current password"
                                        autocomplete="current-password"
                                        :disabled="passwordForm.processing"
                                    >
                                        <template #trailing>
                                            <UButton
                                                type="button"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                :icon="showCurrentPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                                @click="showCurrentPassword = !showCurrentPassword"
                                            />
                                        </template>
                                    </UInput>
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="password"
                                    label="New Password"
                                    description="Must be at least 8 characters."
                                    :error="passwordForm.errors.password"
                                    required
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                    :ui="{ container: 'w-full sm:max-w-xs' }"
                                >
                                    <UInput
                                        v-model="passwordForm.password"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        placeholder="Enter new password"
                                        autocomplete="new-password"
                                        :disabled="passwordForm.processing"
                                    >
                                        <template #trailing>
                                            <UButton
                                                type="button"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                :icon="showNewPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                                @click="showNewPassword = !showNewPassword"
                                            />
                                        </template>
                                    </UInput>
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="password_confirmation"
                                    label="Confirm New Password"
                                    description="Re-enter your new password."
                                    :error="passwordForm.errors.password_confirmation"
                                    required
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                    :ui="{ container: 'w-full sm:max-w-xs' }"
                                >
                                    <UInput
                                        v-model="passwordForm.password_confirmation"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        placeholder="Confirm new password"
                                        autocomplete="new-password"
                                        :disabled="passwordForm.processing"
                                    />
                                </UFormField>
                            </UPageCard>
                        </UForm>

                        <!-- Account Security Info -->
                        <UPageCard
                            title="Account Security"
                            description="Additional security information."
                            variant="subtle"
                            class="mt-4"
                        >
                            <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                <div>
                                    <p class="text-sm font-medium text-highlighted">Two-Factor Authentication</p>
                                    <p class="text-sm text-muted">Add an extra layer of security.</p>
                                </div>
                                <UBadge color="neutral" variant="subtle">Coming Soon</UBadge>
                            </div>

                            <USeparator />

                            <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                <div>
                                    <p class="text-sm font-medium text-highlighted">Active Sessions</p>
                                    <p class="text-sm text-muted">Manage your logged-in devices.</p>
                                </div>
                                <UBadge color="neutral" variant="subtle">Coming Soon</UBadge>
                            </div>
                        </UPageCard>
                    </template>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
