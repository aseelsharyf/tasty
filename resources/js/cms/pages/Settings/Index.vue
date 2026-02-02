<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import type { NavigationMenuItem } from '@nuxt/ui';
import { useSettingsNav } from '../../composables/useSettingsNav';
import { useCmsPath } from '../../composables/useCmsPath';

const props = defineProps<{
    tab: string;
    settings: {
        site_name: string;
        site_tagline: string;
        app_name: string;
        app_url: string;
        meta_keywords: string;
        meta_description: string;
        og_title: string;
        og_description: string;
        og_image: string;
        favicon: string;
        favicon_16: string;
        favicon_32: string;
        apple_touch_icon: string;
        social_facebook: string;
        social_twitter: string;
        social_instagram: string;
        social_youtube: string;
        social_tiktok: string;
        social_linkedin: string;
    };
}>();

const activeTab = computed(() => props.tab || 'general');

const { cmsPath } = useCmsPath();

// Main settings pages navigation
const { mainNav } = useSettingsNav();

// Sub-tabs for general settings
const links = computed<NavigationMenuItem[][]>(() => [[
    { label: 'Site Info', to: cmsPath('/settings/general'), active: activeTab.value === 'general' },
    { label: 'SEO & Meta', to: cmsPath('/settings/seo'), active: activeTab.value === 'seo' },
    { label: 'OpenGraph', to: cmsPath('/settings/opengraph'), active: activeTab.value === 'opengraph' },
    { label: 'Favicons', to: cmsPath('/settings/favicons'), active: activeTab.value === 'favicons' },
    { label: 'Social Links', to: cmsPath('/settings/social'), active: activeTab.value === 'social' },
]]);

const form = useForm({
    // Tab tracking for redirect
    _tab: props.tab || 'general',
    // General
    site_name: props.settings.site_name || '',
    site_tagline: props.settings.site_tagline || '',
    // SEO
    meta_keywords: props.settings.meta_keywords || '',
    meta_description: props.settings.meta_description || '',
    // OpenGraph
    og_title: props.settings.og_title || '',
    og_description: props.settings.og_description || '',
    og_image: null as File | null,
    // Favicons
    favicon: null as File | null,
    favicon_16: null as File | null,
    favicon_32: null as File | null,
    apple_touch_icon: null as File | null,
    // Social
    social_facebook: props.settings.social_facebook || '',
    social_twitter: props.settings.social_twitter || '',
    social_instagram: props.settings.social_instagram || '',
    social_youtube: props.settings.social_youtube || '',
    social_tiktok: props.settings.social_tiktok || '',
    social_linkedin: props.settings.social_linkedin || '',
    // Removals
    remove_og_image: false,
    remove_favicon: false,
    remove_favicon_16: false,
    remove_favicon_32: false,
    remove_apple_touch_icon: false,
});

// Keep form's _tab in sync with active tab when navigating
watch(activeTab, (newTab) => {
    form._tab = newTab;
});

// File input refs
const ogImageRef = ref<HTMLInputElement>();
const faviconRef = ref<HTMLInputElement>();
const favicon16Ref = ref<HTMLInputElement>();
const favicon32Ref = ref<HTMLInputElement>();
const appleTouchIconRef = ref<HTMLInputElement>();

// Preview URLs for uploaded files
const ogImagePreview = ref<string | null>(null);
const faviconPreview = ref<string | null>(null);
const favicon16Preview = ref<string | null>(null);
const favicon32Preview = ref<string | null>(null);
const appleTouchIconPreview = ref<string | null>(null);

// Existing file URLs
const existingOgImage = computed(() => props.settings.og_image ? `/storage/${props.settings.og_image}` : null);
const existingFavicon = computed(() => props.settings.favicon ? `/storage/${props.settings.favicon}` : null);
const existingFavicon16 = computed(() => props.settings.favicon_16 ? `/storage/${props.settings.favicon_16}` : null);
const existingFavicon32 = computed(() => props.settings.favicon_32 ? `/storage/${props.settings.favicon_32}` : null);
const existingAppleTouchIcon = computed(() => props.settings.apple_touch_icon ? `/storage/${props.settings.apple_touch_icon}` : null);

