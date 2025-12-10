<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePermission } from '../composables/usePermission';
import { formatDistanceToNow } from 'date-fns';
import DhivehiInput from './DhivehiInput.vue';
import MediaCropCreator from './MediaCropCreator.vue';

interface Tag {
    id: number;
    name: string | Record<string, string>;
    slug: string;
}

interface Language {
    id: number;
    code: string;
    name: string;
    native_name: string;
    direction: 'ltr' | 'rtl';
    is_default: boolean;
}

interface User {
    id: number;
    name: string;
    role: string | null;
}

interface MediaItem {
    id: number;
    uuid: string;
    type: 'image' | 'video_local' | 'video_embed';
    url: string | null;
    thumbnail_url: string | null;
    embed_url: string | null;
    embed_provider: string | null;
    embed_video_id: string | null;
    title: string | null;
    title_translations: Record<string, string>;
    caption: string | null;
    caption_translations: Record<string, string>;
    description: string | null;
    description_translations: Record<string, string>;
    alt_text: string | null;
    alt_text_translations: Record<string, string>;
    credit_user_id: number | null;
    credit_name: string | null;
    credit_url: string | null;
    credit_role: string | null;
    credit_display: {
        name: string;
        url: string | null;
        role: string | null;
        is_user: boolean;
        user_id: number | null;
    } | null;
    width: number | null;
    height: number | null;
    file_size: number | null;
    mime_type: string | null;
    is_image: boolean;
    is_video: boolean;
    folder: {
        id: number;
        uuid: string;
        name: string;
        path: string;
    } | null;
    folder_id: number | null;
    tags: Tag[];
    tag_ids: number[];
    uploaded_by: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    open: boolean;
    media: MediaItem | null;
    tags: Tag[];
    languages: Language[];
    users: User[];
    creditRoles: Record<string, string>;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'delete': [media: MediaItem];
}>();

const { can } = usePermission();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

// Form state
const activeTab = ref<string | number>('details');
const selectedLanguage = ref('en');
const isSaving = ref(false);

// Form fields
const title = ref<Record<string, string>>({});
const caption = ref<Record<string, string>>({});
const selectedTagIds = ref<number[]>([]);
const creditType = ref<'user' | 'external'>('external');
const creditUserId = ref<string>('');
const creditName = ref('');
const creditUrl = ref('');
const creditRole = ref('');

// Tab items with slot property (computed to conditionally show Crops tab for images)
const tabItems = computed(() => {
    const items = [
        { label: 'Details', value: 'details', icon: 'i-lucide-info', slot: 'details' as const },
        { label: 'Credits', value: 'credits', icon: 'i-lucide-user', slot: 'credits' as const },
    ];

    // Only show Crops tab for images
    if (props.media?.is_image) {
        items.push({ label: 'Crops', value: 'crops', icon: 'i-lucide-crop', slot: 'crops' as const });
    }

    return items;
});

// Check if current language is RTL
const isCurrentLangRtl = computed(() => {
    const lang = props.languages.find(l => l.code === selectedLanguage.value);
    return lang?.direction === 'rtl';
});

// Check if current language is Dhivehi (for special keyboard)
const isDhivehi = computed(() => selectedLanguage.value === 'dv');

// Initialize form when media changes
watch(() => props.media, (media) => {
    if (media) {
        title.value = { ...media.title_translations };
        caption.value = { ...media.caption_translations };
        selectedTagIds.value = [...(media.tag_ids || [])];

        if (media.credit_user_id) {
            creditType.value = 'user';
            creditUserId.value = String(media.credit_user_id);
            creditName.value = '';
            creditUrl.value = '';
        } else {
            creditType.value = 'external';
            creditUserId.value = '';
            creditName.value = media.credit_name || '';
            creditUrl.value = media.credit_url || '';
        }
        creditRole.value = media.credit_role || '';

        // Set default language
        const defaultLang = props.languages.find(l => l.is_default);
        selectedLanguage.value = defaultLang?.code || 'en';

        // Reset to details tab
        activeTab.value = 'details';
    }
}, { immediate: true });

// Tag options for multi-select
const tagOptions = computed(() => {
    return props.tags.map(tag => ({
        value: tag.id,
        label: typeof tag.name === 'string' ? tag.name : (tag.name?.en || tag.slug),
    }));
});

// User options
const userOptions = computed(() => {
    return props.users.map(u => ({
        value: String(u.id),
        label: u.name,
    }));
});

// Auto-assign role when user is selected
watch(creditUserId, (newUserId) => {
    if (newUserId && creditType.value === 'user') {
        const selectedUser = props.users.find(u => String(u.id) === newUserId);
        if (selectedUser?.role) {
            creditRole.value = selectedUser.role;
        }
    }
});

// Credit role options
const creditRoleOptions = computed(() => {
    return Object.entries(props.creditRoles).map(([value, label]) => ({
        value,
        label,
    }));
});

