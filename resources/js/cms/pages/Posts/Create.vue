<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import BlockEditor, { type MediaSelectCallback } from '../../components/BlockEditor.vue';
import MediaPickerModal from '../../components/MediaPickerModal.vue';
import type { MediaBlockItem } from '../../editor-tools/MediaBlock';
import { useSidebar } from '../../composables/useSidebar';
import { useDhivehiKeyboard } from '../../composables/useDhivehiKeyboard';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Category, Tag } from '../../types';

interface PostTypeField {
    name: string;
    label: string;
    type: 'text' | 'number' | 'textarea' | 'select' | 'toggle' | 'repeater';
    suffix?: string;
    options?: string[];
}

interface PostTypeWithFields {
    value: string;
    label: string;
    icon?: string;
    fields?: PostTypeField[];
}

interface MediaItem {
    id: number;
    uuid: string;
    type: 'image' | 'video_local' | 'video_embed';
    url: string | null;
    thumbnail_url: string | null;
    title: string | null;
    alt_text: string | null;
    caption?: string | null;
    credit_display?: {
        name: string;
        url: string | null;
        role: string | null;
    } | null;
    is_image: boolean;
    is_video: boolean;
}

interface LanguageInfo {
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_rtl: boolean;
}

interface Sponsor {
    id: number;
    name: string;
}

// Sidebar control
const { hide: hideSidebar, show: showSidebar } = useSidebar();
const { cmsPath } = useCmsPath();

const props = defineProps<{
    categories: Category[];
    tags: Tag[];
    sponsors: Sponsor[];
    postTypes: PostTypeWithFields[];
    language: LanguageInfo;
}>();

// Dhivehi keyboard for RTL content
const {
    enabled: dhivehiEnabled,
    toggle: toggleDhivehi,
    handleKeyDown: dhivehiKeyDown,
    layout: dhivehiLayout,
    layoutOptions,
} = useDhivehiKeyboard({ defaultEnabled: props.language.is_rtl });

// Compute RTL state based on language
const isRtl = computed(() => props.language.direction === 'rtl');
const textDirection = computed(() => isRtl.value ? 'rtl' : 'ltr');
const textAlign = computed(() => isRtl.value ? 'text-right' : 'text-left');

// Dhivehi placeholders
const placeholders = computed(() => ({
    kicker: isRtl.value ? 'ކިކާ (މިސާލު: ފީޗާ)' : 'KICKER (e.g., TASTY FEATURE)',
    title: isRtl.value ? 'ސުރުޚީ' : 'Title',
    subtitle: isRtl.value ? 'ސަބް ސުރުޚީ...' : 'Add a subtitle...',
    excerpt: isRtl.value ? 'ޚުލާސާ / ޑެކް ލިޔޭ...' : 'Write a deck or summary...',
}));

// Textarea refs for auto-resize
const titleTextarea = ref<HTMLTextAreaElement | null>(null);
const subtitleTextarea = ref<HTMLTextAreaElement | null>(null);
const excerptTextarea = ref<HTMLTextAreaElement | null>(null);

// Auto-resize functions
function autoResizeTitle() {
    if (titleTextarea.value) {
        titleTextarea.value.style.height = 'auto';
        titleTextarea.value.style.height = titleTextarea.value.scrollHeight + 'px';
    }
}

function autoResizeSubtitle() {
    if (subtitleTextarea.value) {
        subtitleTextarea.value.style.height = 'auto';
        subtitleTextarea.value.style.height = subtitleTextarea.value.scrollHeight + 'px';
    }
}

function autoResizeExcerpt() {
    if (excerptTextarea.value) {
        excerptTextarea.value.style.height = 'auto';
        excerptTextarea.value.style.height = excerptTextarea.value.scrollHeight + 'px';
    }
}

// Handle keydown for Dhivehi input fields
function onDhivehiKeyDown(e: KeyboardEvent) {
    if (isRtl.value) {
        dhivehiKeyDown(e, e.target as HTMLInputElement | HTMLTextAreaElement);
    }
}