function handleFileChange(event: Event, field: 'og_image' | 'favicon' | 'favicon_16' | 'favicon_32' | 'apple_touch_icon') {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file) {
        form[field] = file;
        const previewMap = {
            og_image: ogImagePreview,
            favicon: faviconPreview,
            favicon_16: favicon16Preview,
            favicon_32: favicon32Preview,
            apple_touch_icon: appleTouchIconPreview,
        };
        previewMap[field].value = URL.createObjectURL(file);

        // Reset removal flag
        const removeField = `remove_${field}` as keyof typeof form;
        if (removeField in form) {
            (form as any)[removeField] = false;
        }
    }
}

function removeFile(field: 'og_image' | 'favicon' | 'favicon_16' | 'favicon_32' | 'apple_touch_icon') {
    form[field] = null;
    const previewMap = {
        og_image: ogImagePreview,
        favicon: faviconPreview,
        favicon_16: favicon16Preview,
        favicon_32: favicon32Preview,
        apple_touch_icon: appleTouchIconPreview,
    };
    previewMap[field].value = null;

    const removeField = `remove_${field}` as keyof typeof form;
    if (removeField in form) {
        (form as any)[removeField] = true;
    }
}

function onSubmit() {
    form.post(cmsPath('/settings'), {
        forceFormData: true,
        preserveScroll: true,
    });
}

