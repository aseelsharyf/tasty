<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import DhivehiInput from './DhivehiInput.vue';
import PostSearch from './PostSearch.vue';
import type { Language, CollectionPost } from '../types';
import { useCmsPath } from '../composables/useCmsPath';

interface CollectionWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    description?: string;
    description_translations?: Record<string, string>;
    slug?: string;
    is_active?: boolean;
    order?: number;
    sort_order?: string;
    posts?: CollectionPost[];
    categories?: number[];
    tags?: number[];
}

const props = withDefaults(defineProps<{
    collection?: CollectionWithTranslations;
    languages: Language[];
    allCategories: { id: number; name: string; slug: string }[];
    allTags: { id: number; name: string; slug: string }[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const { cmsPath } = useCmsPath();

const isEditing = computed(() => props.mode === 'edit' && props.collection?.uuid);
const activeTab = ref(props.languages[0]?.code || 'en');

function initTranslations(field: 'name' | 'description'): Record<string, string> {
    const translations: Record<string, string> = {};
    const source = field === 'name' ? props.collection?.name_translations : props.collection?.description_translations;
    props.languages.forEach(lang => {
        translations[lang.code] = source?.[lang.code] || '';
    });
    return translations;
}

const form = useForm({
    name: initTranslations('name'),
    description: initTranslations('description'),
    slug: props.collection?.slug || '',
    is_active: props.collection?.is_active ?? true,
    order: props.collection?.order ?? 0,
    sort_order: props.collection?.sort_order || 'manual',
    posts: (props.collection?.posts?.map(p => p.id) || []) as number[],
    categories: (props.collection?.categories || []) as number[],
    tags: (props.collection?.tags || []) as number[],
});

const sortOrderOptions = [
    { label: 'Manual (drag to reorder)', value: 'manual' },
    { label: 'Most Recent First', value: 'recent' },
    { label: 'Oldest First', value: 'oldest' },
    { label: 'Most Viewed', value: 'most_viewed' },
    { label: 'Title A-Z', value: 'title_asc' },
    { label: 'Title Z-A', value: 'title_desc' },
];

const selectedPosts = ref<CollectionPost[]>(props.collection?.posts || []);

// Auto-generate slug from first language name (only in create mode)
watch(() => form.name[props.languages[0]?.code || 'en'], (newName) => {
    if (props.mode === 'create' && newName) {
        const currentSlug = form.slug;
        const previousName = form.name[props.languages[0]?.code || 'en']?.slice(0, -1) || '';
        if (!currentSlug || currentSlug === slugify(previousName)) {
            form.slug = slugify(newName);
        }
    }
});

function slugify(text: string): string {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function onPostSelected(post: CollectionPost) {
    if (!selectedPosts.value.find(p => p.id === post.id)) {
        selectedPosts.value.push(post);
        form.posts.push(post.id);
    }
}

function removePost(postId: number) {
    selectedPosts.value = selectedPosts.value.filter(p => p.id !== postId);
    form.posts = form.posts.filter(id => id !== postId);
}

function movePost(index: number, direction: 'up' | 'down') {
    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex < 0 || newIndex >= selectedPosts.value.length) return;

    const posts = [...selectedPosts.value];
    [posts[index], posts[newIndex]] = [posts[newIndex], posts[index]];
    selectedPosts.value = posts;
    form.posts = posts.map(p => p.id);
}

const categoryOptions = computed(() =>
    props.allCategories.map(c => ({ label: c.name, value: c.id }))
);

const tagOptions = computed(() =>
    props.allTags.map(t => ({ label: t.name, value: t.id }))
);

const hasAutoRules = computed(() => form.categories.length > 0 || form.tags.length > 0);
const hasPinnedPosts = computed(() => selectedPosts.value.length > 0);

function onSubmit() {
    const nameData = Object.fromEntries(
        Object.entries(form.name).filter(([_, v]) => v?.trim())
    );
    const descData = Object.fromEntries(
        Object.entries(form.description).filter(([_, v]) => v?.trim())
    );

    form.transform(() => ({
        name: nameData,
        description: Object.keys(descData).length > 0 ? descData : null,
        slug: form.slug,
        is_active: form.is_active,
        order: form.order,
        sort_order: form.sort_order,
        posts: form.posts,
        categories: form.categories,
        tags: form.tags,
    }));

    if (isEditing.value && props.collection?.uuid) {
        form.put(cmsPath(`/collections/${props.collection.uuid}`), {
            preserveScroll: true,
        });
    } else {
        form.post(cmsPath('/collections'), {
            preserveScroll: true,
        });
    }
}

function hasTranslation(langCode: string): boolean {
    return !!(form.name[langCode]?.trim());
}

const isCurrentRtl = computed(() => {
    const lang = props.languages.find(l => l.code === activeTab.value);
    return lang?.direction === 'rtl';
});

const isDhivehi = computed(() => activeTab.value === 'dv');
</script>

<template>
    <UForm
        :state="form"
        @submit="onSubmit"
    >
        <!-- Top bar: Name + Actions -->
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex-1 max-w-xl">
                <div class="flex items-center gap-3 mb-2">
                    <!-- Language switcher -->
                    <div v-if="languages.length > 1" class="flex gap-0.5 bg-elevated rounded-md p-0.5">
                        <button
                            v-for="lang in languages"
                            :key="lang.code"
                            type="button"
                            :class="[
                                'px-2 py-1 text-xs font-medium rounded transition-colors',
                                activeTab === lang.code
                                    ? 'bg-default text-highlighted shadow-sm'
                                    : 'text-muted hover:text-highlighted',
                            ]"
                            @click="activeTab = lang.code"
                        >
                            {{ lang.code.toUpperCase() }}
                            <span
                                :class="['inline-block size-1.5 rounded-full ml-1', hasTranslation(lang.code) ? 'bg-success' : 'bg-muted/30']"
                            />
                        </button>
                    </div>
                    <USwitch
                        v-model="form.is_active"
                        :disabled="form.processing"
                    />
                    <span class="text-xs" :class="form.is_active ? 'text-success' : 'text-muted'">
                        {{ form.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <DhivehiInput
                    v-if="isDhivehi"
                    v-model="form.name[activeTab]"
                    placeholder="ކަލެކްޝަން ނަން ލިޔުއްވާ"
                    :disabled="form.processing"
                    :default-enabled="true"
                    :show-toggle="false"
                    class="w-full text-lg"
                />
                <UInput
                    v-else
                    v-model="form.name[activeTab]"
                    :placeholder="'Collection name...'"
                    class="w-full"
                    size="xl"
                    :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                    :disabled="form.processing"
                    variant="none"
                    :ui="{ base: 'text-xl font-semibold' }"
                />
                <p v-if="form.errors.name" class="text-xs text-error mt-1">{{ form.errors.name }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0 pt-8">
                <UButton
                    color="neutral"
                    variant="outline"
                    :disabled="form.processing"
                    :to="cmsPath('/collections')"
                >
                    Cancel
                </UButton>
                <UButton
                    type="submit"
                    :loading="form.processing"
                >
                    {{ isEditing ? 'Save Changes' : 'Create Collection' }}
                </UButton>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Description -->
                <div>
                    <UTextarea
                        v-model="form.description[activeTab]"
                        placeholder="Add a description for this collection..."
                        class="w-full"
                        :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                        :disabled="form.processing"
                        :rows="2"
                        variant="none"
                    />
                </div>

                <!-- Auto-populate Rules -->
                <div class="rounded-xl border border-default overflow-hidden">
                    <div class="px-5 py-3.5 bg-elevated/50 border-b border-default">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-highlighted">Auto-populate Rules</h3>
                                <p class="text-xs text-muted mt-0.5">Posts matching these categories or tags are automatically included</p>
                            </div>
                            <UBadge v-if="hasAutoRules" color="primary" variant="subtle" size="xs">
                                {{ form.categories.length + form.tags.length }} rules
                            </UBadge>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <UFormField label="Categories" name="categories">
                                <USelectMenu
                                    v-model="form.categories"
                                    :items="categoryOptions"
                                    multiple
                                    placeholder="Any category..."
                                    class="w-full"
                                    value-key="value"
                                    :ui="{ content: 'z-50' }"
                                />
                            </UFormField>
                            <UFormField label="Tags" name="tags">
                                <USelectMenu
                                    v-model="form.tags"
                                    :items="tagOptions"
                                    multiple
                                    placeholder="Any tag..."
                                    class="w-full"
                                    value-key="value"
                                    :ui="{ content: 'z-50' }"
                                />
                            </UFormField>
                        </div>
                        <p v-if="hasAutoRules && hasPinnedPosts" class="text-xs text-muted mt-3 flex items-center gap-1.5">
                            <UIcon name="i-lucide-info" class="size-3.5 shrink-0" />
                            Pinned posts appear first, then auto-populated posts from matching categories/tags.
                        </p>
                    </div>
                </div>

                <!-- Pinned Posts -->
                <div class="rounded-xl border border-default overflow-hidden">
                    <div class="px-5 py-3.5 bg-elevated/50 border-b border-default">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-highlighted">Pinned Posts</h3>
                                <p class="text-xs text-muted mt-0.5">Manually curated posts that always appear in this collection</p>
                            </div>
                            <UBadge v-if="hasPinnedPosts" color="neutral" variant="subtle" size="xs">
                                {{ selectedPosts.length }}
                            </UBadge>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="relative">
                            <PostSearch
                                :exclude-ids="form.posts"
                                @select="onPostSelected"
                            />
                        </div>

                        <div v-if="selectedPosts.length > 0" class="mt-4 space-y-1">
                            <div
                                v-for="(post, index) in selectedPosts"
                                :key="post.id"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-elevated/50 transition-colors group"
                            >
                                <span class="text-[10px] text-muted font-mono w-4 text-right shrink-0">{{ index + 1 }}</span>

                                <div class="flex flex-col gap-px opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        type="button"
                                        :disabled="index === 0"
                                        class="text-muted hover:text-highlighted disabled:opacity-20 leading-none"
                                        @click="movePost(index, 'up')"
                                    >
                                        <UIcon name="i-lucide-chevron-up" class="size-3" />
                                    </button>
                                    <button
                                        type="button"
                                        :disabled="index === selectedPosts.length - 1"
                                        class="text-muted hover:text-highlighted disabled:opacity-20 leading-none"
                                        @click="movePost(index, 'down')"
                                    >
                                        <UIcon name="i-lucide-chevron-down" class="size-3" />
                                    </button>
                                </div>

                                <img
                                    v-if="post.featured_image_thumb"
                                    :src="post.featured_image_thumb"
                                    :alt="post.title"
                                    class="size-9 rounded object-cover shrink-0"
                                >
                                <div v-else class="size-9 rounded bg-muted/10 shrink-0 flex items-center justify-center">
                                    <UIcon name="i-lucide-image" class="size-3.5 text-muted/50" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-highlighted truncate">{{ post.title }}</p>
                                    <p class="text-[11px] text-muted truncate">
                                        <span v-if="post.author">{{ post.author.name }}</span>
                                        <template v-if="post.author && post.categories?.length"> &middot; </template>
                                        <span v-if="post.categories?.length">{{ post.categories.map(c => c.name).join(', ') }}</span>
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-muted hover:text-error p-1"
                                    @click="removePost(post.id)"
                                >
                                    <UIcon name="i-lucide-x" class="size-3.5" />
                                </button>
                            </div>
                        </div>

                        <div v-else class="mt-4 text-center py-6">
                            <p class="text-xs text-muted">
                                {{ hasAutoRules ? 'No pinned posts. Auto-populate rules will populate this collection.' : 'Search above to add posts, or set auto-populate rules.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-4 space-y-6">
                <div class="rounded-xl border border-default overflow-hidden">
                    <div class="px-5 py-3.5 bg-elevated/50 border-b border-default">
                        <h3 class="text-sm font-semibold text-highlighted">Settings</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <UFormField label="Slug" name="slug" :error="form.errors.slug">
                            <UInput
                                v-model="form.slug"
                                placeholder="editors-picks"
                                class="w-full"
                                :disabled="form.processing"
                            />
                        </UFormField>

                        <UFormField label="Sort Order" name="sort_order">
                            <USelectMenu
                                v-model="form.sort_order"
                                :items="sortOrderOptions"
                                class="w-full"
                                value-key="value"
                                :ui="{ content: 'z-50' }"
                            />
                        </UFormField>

                        <UFormField label="Display Order" name="order" :error="form.errors.order" help="Position among other collections">
                            <UInput
                                v-model.number="form.order"
                                type="number"
                                class="w-full"
                                :disabled="form.processing"
                                :min="0"
                            />
                        </UFormField>
                    </div>
                </div>

                <!-- Summary -->
                <div v-if="isEditing" class="rounded-xl border border-default overflow-hidden">
                    <div class="px-5 py-3.5 bg-elevated/50 border-b border-default">
                        <h3 class="text-sm font-semibold text-highlighted">Summary</h3>
                    </div>
                    <div class="p-5 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted">Pinned posts</span>
                            <span class="text-highlighted font-medium">{{ selectedPosts.length }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Auto-populate rules</span>
                            <span class="text-highlighted font-medium">{{ form.categories.length + form.tags.length }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Sort</span>
                            <span class="text-highlighted font-medium">{{ sortOrderOptions.find(o => o.value === form.sort_order)?.label.split(' (')[0] || 'Manual' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </UForm>
</template>