const form = useForm({
    title: '',
    kicker: '',
    subtitle: '',
    slug: '',
    excerpt: '',
    content: null as any,
    post_type: 'article',
    status: 'draft',
    scheduled_at: '',
    category_id: null as number | null,
    featured_tag_id: null as number | null,
    sponsor_id: null as number | null,
    tags: [] as number[],
    featured_media_id: null as number | null,
    custom_fields: {} as Record<string, unknown>,
    meta_title: '',
    meta_description: '',
    show_author: true,
});

// Unified media picker state
const mediaPickerOpen = ref(false);
const mediaPickerType = ref<'all' | 'images' | 'videos'>('images');
const mediaPickerMultiple = ref(false);
const mediaPickerPurpose = ref<'cover' | 'editor'>('cover');
const selectedFeaturedMedia = ref<MediaItem | null>(null);

// For editor callback
let editorMediaResolve: ((items: MediaBlockItem[] | null) => void) | null = null;

// Open media picker for cover photo
function openCoverPicker() {
    mediaPickerType.value = 'images';
    mediaPickerMultiple.value = false;
    mediaPickerPurpose.value = 'cover';
    mediaPickerOpen.value = true;
}

// Callback for BlockEditor to open media picker
const handleEditorSelectMedia: MediaSelectCallback = ({ multiple }) => {
    return new Promise((resolve) => {
        mediaPickerType.value = 'all';
        mediaPickerMultiple.value = multiple;
        mediaPickerPurpose.value = 'editor';
        editorMediaResolve = resolve;
        mediaPickerOpen.value = true;
    });
};

// Handle media selection from unified picker
function handleMediaSelect(items: MediaItem[]) {
    if (mediaPickerPurpose.value === 'cover') {
        // Cover photo selection
        if (items.length > 0) {
            const item = items[0];
            selectedFeaturedMedia.value = item;
            form.featured_media_id = item.id;
        }
    } else if (mediaPickerPurpose.value === 'editor' && editorMediaResolve) {
        // Editor block selection - convert to MediaBlockItem format
        const blockItems: MediaBlockItem[] = items.map(item => ({
            id: item.id,
            uuid: item.uuid,
            url: item.url || '',
            thumbnail_url: item.thumbnail_url,
            title: item.title,
            alt_text: item.alt_text,
            caption: item.caption || item.title || null,
            credit_display: item.credit_display || null,
            is_image: item.is_image === true,
            is_video: item.is_video === true,
            // Video-specific fields for playback
            type: item.type,
            embed_url: item.embed_url,
            embed_provider: item.embed_provider,
            embed_video_id: item.embed_video_id,
        }));
        editorMediaResolve(blockItems.length > 0 ? blockItems : null);
        editorMediaResolve = null;
    }
}

// Handle picker close without selection
function handleMediaPickerClose(open: boolean) {
    if (!open && mediaPickerPurpose.value === 'editor' && editorMediaResolve) {
        editorMediaResolve(null);
        editorMediaResolve = null;
    }
}

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

// Save state
const lastSaved = ref<Date | null>(null);
const isSaving = ref(false);
const postUuid = ref<string | null>(null); // Will be set after first save
const hasUnsavedChanges = ref(false);

// Idle auto-save (5 minutes of inactivity)
const IDLE_SAVE_TIMEOUT = 5 * 60 * 1000; // 5 minutes in milliseconds
const idleTimer = ref<ReturnType<typeof setTimeout> | null>(null);

