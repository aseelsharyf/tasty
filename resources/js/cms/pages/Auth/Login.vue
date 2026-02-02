<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import TastyLogo from '../../components/TastyLogo.vue';
import { useCmsPath } from '../../composables/useCmsPath';

const { cmsPath } = useCmsPath();

interface DevUser {
    name: string;
    email: string;
    role: string;
}

const props = defineProps<{
    isLocal?: boolean;
    devUsers?: DevUser[] | null;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const selectedDevUserEmail = ref<string | null>(null);

const roleColors: Record<string, string> = {
    Admin: 'error',
    Developer: 'info',
    Editor: 'success',
    Writer: 'warning',
    Photographer: 'neutral',
};

const roleIcons: Record<string, string> = {
    Admin: 'i-lucide-shield',
    Developer: 'i-lucide-code',
    Editor: 'i-lucide-pen-tool',
    Writer: 'i-lucide-pencil',
    Photographer: 'i-lucide-camera',
};

// Dev user options for dropdown
const devUserOptions = computed(() => {
    return props.devUsers?.map(user => ({
        label: user.name,
        value: user.email,
        suffix: user.role,
        icon: roleIcons[user.role] || 'i-lucide-user',
    })) || [];
});

// Watch for dev user selection and auto-login
watch(selectedDevUserEmail, (email) => {
    if (email) {
        form.email = email;
        form.password = 'password';
        // Auto-submit after a brief delay for visual feedback
        setTimeout(() => {
            onSubmit();
        }, 150);
    }
});

function onSubmit() {
    form.post(cmsPath('/login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <UApp>
        <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-sm space-y-8">
                <!-- Logo and Title -->
                <div class="text-center">
                    <div class="flex justify-center mb-6">
                        <TastyLogo class="h-10 w-auto text-gray-900 dark:text-white" />
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back</h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sign in to your CMS account</p>
                </div>

                <!-- Dev Mode: Quick User Selector Dropdown -->
                <template v-if="isLocal && devUsers?.length">
                    <UCard :ui="{ root: 'border-dashed border-2 border-warning/50 bg-warning/5', body: 'p-4' }">
                        <div class="flex items-center gap-2 mb-3">
                            <UIcon name="i-lucide-zap" class="size-4 text-warning" />
                            <span class="text-xs font-semibold uppercase tracking-wider text-warning">
                                Dev Quick Login
                            </span>
                        </div>

                        <USelectMenu
                            v-model="selectedDevUserEmail"
                            :items="devUserOptions"
                            value-key="value"
                            placeholder="Select a user to login..."
                            icon="i-lucide-user"
                            size="lg"
                            class="w-full"
                            :loading="form.processing"
                        >
                            <template #item="{ item }">
                                <div class="flex items-center gap-3 w-full py-1">
                                    <div
                                        class="flex items-center justify-center size-8 rounded-full"
                                        :class="`bg-${roleColors[item.suffix] || 'neutral'}/10`"
                                    >
                                        <UIcon
                                            :name="item.icon"
                                            class="size-4"
                                            :class="`text-${roleColors[item.suffix] || 'neutral'}`"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium truncate">{{ item.label }}</p>
                                        <p class="text-xs text-muted truncate">{{ item.value }}</p>
                                    </div>
                                    <UBadge
                                        :color="(roleColors[item.suffix] as any) || 'neutral'"
                                        variant="subtle"
                                        size="xs"
                                    >
                                        {{ item.suffix }}
                                    </UBadge>
                                </div>
                            </template>
                        </USelectMenu>

                        <p v-if="form.processing" class="text-xs text-center text-muted mt-3">
                            <UIcon name="i-lucide-loader-2" class="size-3 animate-spin inline mr-1" />
                            Signing in...
                        </p>
                    </UCard>

                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200 dark:border-gray-800" />
                        </div>
                        <div class="relative flex justify-center text-xs uppercase">
                            <span class="bg-gray-50 dark:bg-gray-950 px-2 text-gray-400">
                                or sign in manually
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Form Card -->
                <UCard :ui="{ root: 'shadow-xl shadow-gray-200/50 dark:shadow-none', body: 'p-6 sm:p-8' }">
                    <form @submit.prevent="onSubmit" class="space-y-5">
                        <UFormField label="Email address" name="email" :error="form.errors.email">
                            <UInput
                                v-model="form.email"
                                type="email"
                                placeholder="you@example.com"
                                icon="i-lucide-mail"
                                size="lg"
                                :disabled="form.processing"
                                autofocus
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField label="Password" name="password" :error="form.errors.password">
                            <UInput
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Enter your password"
                                icon="i-lucide-lock"
                                size="lg"
                                :disabled="form.processing"
                                class="w-full"
                            >
                                <template #trailing>
                                    <UButton
                                        type="button"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                        @click="showPassword = !showPassword"
                                        :padded="false"
                                    />
                                </template>
                            </UInput>
                        </UFormField>

                        <div class="flex items-center justify-between">
                            <UCheckbox
                                v-model="form.remember"
                                label="Remember me"
                                :ui="{ label: 'text-sm text-gray-600 dark:text-gray-400' }"
                            />
                        </div>

                        <UButton
                            type="submit"
                            size="lg"
                            block
                            :loading="form.processing"
                            class="mt-6"
                        >
                            Sign in
                        </UButton>
                    </form>
                </UCard>

                <!-- Color Mode Toggle -->
                <div class="flex justify-center">
                    <UColorModeButton color="neutral" variant="ghost" size="lg" />
                </div>
            </div>
        </div>
    </UApp>
</template>