const socialLinks = [
    { key: 'social_facebook', label: 'Facebook', icon: 'i-lucide-facebook', placeholder: 'https://facebook.com/yourpage', description: 'Your Facebook page URL.' },
    { key: 'social_twitter', label: 'X (Twitter)', icon: 'i-lucide-twitter', placeholder: 'https://x.com/yourhandle', description: 'Your X/Twitter profile URL.' },
    { key: 'social_instagram', label: 'Instagram', icon: 'i-lucide-instagram', placeholder: 'https://instagram.com/yourhandle', description: 'Your Instagram profile URL.' },
    { key: 'social_youtube', label: 'YouTube', icon: 'i-lucide-youtube', placeholder: 'https://youtube.com/@yourchannel', description: 'Your YouTube channel URL.' },
    { key: 'social_tiktok', label: 'TikTok', icon: 'i-lucide-music-2', placeholder: 'https://tiktok.com/@yourhandle', description: 'Your TikTok profile URL.' },
    { key: 'social_linkedin', label: 'LinkedIn', icon: 'i-lucide-linkedin', placeholder: 'https://linkedin.com/company/yourcompany', description: 'Your LinkedIn page URL.' },
] as const;
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

                <UDashboardToolbar>
                    <UNavigationMenu :items="mainNav" highlight class="-mx-1 flex-1 overflow-x-auto" />
                </UDashboardToolbar>

                <UDashboardToolbar class="border-t-0 bg-elevated/50">
                    <UNavigationMenu :items="links" highlight variant="pill" class="-mx-1 flex-1" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-2xl mx-auto">
                    <UForm id="settings-form" :state="form" @submit="onSubmit">
                        <!-- General Section -->
                        <template v-if="activeTab === 'general'">
                            <UPageCard
                                title="General"
                                description="Basic site information and configuration."
                                variant="naked"
                                orientation="horizontal"
                                class="mb-4"
                            >
                                <UButton
                                    form="settings-form"
                                    label="Save changes"
                                    color="neutral"
                                    type="submit"
                                    :loading="form.processing"
                                    class="w-fit lg:ms-auto"
                                />
                            </UPageCard>

                            <UPageCard variant="subtle">
                                <UFormField
                                    name="site_name"
                                    label="Site Name"
                                    description="The public name of your site."
                                    :error="form.errors.site_name"
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                >
                                    <UInput
                                        v-model="form.site_name"
                                        placeholder="My Awesome Site"
                                        autocomplete="off"
                                        :disabled="form.processing"
                                    />
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="site_tagline"
                                    label="Tagline"
                                    description="A short description or slogan for your site."
                                    :error="form.errors.site_tagline"
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                >
                                    <UInput
                                        v-model="form.site_tagline"
                                        placeholder="Just another awesome site"
                                        autocomplete="off"
                                        :disabled="form.processing"
                                    />
                                </UFormField>

                                <USeparator />

                                <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-highlighted">Application URL</p>
                                        <p class="text-sm text-muted">The base URL of your application.</p>
                                    </div>
                                    <p class="text-sm font-medium text-highlighted">{{ settings.app_url }}</p>
                                </div>

                                <USeparator />

                                <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-highlighted">Environment</p>
                                        <p class="text-sm text-muted">Current running environment.</p>
                                    </div>
                                    <UBadge color="success" variant="subtle">Local</UBadge>
                                </div>

                                <USeparator />

                                <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-highlighted">Laravel Version</p>
                                        <p class="text-sm text-muted">The framework version.</p>
                                    </div>
                                    <p class="text-sm font-medium text-highlighted">12.x</p>
                                </div>

                                <USeparator />

                                <div class="flex max-sm:flex-col justify-between items-start gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-highlighted">PHP Version</p>
                                        <p class="text-sm text-muted">The PHP runtime version.</p>
                                    </div>
                                    <p class="text-sm font-medium text-highlighted">8.4</p>
                                </div>
                            </UPageCard>
                        </template>

                        <!-- SEO & Meta Section -->
                        <template v-if="activeTab === 'seo'">
                            <UPageCard
                                title="SEO & Meta"
                                description="Default meta tags for search engines."
                                variant="naked"
                                orientation="horizontal"
                                class="mb-4"
                            >
                                <UButton
                                    form="settings-form"
                                    label="Save changes"
                                    color="neutral"
                                    type="submit"
                                    :loading="form.processing"
                                    class="w-fit lg:ms-auto"
                                />
                            </UPageCard>

                            <UPageCard variant="subtle">
                                <UFormField
                                    name="meta_keywords"
                                    label="Meta Keywords"
                                    description="Comma-separated keywords for SEO (optional)."
                                    :error="form.errors.meta_keywords"
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                >
                                    <UInput
                                        v-model="form.meta_keywords"
                                        placeholder="food, recipes, cooking, maldives"
                                        autocomplete="off"
                                        :disabled="form.processing"
                                    />
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="meta_description"
                                    label="Meta Description"
                                    description="Default description for pages. Max 160 characters."
                                    :error="form.errors.meta_description"
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                    :ui="{ container: 'w-full sm:max-w-xs' }"
                                >
                                    <UTextarea
                                        v-model="form.meta_description"
                                        placeholder="Discover delicious recipes and food stories..."
                                        :rows="3"
                                        class="w-full"
                                        :disabled="form.processing"
                                    />
                                </UFormField>
                            </UPageCard>
                        </template>

                        <!-- OpenGraph Section -->
                        <template v-if="activeTab === 'opengraph'">
                            <UPageCard
                                title="OpenGraph"
                                description="How your site appears when shared on social media."
                                variant="naked"
                                orientation="horizontal"
                                class="mb-4"
                            >
                                <UButton
                                    form="settings-form"
                                    label="Save changes"
                                    color="neutral"
                                    type="submit"
                                    :loading="form.processing"
                                    class="w-fit lg:ms-auto"
                                />
                            </UPageCard>

                            <UPageCard variant="subtle">
                                <UFormField
                                    name="og_title"
                                    label="OG Title"
                                    description="Title shown when shared on social media."
                                    :error="form.errors.og_title"
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                >
                                    <UInput
                                        v-model="form.og_title"
                                        placeholder="Tasty - Food & Recipes"
                                        autocomplete="off"
                                        :disabled="form.processing"
                                    />
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="og_description"
                                    label="OG Description"
                                    description="Description shown when shared."
                                    :error="form.errors.og_description"
                                    class="flex max-sm:flex-col justify-between items-start gap-4"
                                    :ui="{ container: 'w-full sm:max-w-xs' }"
                                >
                                    <UTextarea
                                        v-model="form.og_description"
                                        placeholder="Discover delicious recipes..."
                                        :rows="3"
                                        class="w-full"
                                        :disabled="form.processing"
                                    />
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="og_image"
                                    label="OG Image"
                                    description="Default image for sharing. 1200x630px recommended."
                                    :error="form.errors.og_image"
                                    class="flex max-sm:flex-col justify-between sm:items-center gap-4"
                                >
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div
                                            v-if="ogImagePreview || (existingOgImage && !form.remove_og_image)"
                                            class="relative rounded-lg overflow-hidden border border-default bg-muted"
                                        >
                                            <img
                                                :src="ogImagePreview || existingOgImage!"
                                                alt="OG Image"
                                                class="w-32 h-16 object-cover"
                                            />
                                            <UButton
                                                icon="i-lucide-x"
                                                color="error"
                                                variant="solid"
                                                size="2xs"
                                                class="absolute top-1 right-1"
                                                @click="removeFile('og_image')"
                                            />
                                        </div>
                                        <UButton
                                            label="Choose"
                                            color="neutral"
                                            :disabled="form.processing"
                                            @click="ogImageRef?.click()"
                                        />
                                        <input
                                            ref="ogImageRef"
                                            type="file"
                                            class="hidden"
                                            accept=".jpg,.jpeg,.png,.webp"
                                            @change="handleFileChange($event, 'og_image')"
                                        />
                                    </div>
                                </UFormField>
                            </UPageCard>
                        </template>

                        <!-- Favicons Section -->
                        <template v-if="activeTab === 'favicons'">
                            <UPageCard
                                title="Favicons"
                                description="Browser tab icons and app icons."
                                variant="naked"
                                orientation="horizontal"
                                class="mb-4"
                            >
                                <UButton
                                    form="settings-form"
                                    label="Save changes"
                                    color="neutral"
                                    type="submit"
                                    :loading="form.processing"
                                    class="w-fit lg:ms-auto"
                                />
                            </UPageCard>

                            <UPageCard variant="subtle">
                                <UFormField
                                    name="favicon"
                                    label="Favicon"
                                    description="Main browser tab icon. ICO, PNG or SVG."
                                    :error="form.errors.favicon"
                                    class="flex max-sm:flex-col justify-between sm:items-center gap-4"
                                >
                                    <div class="flex flex-wrap items-center gap-3">
                                        <img
                                            v-if="faviconPreview || (existingFavicon && !form.remove_favicon)"
                                            :src="faviconPreview || existingFavicon!"
                                            alt="Favicon"
                                            class="size-8 rounded border border-default bg-muted object-contain"
                                        />
                                        <UButton
                                            v-if="faviconPreview || (existingFavicon && !form.remove_favicon)"
                                            icon="i-lucide-x"
                                            color="error"
                                            variant="ghost"
                                            size="xs"
                                            @click="removeFile('favicon')"
                                        />
                                        <UButton
                                            label="Choose"
                                            color="neutral"
                                            :disabled="form.processing"
                                            @click="faviconRef?.click()"
                                        />
                                        <input
                                            ref="faviconRef"
                                            type="file"
                                            class="hidden"
                                            accept=".ico,.png,.svg"
                                            @change="handleFileChange($event, 'favicon')"
                                        />
                                    </div>
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="favicon_16"
                                    label="Favicon 16x16"
                                    description="Small browser tab icon. PNG only."
                                    :error="form.errors.favicon_16"
                                    class="flex max-sm:flex-col justify-between sm:items-center gap-4"
                                >
                                    <div class="flex flex-wrap items-center gap-3">
                                        <img
                                            v-if="favicon16Preview || (existingFavicon16 && !form.remove_favicon_16)"
                                            :src="favicon16Preview || existingFavicon16!"
                                            alt="Favicon 16"
                                            class="size-4 rounded border border-default bg-muted object-contain"
                                        />
                                        <UButton
                                            v-if="favicon16Preview || (existingFavicon16 && !form.remove_favicon_16)"
                                            icon="i-lucide-x"
                                            color="error"
                                            variant="ghost"
                                            size="xs"
                                            @click="removeFile('favicon_16')"
                                        />
                                        <UButton
                                            label="Choose"
                                            color="neutral"
                                            :disabled="form.processing"
                                            @click="favicon16Ref?.click()"
                                        />
                                        <input
                                            ref="favicon16Ref"
                                            type="file"
                                            class="hidden"
                                            accept=".png"
                                            @change="handleFileChange($event, 'favicon_16')"
                                        />
                                    </div>
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="favicon_32"
                                    label="Favicon 32x32"
                                    description="Standard browser tab icon. PNG only."
                                    :error="form.errors.favicon_32"
                                    class="flex max-sm:flex-col justify-between sm:items-center gap-4"
                                >
                                    <div class="flex flex-wrap items-center gap-3">
                                        <img
                                            v-if="favicon32Preview || (existingFavicon32 && !form.remove_favicon_32)"
                                            :src="favicon32Preview || existingFavicon32!"
                                            alt="Favicon 32"
                                            class="size-8 rounded border border-default bg-muted object-contain"
                                        />
                                        <UButton
                                            v-if="favicon32Preview || (existingFavicon32 && !form.remove_favicon_32)"
                                            icon="i-lucide-x"
                                            color="error"
                                            variant="ghost"
                                            size="xs"
                                            @click="removeFile('favicon_32')"
                                        />
                                        <UButton
                                            label="Choose"
                                            color="neutral"
                                            :disabled="form.processing"
                                            @click="favicon32Ref?.click()"
                                        />
                                        <input
                                            ref="favicon32Ref"
                                            type="file"
                                            class="hidden"
                                            accept=".png"
                                            @change="handleFileChange($event, 'favicon_32')"
                                        />
                                    </div>
                                </UFormField>

                                <USeparator />

                                <UFormField
                                    name="apple_touch_icon"
                                    label="Apple Touch Icon"
                                    description="iOS home screen icon. 180x180px PNG."
                                    :error="form.errors.apple_touch_icon"
                                    class="flex max-sm:flex-col justify-between sm:items-center gap-4"
                                >
                                    <div class="flex flex-wrap items-center gap-3">
                                        <img
                                            v-if="appleTouchIconPreview || (existingAppleTouchIcon && !form.remove_apple_touch_icon)"
                                            :src="appleTouchIconPreview || existingAppleTouchIcon!"
                                            alt="Apple Touch Icon"
                                            class="size-12 rounded-xl border border-default bg-muted object-contain"
                                        />
                                        <UButton
                                            v-if="appleTouchIconPreview || (existingAppleTouchIcon && !form.remove_apple_touch_icon)"
                                            icon="i-lucide-x"
                                            color="error"
                                            variant="ghost"
                                            size="xs"
                                            @click="removeFile('apple_touch_icon')"
                                        />
                                        <UButton
                                            label="Choose"
                                            color="neutral"
                                            :disabled="form.processing"
                                            @click="appleTouchIconRef?.click()"
                                        />
                                        <input
                                            ref="appleTouchIconRef"
                                            type="file"
                                            class="hidden"
                                            accept=".png"
                                            @change="handleFileChange($event, 'apple_touch_icon')"
                                        />
                                    </div>
                                </UFormField>
                            </UPageCard>
                        </template>

                        <!-- Social Links Section -->
                        <template v-if="activeTab === 'social'">
                            <UPageCard
                                title="Social Links"
                                description="Links to your social media profiles."
                                variant="naked"
                                orientation="horizontal"
                                class="mb-4"
                            >
                                <UButton
                                    form="settings-form"
                                    label="Save changes"
                                    color="neutral"
                                    type="submit"
                                    :loading="form.processing"
                                    class="w-fit lg:ms-auto"
                                />
                            </UPageCard>

                            <UPageCard variant="subtle">
                                <template v-for="(social, index) in socialLinks" :key="social.key">
                                    <UFormField
                                        :name="social.key"
                                        :label="social.label"
                                        :description="social.description"
                                        :error="(form.errors as any)[social.key]"
                                        class="flex max-sm:flex-col justify-between items-start gap-4"
                                    >
                                        <UInput
                                            v-model="(form as any)[social.key]"
                                            :placeholder="social.placeholder"
                                            type="url"
                                            autocomplete="off"
                                            :disabled="form.processing"
                                        >
                                            <template #leading>
                                                <UIcon :name="social.icon" class="size-4 text-muted" />
                                            </template>
                                        </UInput>
                                    </UFormField>
                                    <USeparator v-if="index < socialLinks.length - 1" />
                                </template>
                            </UPageCard>
                        </template>
                    </UForm>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>