// Perform save operation
function performSave() {
    // Only save if there's a title (minimum requirement)
    if (!form.title.trim()) return;
    if (form.processing || isSaving.value) return;

    isSaving.value = true;

    // If we already have a UUID, update the existing draft
    if (postUuid.value) {
        form.post(cmsPath(`/posts/${props.language.code}/${postUuid.value}`), {
            forceFormData: true,
            headers: { 'X-HTTP-Method-Override': 'PUT' },
            preserveScroll: true,
            onSuccess: () => {
                lastSaved.value = new Date();
                hasUnsavedChanges.value = false;
                isSaving.value = false;
            },
            onError: () => {
                isSaving.value = false;
            },
        });
    } else {
        // First save - create as draft
        form.transform((data) => ({
            ...data,
            status: 'draft',
        })).post(cmsPath(`/posts/${props.language.code}`), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page: any) => {
                // Extract UUID from redirect URL or response
                const match = page.url?.match(/\/cms\/posts\/[^/]+\/([^/]+)\/edit/);
                if (match) {
                    postUuid.value = match[1];
                    // Update URL without full navigation
                    window.history.replaceState({}, '', cmsPath(`/posts/${props.language.code}/${match[1]}/edit`));
                }
                lastSaved.value = new Date();
                hasUnsavedChanges.value = false;
                isSaving.value = false;
            },
            onError: () => {
                isSaving.value = false;
            },
        });
    }
}

// Reset idle timer on user activity
function resetIdleTimer() {
    if (idleTimer.value) {
        clearTimeout(idleTimer.value);
    }

    // Only set timer if there are unsaved changes
    if (hasUnsavedChanges.value) {
        idleTimer.value = setTimeout(() => {
            if (hasUnsavedChanges.value && form.title.trim()) {
                performSave();
            }
        }, IDLE_SAVE_TIMEOUT);
    }
}

// Track user activity events
function setupIdleDetection() {
    const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart'];
    events.forEach(event => {
        document.addEventListener(event, resetIdleTimer, { passive: true });
    });
}

function cleanupIdleDetection() {
    const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart'];
    events.forEach(event => {
        document.removeEventListener(event, resetIdleTimer);
    });
    if (idleTimer.value) {
        clearTimeout(idleTimer.value);
        idleTimer.value = null;
    }
}

