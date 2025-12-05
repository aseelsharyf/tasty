<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TastyLogo from '../../components/TastyLogo.vue';

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
    password: 'password',
    remember: false,
});

const showPassword = ref(false);

const selectedUser = computed({
    get: () => props.devUsers?.find((u) => u.email === form.email) ?? null,
    set: (user: DevUser | null) => {
        if (user) {
            form.email = user.email;
            form.password = 'password';
        }
    },
});

const roleColors: Record<string, string> = {
    Admin: 'error',
    Developer: 'info',
    Editor: 'success',
    Writer: 'warning',
    Photographer: 'secondary',
};

function onSubmit() {
    form.post('/cms/login', {
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

                <!-- Dev User Switcher -->
                <div v-if="isLocal && devUsers?.length" class="space-y-2">
                    <p class="text-xs font-medium text-center text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Quick Login (Dev Only)
                    </p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <UButton
                            v-for="user in devUsers"
                            :key="user.email"
                            size="xs"
                            :color="selectedUser?.email === user.email ? (roleColors[user.role] as any) : 'neutral'"
                            :variant="selectedUser?.email === user.email ? 'solid' : 'outline'"
                            @click="selectedUser = user"
                        >
                            {{ user.role }}
                        </UButton>
                    </div>
                </div>

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
