<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BlockEditor from '../../components/BlockEditor.vue';
import { useSidebar } from '../../composables/useSidebar';
import { useDhivehiKeyboard } from '../../composables/useDhivehiKeyboard';
import type { Category, Tag, PostTypeOption, Post } from '../../types';

interface LanguageInfo {
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_rtl: boolean;
}

// Sidebar control
const { hide: hideSidebar, show: showSidebar } = useSidebar();

const props = defineProps<{
    post: Post & { category_id: number | null; tags: number[]; language_code: string };
    categories: Category[];
    tags: Tag[];
    postTypes: PostTypeOption[];
    language: LanguageInfo | null;
}>();

// Dhivehi keyboard for RTL content
const isRtl = computed(() => props.language?.direction === 'rtl');
const {
    enabled: dhivehiEnabled,
    toggle: toggleDhivehi,
    handleKeyDown: dhivehiKeyDown,
    layout: dhivehiLayout,
    layoutOptions,
} = useDhivehiKeyboard({ defaultEnabled: isRtl.value });

// Compute RTL state based on language
const textDirection = computed(() => isRtl.value ? 'rtl' : 'ltr');
const textAlign = computed(() => isRtl.value ? 'text-right' : 'text-left');

// Dhivehi placeholders
const placeholders = computed(() => ({
    title: isRtl.value ? 'ސުރުޚީ' : 'Title',
    subtitle: isRtl.value ? 'ސަބް ސުރުޚީ...' : 'Add a subtitle...',
    excerpt: isRtl.value ? 'ކުރު ޚުލާސާއެއް ލިޔޭ...' : 'Write a brief excerpt or summary...',
}));

// Handle keydown for Dhivehi input fields
function onDhivehiKeyDown(e: KeyboardEvent) {
    if (isRtl.value) {
        dhivehiKeyDown(e, e.target as HTMLInputElement | HTMLTextAreaElement);
    }
}

const form = useForm({
    title: props.post.title,
    subtitle: props.post.subtitle || '',
    slug: props.post.slug,
    excerpt: props.post.excerpt || '',
    content: props.post.content,
    post_type: props.post.post_type,
    status: props.post.status,
    scheduled_at: props.post.scheduled_at || '',
    category_id: props.post.category_id ?? null,
    tags: props.post.tags || [],
    featured_image: null as File | null,
    remove_featured_image: false,
    recipe_meta: {
        prep_time: props.post.recipe_meta?.prep_time || null,
        cook_time: props.post.recipe_meta?.cook_time || null,
        servings: props.post.recipe_meta?.servings || null,
        difficulty: props.post.recipe_meta?.difficulty || '',
        ingredients: props.post.recipe_meta?.ingredients || [],
    },
    meta_title: props.post.meta_title || '',
    meta_description: props.post.meta_description || '',
});

// Sidebar toggle for mobile
const sidebarOpen = ref(false);

// Fullscreen mode
const isFullscreen = ref(false);

function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
    // Also hide/show the main sidebar
    if (isFullscreen.value) {
        hideSidebar();
    } else {
        showSidebar();
    }
}

// Auto-save functionality
const autoSaveTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const lastSaved = ref<Date | null>(null);
const isSaving = ref(false);

function autoSave() {
    // Only auto-save if there's a title
    if (!form.title.trim()) return;

    // Clear existing timer
    if (autoSaveTimer.value) {
        clearTimeout(autoSaveTimer.value);
    }

    // Set new timer for 3 seconds after last change
    autoSaveTimer.value = setTimeout(async () => {
        if (form.processing || isSaving.value) return;

        isSaving.value = true;

        const langCode = props.language?.code || props.post.language_code || 'en';
        form.post(`/cms/posts/${langCode}/${props.post.uuid}`, {
            forceFormData: true,
            headers: { 'X-HTTP-Method-Override': 'PUT' },
            preserveScroll: true,
            onSuccess: () => {
                lastSaved.value = new Date();
                isSaving.value = false;
            },
            onError: () => {
                isSaving.value = false;
            },
        });
    }, 3000);
}

// Watch for changes to trigger auto-save
watch(
    () => [form.title, form.subtitle, form.excerpt, form.content],
    () => {
        autoSave();
    },
    { deep: true }
);

// Keyboard shortcut for fullscreen (Escape to exit)
function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && isFullscreen.value) {
        isFullscreen.value = false;
        showSidebar();
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
    if (autoSaveTimer.value) {
        clearTimeout(autoSaveTimer.value);
    }
    // Restore sidebar when leaving the page
    showSidebar();
});