// Watch for changes to mark as dirty and reset idle timer
watch(
    () => [form.title, form.subtitle, form.excerpt, form.content],
    () => {
        hasUnsavedChanges.value = true;
        resetIdleTimer();
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
    // Setup idle detection for auto-save after 5 minutes of inactivity
    setupIdleDetection();
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeydown);
    // Cleanup idle detection
    cleanupIdleDetection();
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

// Get current post type configuration (with fields)
const currentPostType = computed(() => {
    const type = props.postTypes.find(t => t.value === form.post_type);
    if (!type) return null;
    return {
        key: type.value,
        label: type.label,
        icon: type.icon,
        fields: type.fields || [],
    };
});

// Toast for post type changes
const toast = useToast();
const previousPostType = ref(form.post_type);

watch(() => form.post_type, (newType) => {
    const type = props.postTypes.find(t => t.value === newType);
    if (type?.fields && type.fields.length > 0 && newType !== previousPostType.value) {
        toast.add({
            title: `${type.label} Fields Available`,
            description: 'Additional fields are now available below the content editor.',
            icon: type.icon || 'i-lucide-info',
            color: 'info',
        });
    }
    previousPostType.value = newType;
});

const showScheduleField = computed(() => form.status === 'scheduled');

const statusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Pending Review', value: 'pending' },
    { label: 'Published', value: 'published' },
    { label: 'Scheduled', value: 'scheduled' },
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

// Reactive tags list (can be extended when creating new tags inline)
const localTags = ref<Tag[]>([...props.tags]);

const tagOptions = computed(() =>
    localTags.value.map((tag) => ({ label: tag.name, value: tag.id }))
);

const sponsorOptions = computed(() =>
    props.sponsors?.map((sponsor) => ({ label: sponsor.name, value: sponsor.id })) || []
);

// Create a new tag inline (for multi-select tags)
async function onCreateTag(name: string) {
    const newTag = await createTag(name);
    if (newTag) {
        form.tags.push(newTag.id);
    }
}

// Create a new featured tag inline (for single-select)
async function onCreateFeaturedTag(name: string) {
    const newTag = await createTag(name);
    if (newTag) {
        form.featured_tag_id = newTag.id;
    }
}

// Shared function to create a tag
async function createTag(name: string): Promise<{ id: number; name: string; slug: string } | null> {
    try {
        const response = await axios.post(cmsPath('/tags'), {
            name: { [props.language.code]: name },
        });
        const newTag = response.data;
        // Add to local tags list
        localTags.value.push({ id: newTag.id, name: newTag.name, slug: newTag.slug });
        toast.add({
            title: 'Tag Created',
            description: `Tag "${name}" has been created.`,
            color: 'success',
        });
        return newTag;
    } catch (error: any) {
        toast.add({
            title: 'Error',
            description: error.response?.data?.message || 'Failed to create tag',
            color: 'error',
        });
        return null;
    }
}

function removeFeaturedMedia() {
    selectedFeaturedMedia.value = null;
    form.featured_media_id = null;
}


function generateSlug() {
    form.slug = form.title
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
}

// Auto-generate slug as user types (only if slug hasn't been manually edited)
const slugManuallyEdited = ref(false);

watch(() => form.title, () => {
    if (!slugManuallyEdited.value) {
        generateSlug();
    }
});

function onSlugInput() {
    slugManuallyEdited.value = true;
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

function submit(status?: string) {
    if (status) {
        form.status = status;
    }

    form.post(cmsPath(`/posts/${props.language.code}`), {
        forceFormData: true,
    });
}

function goBack() {
    router.visit(cmsPath(`/posts/${props.language.code}`));
}
</script>

<template>
    <Head title="Create Post" />

    <DashboardLayout>
        <UDashboardPanel id="create-post" :ui="{ body: 'p-0 gap-0' }">
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
                            <span class="text-sm text-muted">New Post</span>
                            <UBadge
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
                            <!-- Save status -->
                            <span v-if="isSaving" class="text-xs text-muted flex items-center gap-1">
                                <UIcon name="i-lucide-loader-2" class="size-3 animate-spin" />
                                Saving...
                            </span>
                            <span v-else-if="hasUnsavedChanges" class="text-xs text-warning flex items-center gap-1.5 hidden sm:flex">
                                <span class="size-1.5 rounded-full bg-warning"></span>
                                Unsaved changes
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
                                @click="submit('draft')"
                            >
                                Save Draft
                            </UButton>
                            <UButton
                                size="sm"
                                :loading="form.processing"
                                @click="submit('published')"
                            >
                                Publish
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
                            <!-- Kicker -->
                            <input
                                v-model="form.kicker"
                                type="text"
                                :placeholder="placeholders.kicker"
                                :dir="textDirection"
                                :class="[
                                    'w-full bg-transparent border-0 outline-none placeholder:text-muted/30 mb-3 uppercase tracking-wider',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-sm placeholder:font-dhivehi' : 'text-sm font-medium',
                                ]"
                                @keydown="onDhivehiKeyDown"
                            />

                            <!-- Title -->
                            <textarea
                                ref="titleTextarea"
                                v-model="form.title"
                                :placeholder="placeholders.title"
                                :dir="textDirection"
                                rows="1"
                                maxlength="70"
                                :class="[
                                    'w-full font-bold bg-transparent border-0 outline-none placeholder:text-muted/40 mb-1 resize-none overflow-hidden',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-4xl leading-relaxed placeholder:font-dhivehi' : 'text-4xl leading-tight',
                                ]"
                                @input="autoResizeTitle"
                                @keydown="onDhivehiKeyDown"
                            />
                            <div class="flex items-center justify-between mb-3">
                                <p v-if="form.errors.title" class="text-error text-xs">{{ form.errors.title }}</p>
                                <span v-else />
                                <span :class="['text-xs', form.title.length > 70 ? 'text-error' : 'text-muted']">
                                    {{ 70 - form.title.length }} left
                                </span>
                            </div>

                            <!-- Separator between headline and description -->
                            <div class="h-px bg-gray-200 dark:bg-gray-700 my-6" />

                            <!-- Subtitle -->
                            <textarea
                                ref="subtitleTextarea"
                                v-model="form.subtitle"
                                :placeholder="placeholders.subtitle"
                                :dir="textDirection"
                                rows="1"
                                maxlength="120"
                                :class="[
                                    'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 mb-1 resize-none overflow-hidden',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-xl leading-relaxed placeholder:font-dhivehi' : 'text-xl',
                                ]"
                                @input="autoResizeSubtitle"
                                @keydown="onDhivehiKeyDown"
                            />
                            <div class="flex items-center justify-end mb-4">
                                <span :class="['text-xs', (form.subtitle?.length || 0) > 120 ? 'text-error' : 'text-muted']">
                                    {{ 120 - (form.subtitle?.length || 0) }} left
                                </span>
                            </div>

                            <!-- Excerpt / Deck -->
                            <textarea
                                ref="excerptTextarea"
                                v-model="form.excerpt"
                                :placeholder="placeholders.excerpt"
                                rows="1"
                                maxlength="160"
                                :dir="textDirection"
                                :class="[
                                    'w-full text-muted bg-transparent border-0 outline-none placeholder:text-muted/30 mb-1 resize-none overflow-hidden',
                                    textAlign,
                                    isRtl ? 'font-dhivehi text-dhivehi-base leading-relaxed placeholder:font-dhivehi' : 'text-base',
                                ]"
                                @input="autoResizeExcerpt"
                                @keydown="onDhivehiKeyDown"
                            />
                            <div class="flex items-center justify-end">
                                <span :class="['text-xs', (form.excerpt?.length || 0) > 160 ? 'text-error' : 'text-muted']">
                                    {{ 160 - (form.excerpt?.length || 0) }} left
                                </span>
                            </div>

                            <!-- Separator between header and content -->
                            <div class="h-px bg-gray-200 dark:bg-gray-700 my-8" />

                            <!-- Content Editor -->
                            <BlockEditor
                                v-model="form.content"
                                placeholder="Tell your story..."
                                :rtl="isRtl"
                                :dhivehi-enabled="dhivehiEnabled"
                                :dhivehi-layout="dhivehiLayout"
                                :on-select-media="handleEditorSelectMedia"
                            />

                            <!-- Custom Fields Section (for post types with additional fields) -->
                            <div v-if="currentPostType?.fields?.length" class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="flex items-center justify-center size-9 rounded-full bg-primary/10">
                                        <UIcon :name="currentPostType.icon || 'i-lucide-file-text'" class="size-4 text-primary" />
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-highlighted">{{ currentPostType.label }} Details</h3>
                                        <p class="text-xs text-muted">Additional information specific to this content type</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <template v-for="field in currentPostType.fields" :key="field.name">
                                        <!-- Text Field -->
                                        <div v-if="field.type === 'text'">
                                            <label class="text-sm font-medium mb-1.5 block">{{ field.label }}</label>
                                            <UInput
                                                :model-value="(form.custom_fields?.[field.name] as string) ?? ''"
                                                class="w-full"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Number Field -->
                                        <div v-else-if="field.type === 'number'">
                                            <label class="text-sm font-medium mb-1.5 block">
                                                {{ field.label }}
                                                <span v-if="field.suffix" class="text-muted font-normal">({{ field.suffix }})</span>
                                            </label>
                                            <UInput
                                                :model-value="(form.custom_fields?.[field.name] as number | null) ?? null"
                                                type="number"
                                                min="0"
                                                class="w-full"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Textarea Field -->
                                        <div v-else-if="field.type === 'textarea'">
                                            <label class="text-sm font-medium mb-1.5 block">{{ field.label }}</label>
                                            <UTextarea
                                                :model-value="(form.custom_fields?.[field.name] as string) ?? ''"
                                                :rows="4"
                                                class="w-full"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Select Field -->
                                        <div v-else-if="field.type === 'select'">
                                            <label class="text-sm font-medium mb-1.5 block">{{ field.label }}</label>
                                            <USelectMenu
                                                :model-value="(form.custom_fields?.[field.name] as string) ?? ''"
                                                :items="(field.options ?? []).map(opt => ({ label: opt, value: opt }))"
                                                value-key="value"
                                                class="w-full"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Toggle Field -->
                                        <div v-else-if="field.type === 'toggle'" class="flex items-center justify-between p-3 rounded-lg bg-elevated/50 border border-default">
                                            <label class="text-sm font-medium">{{ field.label }}</label>
                                            <USwitch
                                                :model-value="(form.custom_fields?.[field.name] as boolean) ?? false"
                                                @update:model-value="form.custom_fields = { ...form.custom_fields, [field.name]: $event }"
                                            />
                                        </div>

                                        <!-- Repeater Field -->
                                        <div v-else-if="field.type === 'repeater'">
                                            <label class="text-sm font-medium mb-1.5 block">{{ field.label }}</label>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="(item, index) in ((form.custom_fields?.[field.name] as string[]) ?? [])"
                                                    :key="index"
                                                    class="flex gap-2"
                                                >
                                                    <UInput
                                                        :model-value="item"
                                                        class="flex-1"
                                                        @update:model-value="form.custom_fields = {
                                                            ...form.custom_fields,
                                                            [field.name]: ((form.custom_fields?.[field.name] as string[]) ?? []).map((v, i) => i === index ? $event : v)
                                                        }"
                                                    />
                                                    <UButton
                                                        color="neutral"
                                                        variant="ghost"
                                                        icon="i-lucide-x"
                                                        @click="form.custom_fields = {
                                                            ...form.custom_fields,
                                                            [field.name]: ((form.custom_fields?.[field.name] as string[]) ?? []).filter((_, i) => i !== index)
                                                        }"
                                                    />
                                                </div>
                                                <div class="flex gap-2">
                                                    <UInput
                                                        :id="`custom-repeater-${field.name}`"
                                                        placeholder="Add new item..."
                                                        class="flex-1"
                                                        @keyup.enter.prevent="(e: KeyboardEvent) => {
                                                            const input = e.target as HTMLInputElement;
                                                            if (input.value.trim()) {
                                                                form.custom_fields = {
                                                                    ...form.custom_fields,
                                                                    [field.name]: [...((form.custom_fields?.[field.name] as string[]) ?? []), input.value.trim()]
                                                                };
                                                                input.value = '';
                                                            }
                                                        }"
                                                    />
                                                    <UButton
                                                        color="neutral"
                                                        variant="soft"
                                                        icon="i-lucide-plus"
                                                        @click="() => {
                                                            const input = document.getElementById(`custom-repeater-${field.name}`) as HTMLInputElement;
                                                            if (input?.value.trim()) {
                                                                form.custom_fields = {
                                                                    ...form.custom_fields,
                                                                    [field.name]: [...((form.custom_fields?.[field.name] as string[]) ?? []), input.value.trim()]
                                                                };
                                                                input.value = '';
                                                            }
                                                        }"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
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
                                            <UInput v-model="form.slug" placeholder="post-slug" size="sm" class="flex-1 min-w-0" @input="onSlugInput" />
                                            <UButton size="sm" color="neutral" variant="ghost" icon="i-lucide-refresh-cw" @click="slugManuallyEdited = false; generateSlug()" />
                                        </div>
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
                                <div v-if="selectedFeaturedMedia" class="relative mb-2">
                                    <img
                                        :src="selectedFeaturedMedia.thumbnail_url || selectedFeaturedMedia.url || ''"
                                        :alt="selectedFeaturedMedia.title || 'Cover'"
                                        class="w-full h-28 object-cover rounded-lg"
                                    />
                                    <UButton
                                        color="neutral"
                                        variant="solid"
                                        icon="i-lucide-x"
                                        size="xs"
                                        class="absolute top-1.5 right-1.5"
                                        @click="removeFeaturedMedia"
                                    />
                                    <div v-if="selectedFeaturedMedia.credit_display" class="absolute bottom-1.5 left-1.5 right-1.5">
                                        <span class="text-xs text-white bg-black/50 px-1.5 py-0.5 rounded">
                                            {{ selectedFeaturedMedia.credit_display.name }}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="w-full border border-dashed border-default rounded-lg px-3 py-4 text-center hover:border-primary hover:bg-primary/5 transition-colors"
                                    @click="openCoverPicker"
                                >
                                    <UIcon name="i-lucide-image-plus" class="size-5 text-muted mx-auto mb-1" />
                                    <p class="text-xs text-muted">{{ selectedFeaturedMedia ? 'Change cover' : 'Select cover image' }}</p>
                                </button>
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
                                        <label class="text-xs text-muted mb-1 block">Category <span class="text-error">*</span></label>
                                        <USelectMenu
                                            v-model="form.category_id"
                                            :items="flattenedCategories"
                                            value-key="value"
                                            placeholder="Select category..."
                                            size="sm"
                                            class="w-full"
                                        />
                                        <p v-if="form.errors.category_id" class="text-error text-xs mt-1">{{ form.errors.category_id }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Featured Tag <span class="text-error">*</span></label>
                                        <USelectMenu
                                            v-model="form.featured_tag_id"
                                            :items="tagOptions"
                                            value-key="value"
                                            placeholder="Select or create..."
                                            searchable
                                            create-item
                                            size="sm"
                                            class="w-full"
                                            @create="onCreateFeaturedTag"
                                        />
                                        <p v-if="form.errors.featured_tag_id" class="text-error text-xs mt-1">{{ form.errors.featured_tag_id }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Tags</label>
                                        <USelectMenu
                                            v-model="form.tags"
                                            :items="tagOptions"
                                            value-key="value"
                                            multiple
                                            placeholder="Select or create..."
                                            searchable
                                            create-item
                                            size="sm"
                                            class="w-full"
                                            @create="onCreateTag"
                                        />
                                    </div>
                                    <!-- Display Options -->
                                    <div class="pt-3 border-t border-default mt-3">
                                        <label class="text-xs text-muted mb-2 block">Display Options</label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                v-model="form.show_author"
                                                class="rounded border-default text-primary focus:ring-primary"
                                            />
                                            <span class="text-sm">Show author name</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Sponsorship -->
                            <div v-if="sponsorOptions.length > 0">
                                <div class="flex items-center gap-2 mb-3">
                                    <UIcon name="i-lucide-handshake" class="size-4 text-muted" />
                                    <span class="text-xs font-medium text-muted uppercase tracking-wider">Sponsorship</span>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-xs text-muted mb-1 block">Sponsor</label>
                                        <USelectMenu
                                            v-model="form.sponsor_id"
                                            :items="sponsorOptions"
                                            value-key="value"
                                            placeholder="Select sponsor (optional)..."
                                            searchable
                                            size="sm"
                                            class="w-full"
                                        />
                                    </div>
                                </div>
                            </div>

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

                        </div>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Unified Media Picker Modal -->
        <MediaPickerModal
            v-model:open="mediaPickerOpen"
            :type="mediaPickerType"
            :multiple="mediaPickerMultiple"
            default-category="media"
            @select="handleMediaSelect"
            @update:open="handleMediaPickerClose"
        />
    </DashboardLayout>
</template>

<style scoped>
input::placeholder,
textarea::placeholder {
    opacity: 1;
}
</style>