function formatFileSize(bytes: number | null): string {
    if (!bytes) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let unitIndex = 0;
    let size = bytes;
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${size.toFixed(1)} ${units[unitIndex]}`;
}

function getEmbedIframeUrl(): string | null {
    if (!props.media || props.media.type !== 'video_embed' || !props.media.embed_video_id) {
        return null;
    }

    switch (props.media.embed_provider) {
        case 'youtube':
            return `https://www.youtube.com/embed/${props.media.embed_video_id}`;
        case 'vimeo':
            return `https://player.vimeo.com/video/${props.media.embed_video_id}`;
        default:
            return null;
    }
}

function save() {
    if (!props.media) return;

    isSaving.value = true;

    const data: Record<string, any> = {
        title: title.value,
        caption: caption.value,
        tag_ids: selectedTagIds.value,
        credit_role: creditRole.value || null,
    };

    if (creditType.value === 'user' && creditUserId.value) {
        data.credit_user_id = creditUserId.value;
        data.credit_name = null;
        data.credit_url = null;
    } else {
        data.credit_user_id = null;
        data.credit_name = creditName.value || null;
        data.credit_url = creditUrl.value || null;
    }

    router.put(`/cms/media/${props.media.uuid}`, data, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
}

function handleDelete() {
    if (props.media) {
        emit('delete', props.media);
    }
}

function setCreditType(type: 'user' | 'external') {
    creditType.value = type;
}
</script>

<template>
    <USlideover v-model:open="isOpen" :ui="{ width: 'max-w-xl' }">
        <template #content>
            <div v-if="media" class="flex flex-col h-full bg-default">
                <!-- Header -->
                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-default">
                    <div>
                        <h2 class="text-lg font-semibold text-highlighted">
                            {{ media.is_video ? 'Video' : 'Image' }} Details
                        </h2>
                        <p class="text-sm text-muted mt-0.5">
                            {{ media.mime_type || 'Embedded Video' }}
                        </p>
                    </div>
                    <UButton
                        icon="i-lucide-x"
                        color="neutral"
                        variant="ghost"
                        @click="isOpen = false"
                    />
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <!-- Preview -->
                    <div class="mb-6">
                        <div class="rounded-lg overflow-hidden bg-muted/30 border border-default">
                            <!-- Image Preview -->
                            <img
                                v-if="media.is_image && media.url"
                                :src="media.url"
                                :alt="media.title || 'Media'"
                                class="w-full max-h-64 object-contain"
                            />

                            <!-- Video Embed Preview -->
                            <div
                                v-else-if="media.type === 'video_embed' && getEmbedIframeUrl()"
                                class="aspect-video"
                            >
                                <iframe
                                    :src="getEmbedIframeUrl()!"
                                    class="w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                />
                            </div>

                            <!-- Video Local Preview -->
                            <div
                                v-else-if="media.is_video && media.thumbnail_url"
                                class="aspect-video relative"
                            >
                                <img
                                    :src="media.thumbnail_url"
                                    :alt="media.title || 'Video'"
                                    class="w-full h-full object-cover"
                                />
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="rounded-full bg-black/50 p-4">
                                        <UIcon name="i-lucide-play" class="size-8 text-white" />
                                    </div>
                                </div>
                            </div>

                            <!-- Fallback -->
                            <div
                                v-else
                                class="aspect-video flex items-center justify-center"
                            >
                                <UIcon
                                    :name="media.is_video ? 'i-lucide-video' : 'i-lucide-image'"
                                    class="size-16 text-muted"
                                />
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-sm text-muted">
                            <span v-if="media.width && media.height">
                                {{ media.width }} x {{ media.height }}
                            </span>
                            <span v-if="media.file_size">
                                {{ formatFileSize(media.file_size) }}
                            </span>
                            <span v-if="media.uploaded_by">
                                Uploaded by {{ media.uploaded_by.name }}
                            </span>
                            <span>
                                {{ formatDistanceToNow(new Date(media.created_at), { addSuffix: true }) }}
                            </span>
                        </div>
                    </div>

                    <!-- Tabs with slot-based content -->
                    <UTabs
                        v-model="activeTab"
                        :items="tabItems"
                        :unmount-on-hide="false"
                        class="w-full"
                    >
                        <!-- Details Tab -->
                        <template #details>
                            <div class="space-y-4 pt-4">
                                <!-- Language Selector -->
                                <div class="flex gap-1 border-b border-default pb-2 mb-4">
                                    <UButton
                                        v-for="lang in languages"
                                        :key="lang.code"
                                        type="button"
                                        :color="selectedLanguage === lang.code ? 'primary' : 'neutral'"
                                        :variant="selectedLanguage === lang.code ? 'soft' : 'ghost'"
                                        size="sm"
                                        @click="selectedLanguage = lang.code"
                                    >
                                        {{ lang.name }}
                                    </UButton>
                                </div>

                                <!-- Title -->
                                <UFormField label="Title">
                                    <DhivehiInput
                                        v-if="isDhivehi"
                                        v-model="title[selectedLanguage]"
                                        placeholder="ސުރުޚީ ލިޔުއްވާ"
                                        :default-enabled="true"
                                        :show-toggle="false"
                                    />
                                    <UInput
                                        v-else
                                        v-model="title[selectedLanguage]"
                                        placeholder="Enter title..."
                                        class="w-full"
                                        :dir="isCurrentLangRtl ? 'rtl' : 'ltr'"
                                    />
                                </UFormField>

                                <!-- Caption -->
                                <UFormField label="Caption">
                                    <DhivehiInput
                                        v-if="isDhivehi"
                                        v-model="caption[selectedLanguage]"
                                        placeholder="ކުރު ތަފްޞީލް"
                                        type="textarea"
                                        :rows="3"
                                        :default-enabled="true"
                                        :show-toggle="false"
                                    />
                                    <UTextarea
                                        v-else
                                        v-model="caption[selectedLanguage]"
                                        placeholder="Short caption..."
                                        :rows="3"
                                        class="w-full"
                                        :dir="isCurrentLangRtl ? 'rtl' : 'ltr'"
                                    />
                                </UFormField>

                                <USeparator />

                                <!-- Tags -->
                                <UFormField label="Tags">
                                    <USelectMenu
                                        v-model="selectedTagIds"
                                        :items="tagOptions"
                                        value-key="value"
                                        placeholder="Select tags..."
                                        multiple
                                        searchable
                                        class="w-full"
                                    />
                                </UFormField>
                            </div>
                        </template>

                        <!-- Credits Tab -->
                        <template #credits>
                            <div class="space-y-4 pt-4">
                                <!-- Credit Type Toggle -->
                                <div class="flex gap-2">
                                    <UButton
                                        type="button"
                                        :color="creditType === 'external' ? 'primary' : 'neutral'"
                                        :variant="creditType === 'external' ? 'soft' : 'ghost'"
                                        size="sm"
                                        @click.stop="setCreditType('external')"
                                    >
                                        External Credit
                                    </UButton>
                                    <UButton
                                        type="button"
                                        :color="creditType === 'user' ? 'primary' : 'neutral'"
                                        :variant="creditType === 'user' ? 'soft' : 'ghost'"
                                        size="sm"
                                        @click.stop="setCreditType('user')"
                                    >
                                        CMS User
                                    </UButton>
                                </div>

                                <!-- CMS User Credit -->
                                <template v-if="creditType === 'user'">
                                    <UFormField label="Select User">
                                        <USelectMenu
                                            v-model="creditUserId"
                                            :items="userOptions"
                                            value-key="value"
                                            placeholder="Select a user..."
                                            searchable
                                            class="w-full"
                                        />
                                    </UFormField>
                                </template>

                                <!-- External Credit -->
                                <template v-else>
                                    <UFormField label="Name">
                                        <UInput
                                            v-model="creditName"
                                            placeholder="Photographer/Creator name"
                                            class="w-full"
                                        />
                                    </UFormField>

                                    <UFormField label="Website">
                                        <UInput
                                            v-model="creditUrl"
                                            placeholder="https://..."
                                            icon="i-lucide-link"
                                            class="w-full"
                                        />
                                    </UFormField>
                                </template>

                                <!-- Role -->
                                <UFormField label="Role">
                                    <USelectMenu
                                        v-model="creditRole"
                                        :items="creditRoleOptions"
                                        value-key="value"
                                        placeholder="Select role..."
                                        class="w-full"
                                    />
                                </UFormField>
                            </div>
                        </template>

                        <!-- Crops Tab (only for images) -->
                        <template v-if="media?.is_image" #crops>
                            <div class="pt-4">
                                <MediaCropCreator
                                    :media-uuid="media.uuid"
                                    :image-url="media.url!"
                                    :image-width="media.width!"
                                    :image-height="media.height!"
                                />
                            </div>
                        </template>
                    </UTabs>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between gap-2 px-6 py-4 border-t border-default">
                    <UButton
                        v-if="can('media.delete')"
                        type="button"
                        icon="i-lucide-trash"
                        color="error"
                        variant="ghost"
                        @click="handleDelete"
                    >
                        Delete
                    </UButton>

                    <div class="flex items-center gap-2 ml-auto">
                        <UButton
                            type="button"
                            color="neutral"
                            variant="ghost"
                            @click="isOpen = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            v-if="can('media.edit')"
                            type="button"
                            color="primary"
                            :loading="isSaving"
                            @click="save"
                        >
                            Save Changes
                        </UButton>
                    </div>
                </div>
            </div>
        </template>
    </USlideover>
</template>