// Format last saved time
const lastSavedText = computed(() => {
    if (!lastSaved.value) return null;
    const now = new Date();
    const diff = Math.floor((now.getTime() - lastSaved.value.getTime()) / 1000);
    if (diff < 60) return 'Saved just now';
    if (diff < 3600) return `Saved ${Math.floor(diff / 60)}m ago`;
    return `Saved at ${lastSaved.value.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
});

const showRecipeFields = computed(() => form.post_type === 'recipe');
const showScheduleField = computed(() => form.status === 'scheduled');

const statusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Pending Review', value: 'pending' },
    { label: 'Published', value: 'published' },
    { label: 'Scheduled', value: 'scheduled' },
];

const difficultyOptions = [
    { label: 'Select difficulty', value: '' },
    { label: 'Easy', value: 'easy' },
    { label: 'Medium', value: 'medium' },
    { label: 'Hard', value: 'hard' },
];

const flattenedCategories = computed(() => {
    const flatten = (cats: Category[], depth = 0): { label: string; value: number }[] => {
        return cats.flatMap((cat) => {
            const prefix = '— '.repeat(depth);
            const result = [{ label: prefix + cat.name, value: cat.id }];
            if (cat.children && cat.children.length > 0) {
                result.push(...flatten(cat.children, depth + 1));
            }
            return result;
        });
    };
    return flatten(props.categories);
});

const tagOptions = computed(() =>
    props.tags.map((tag) => ({ label: tag.name, value: tag.id }))
);

const featuredImagePreview = ref<string | null>(props.post.featured_image_url || null);

function handleFeaturedImage(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.featured_image = file;
        form.remove_featured_image = false;
        featuredImagePreview.value = URL.createObjectURL(file);
    }
}

function removeFeaturedImage() {
    form.featured_image = null;
    form.remove_featured_image = true;
    featuredImagePreview.value = null;
}

const newIngredient = ref('');

function addIngredient() {
    if (newIngredient.value.trim()) {
        form.recipe_meta.ingredients.push(newIngredient.value.trim());
        newIngredient.value = '';
    }
}

function removeIngredient(index: number) {
    form.recipe_meta.ingredients.splice(index, 1);
}

function generateSlug() {
    form.slug = form.title
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
}

// Extract text from Editor.js blocks
function extractTextFromBlocks(content: any): string {
    if (!content?.blocks) return '';

    let text = '';
    for (const block of content.blocks) {
        if (block.data?.text) {
            // Strip HTML tags
            text += ' ' + block.data.text.replace(/<[^>]*>/g, '');
        }
        if (block.data?.items) {
            for (const item of block.data.items) {
                if (typeof item === 'string') {
                    text += ' ' + item.replace(/<[^>]*>/g, '');
                } else if (item.content) {
                    text += ' ' + item.content.replace(/<[^>]*>/g, '');
                }
            }
        }
        if (block.data?.caption) {
            text += ' ' + block.data.caption.replace(/<[^>]*>/g, '');
        }
    }
    return text.trim();
}

const contentText = computed(() => extractTextFromBlocks(form.content));
const wordCount = computed(() => {
    if (!contentText.value) return 0;
    return contentText.value.split(/\s+/).filter(Boolean).length;
});
const charCount = computed(() => contentText.value.length);

// Reading time estimate (200 words per minute)
const readingTime = computed(() => {
    const minutes = Math.ceil(wordCount.value / 200);
    return minutes < 1 ? 'Less than 1 min' : `${minutes} min read`;
});

// Time ago helper
const timeAgo = computed(() => {
    const date = new Date(props.post.updated_at);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    return 'just now';
});

function submit(status?: string) {
    if (status) {
        form.status = status;
    }

    const langCode = props.language?.code || props.post.language_code || 'en';
    form.post(`/cms/posts/${langCode}/${props.post.uuid}`, {
        forceFormData: true,
        headers: {
            'X-HTTP-Method-Override': 'PUT',
        },
    });
}

function goBack() {
    const langCode = props.language?.code || props.post.language_code || 'en';
    router.visit(`/cms/posts/${langCode}`);
}

function deletePost() {
    if (confirm('Are you sure you want to move this post to trash?')) {
        const langCode = props.language?.code || props.post.language_code || 'en';
        router.delete(`/cms/posts/${langCode}/${props.post.uuid}`);
    }
}
</script>

<template>
    <Head :title="`Edit: ${post.title}`" />

    <DashboardLayout>
        <UDashboardPanel id="edit-post" :ui="{ body: 'p-0 gap-0' }">
            <template #header>
                <UDashboardNavbar>
                    <template #leading>
                        <div class="flex items-center gap-3">
                            <UDashboardSidebarCollapse />
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-arrow-left"
                                size="sm"
                                @click="goBack"
                            />
                            <div class="h-4 w-px bg-default" />
                            <span class="text-sm text-muted">Editing</span>
                            <UBadge
                                :color="form.status === 'published' ? 'success' : form.status === 'pending' ? 'warning' : 'neutral'"
                                variant="subtle"
                                size="sm"
                            >
                                {{ form.status }}
                            </UBadge>
                            <UBadge
                                v-if="language"
                                :color="isRtl ? 'warning' : 'primary'"
                                variant="subtle"
                                size="sm"
                            >
                                <span :class="isRtl ? 'font-dhivehi' : ''">{{ language.native_name }}</span>
                            </UBadge>
                        </div>
                    </template>

                    <template #right>
                        <div class="flex items-center gap-2">
                            <!-- Auto-save status -->
                            <span v-if="isSaving" class="text-xs text-muted flex items-center gap-1">
                                <UIcon name="i-lucide-loader-2" class="size-3 animate-spin" />
                                Saving...
                            </span>
                            <span v-else-if="lastSavedText" class="text-xs text-muted">
                                {{ lastSavedText }}
                            </span>

                            <div class="h-4 w-px bg-default hidden sm:block" />

                            <!-- Dhivehi keyboard toggle (only for RTL languages) -->
                            <UTooltip v-if="isRtl" :text="dhivehiEnabled ? 'Dhivehi keyboard ON' : 'Dhivehi keyboard OFF'">
                                <UButton
                                    color="neutral"
                                    :variant="dhivehiEnabled ? 'soft' : 'ghost'"
                                    icon="i-lucide-keyboard"
                                    size="sm"
                                    :class="dhivehiEnabled ? 'text-warning' : ''"
                                    @click="toggleDhivehi"
                                />
                            </UTooltip>

                            <!-- Fullscreen toggle -->
                            <UButton
                                color="neutral"
                                variant="ghost"
                                :icon="isFullscreen ? 'i-lucide-minimize-2' : 'i-lucide-maximize-2'"
                                size="sm"
                                @click="toggleFullscreen"
                            />

                            <UButton
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                :disabled="form.processing"
                                @click="submit()"
                            >
                                Save
                            </UButton>
                            <UButton
                                v-if="form.status !== 'published'"
                                size="sm"
                                :loading="form.processing"
                                @click="submit('published')"
                            >
                                Publish
                            </UButton>
                            <UButton
                                v-else
                                size="sm"
                                :loading="form.processing"
                                @click="submit()"
                            >
                                Update
                            </UButton>
                            <!-- Mobile sidebar toggle -->
                            <UButton
                                v-if="!isFullscreen"
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-panel-right"
                                size="sm"
                                class="lg:hidden"
                                @click="sidebarOpen = !sidebarOpen"
                            />
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="flex h-full relative">
                    <!-- Mobile sidebar overlay -->
                    <div
                        v-if="sidebarOpen && !isFullscreen"
                        class="fixed inset-0 bg-black/50 z-50 lg:hidden"
                        @click="sidebarOpen = false"
                    />

                    <!-- Main Editor Area -->
                    <div
                        :class="[
                            'flex-1 overflow-y-auto transition-all duration-300',
                            isFullscreen ? 'absolute inset-0 z-50 bg-[var(--ui-bg)]' : '',
                        ]"
                    >
                        <div :class="['mx-auto px-6 py-12', isFullscreen ? 'max-w-screen-2xl' : 'max-w-2xl']">
                            <!-- Title -->
                            <input
                                v-model="form.title"
                                type="text"
                                :placeholder="placeholders.title"
                                :dir="textDirection"
                                :class="[
                                    'w-full font-bold bg-transparent border-0 outline-none placeholder:text-muted/40 mb-2',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-4xl leading-relaxed placeholder:font-dhivehi' : 'text-4xl leading-tight',
                                ]"
                                @keydown="onDhivehiKeyDown"
                            />
                            <p v-if="form.errors.title" class="text-error text-sm mb-2">{{ form.errors.title }}</p>

                            <!-- Subtitle -->
                            <input
                                v-model="form.subtitle"
                                type="text"
                                :placeholder="placeholders.subtitle"
                                :dir="textDirection"
                                :class="[
                                    'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 mb-4',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-xl leading-relaxed placeholder:font-dhivehi' : 'text-xl',
                                ]"
                                @keydown="onDhivehiKeyDown"
                            />

                            <!-- Excerpt -->
                            <textarea
                                v-model="form.excerpt"
                                :placeholder="placeholders.excerpt"
                                rows="2"
                                :dir="textDirection"
                                :class="[
                                    'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 resize-none',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-base leading-relaxed placeholder:font-dhivehi' : 'text-base',
                                ]"
                                @keydown="onDhivehiKeyDown"
                            />

                            <!-- Content Editor -->
                            <BlockEditor
                                v-model="form.content"
                                placeholder="Tell your story..."
                                :rtl="isRtl"
                                :dhivehi-enabled="dhivehiEnabled"
                                :dhivehi-layout="dhivehiLayout"
                            />
                        </div>
                    </div>

                    <!-- Sidebar - hidden on mobile and in fullscreen, toggle with button -->
                    <div
                        v-show="!isFullscreen"
                        :class="[
                            'w-80 border-l border-default overflow-y-auto',
                            'bg-[var(--ui-bg)] lg:bg-elevated/25',
                            'fixed lg:relative inset-y-0 right-0 z-[60]',
                            'transition-transform duration-200 ease-in-out',
                            sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0',
                        ]"
                    >
                        <!-- Mobile close button -->
                        <div class="lg:hidden flex items-center justify-between p-4 border-b border-default">
                            <span class="text-sm font-medium">Post Settings</span>
                            <UButton
                                color="neutral"
                                variant="ghost"
                                icon="i-lucide-x"
                                size="sm"
                                @click="sidebarOpen = false"
                            />
                        </div>
                        <div class="p-4 space-y-6">

                            <!-- Document Info -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-file-text" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Document</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted">Characters</span>
                                        <span class="text-highlighted font-medium">{{ charCount.toLocaleString() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted">Words</span>
                                        <span class="text-highlighted font-medium">{{ wordCount.toLocaleString() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted">Reading time</span>
                                        <span class="text-highlighted font-medium">{{ readingTime }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted">Last saved</span>
                                        <span class="text-highlighted font-medium">{{ timeAgo }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="h-px bg-default" />

                            <!-- Status & Type -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-settings-2" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Settings</span>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Status</label>
                                        <USelectMenu
                                            v-model="form.status"
                                            :items="statusOptions"
                                            value-key="value"
                                            size="sm"
                                            class="w-full"
                                        />
                                    </div>
                                    <div v-if="showScheduleField">
                                        <label class="text-xs text-muted mb-1 block">Schedule for</label>
                                        <UInput v-model="form.scheduled_at" type="datetime-local" size="sm" class="w-full" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Type</label>
                                        <USelectMenu
                                            v-model="form.post_type"
                                            :items="postTypes"
                                            value-key="value"
                                            size="sm"
                                            class="w-full"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">URL Slug</label>
                                        <div class="flex gap-1.5">
                                            <UInput v-model="form.slug" placeholder="post-slug" size="sm" class="flex-1 min-w-0" />
                                            <UButton size="sm" color="neutral" variant="ghost" icon="i-lucide-refresh-cw" @click="generateSlug" />
                                        </div>
                                    </div>
                                    <div class="pt-2 text-xs text-muted space-y-1">
                                        <p v-if="post.author"><span class="text-highlighted">Author:</span> {{ post.author.name }}</p>
                                        <p v-if="post.published_at"><span class="text-highlighted">Published:</span> {{ new Date(post.published_at).toLocaleDateString() }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="h-px bg-default" />

                            <!-- Featured Image -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-image" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Cover</span>
                                </div>
                                <div v-if="featuredImagePreview" class="relative mb-2">
                                    <img :src="featuredImagePreview" alt="Cover" class="w-full h-28 object-cover rounded-lg" />
                                    <UButton
                                        color="neutral"
                                        variant="solid"
                                        icon="i-lucide-x"
                                        size="xs"
                                        class="absolute top-1.5 right-1.5"
                                        @click="removeFeaturedImage"
                                    />
                                </div>
                                <label class="block cursor-pointer">
                                    <div class="border border-dashed border-default rounded-lg px-3 py-4 text-center hover:border-primary hover:bg-primary/5 transition-colors">
                                        <UIcon name="i-lucide-upload" class="size-5 text-muted mx-auto mb-1" />
                                        <p class="text-xs text-muted">{{ featuredImagePreview ? 'Replace' : 'Upload cover image' }}</p>
                                    </div>
                                    <input type="file" accept="image/*" class="hidden" @change="handleFeaturedImage" />
                                </label>
                            </div>

                            <div class="h-px bg-default" />

                            <!-- Taxonomy -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-folder" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Organize</span>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Category</label>
                                        <USelectMenu
                                            v-model="form.category_id"
                                            :items="flattenedCategories"
                                            value-key="value"
                                            placeholder="Select category..."
                                            size="sm"
                                            class="w-full"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Tags</label>
                                        <USelectMenu
                                            v-model="form.tags"
                                            :items="tagOptions"
                                            value-key="value"
                                            multiple
                                            placeholder="Select..."
                                            searchable
                                            size="sm"
                                            class="w-full"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Recipe Details (conditional) -->
                            <template v-if="showRecipeFields">
                                <div class="h-px bg-default" />
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <UIcon name="i-lucide-chef-hat" class="size-4 text-muted" />
                                        <span class="text-xs font-medium text-muted uppercase tracking-wider">Recipe</span>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-xs text-muted mb-1 block">Prep (min)</label>
                                                <UInput v-model.number="form.recipe_meta.prep_time" type="number" min="0" size="sm" class="w-full" />
                                            </div>
                                            <div>
                                                <label class="text-xs text-muted mb-1 block">Cook (min)</label>
                                                <UInput v-model.number="form.recipe_meta.cook_time" type="number" min="0" size="sm" class="w-full" />
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-xs text-muted mb-1 block">Servings</label>
                                                <UInput v-model.number="form.recipe_meta.servings" type="number" min="1" size="sm" class="w-full" />
                                            </div>
                                            <div>
                                                <label class="text-xs text-muted mb-1 block">Difficulty</label>
                                                <USelectMenu v-model="form.recipe_meta.difficulty" :items="difficultyOptions" value-key="value" size="sm" class="w-full" />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">Ingredients</label>
                                            <div class="space-y-1.5">
                                                <div v-for="(ingredient, index) in form.recipe_meta.ingredients" :key="index" class="flex gap-1.5">
                                                    <UInput
                                                        :model-value="ingredient"
                                                        size="sm"
                                                        class="flex-1 min-w-0"
                                                        @update:model-value="form.recipe_meta.ingredients[index] = $event as string"
                                                    />
                                                    <UButton size="sm" color="neutral" variant="ghost" icon="i-lucide-x" @click="removeIngredient(index)" />
                                                </div>
                                                <div class="flex gap-1.5">
                                                    <UInput
                                                        v-model="newIngredient"
                                                        placeholder="Add..."
                                                        size="sm"
                                                        class="flex-1 min-w-0"
                                                        @keyup.enter.prevent="addIngredient"
                                                    />
                                                    <UButton size="sm" color="neutral" variant="soft" icon="i-lucide-plus" @click="addIngredient" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="h-px bg-default" />

                            <!-- SEO -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-search" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">SEO</span>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="text-xs text-muted">Meta title</label>
                                            <span class="text-xs text-muted">{{ form.meta_title?.length || 0 }}/70</span>
                                        </div>
                                        <UInput v-model="form.meta_title" placeholder="SEO title" maxlength="70" size="sm" class="w-full" />
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="text-xs text-muted">Meta description</label>
                                            <span class="text-xs text-muted">{{ form.meta_description?.length || 0 }}/160</span>
                                        </div>
                                        <UTextarea
                                            v-model="form.meta_description"
                                            placeholder="SEO description"
                                            :rows="2"
                                            maxlength="160"
                                            class="w-full"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="h-px bg-default" />

                            <!-- Actions -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-zap" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Actions</span>
                                </div>
                                <div class="space-y-1">
                                    <UButton
                                        color="neutral"
                                        variant="ghost"
                                        icon="i-lucide-trash"
                                        size="sm"
                                        class="w-full justify-start text-error hover:bg-error/10"
                                        @click="deletePost"
                                    >
                                        Move to trash
                                    </UButton>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </template>
        </UDashboardPanel>
    </DashboardLayout>
</template>

<style scoped>
input::placeholder,
textarea::placeholder {
    opacity: 1;
}
</style>
