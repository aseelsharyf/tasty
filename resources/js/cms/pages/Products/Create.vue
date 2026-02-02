<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import MediaPickerModal from '../../components/MediaPickerModal.vue';
import DhivehiInput from '../../components/DhivehiInput.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { Language } from '../../types';

const toast = useToast();

interface MediaItem {
    id: number;
    uuid?: string;
    url: string | null;
    thumbnail_url?: string | null;
    title?: string | null;
    is_image?: boolean;
}

interface ProductCategory {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
    slug?: string;
}

interface ProductStore {
    id: number;
    name: string;
}

const props = defineProps<{
    categories: ProductCategory[];
    tags: Tag[];
    stores: ProductStore[];
    languages: Language[];
}>();

const { cmsPath } = useCmsPath();

const activeTab = ref(props.languages[0]?.code || 'en');
const localTags = ref<Tag[]>([...props.tags]);

function initTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = '';
    });
    return translations;
}

const form = useForm({
    title: initTranslations(),
    description: initTranslations(),
    short_description: initTranslations(),
    slug: '',
    brand: '',
    product_category_id: null as number | null,
    featured_tag_id: null as number | null,
    featured_media_id: null as number | null,
    price: null as number | null,
    currency: 'USD',
    compare_at_price: null as number | null,
    availability: 'in_stock',
    affiliate_url: '',
    product_store_id: null as number | null,
    is_active: true,
    is_featured: false,
    sku: '',
    tag_ids: [] as number[],
    image_ids: [] as number[],
});

// Media picker
const mediaPickerOpen = ref(false);
const mediaPickerMultiple = ref(false);
const mediaPickerPurpose = ref<'featured' | 'gallery'>('featured');
const selectedFeaturedMedia = ref<MediaItem | null>(null);
const selectedImages = ref<MediaItem[]>([]);

// Options
const currencyOptions = [
    { value: 'USD', label: 'USD ($)' },
    { value: 'EUR', label: 'EUR (€)' },
    { value: 'GBP', label: 'GBP (£)' },
    { value: 'MVR', label: 'MVR' },
];

const availabilityOptions = [
    { value: 'in_stock', label: 'In Stock' },
    { value: 'out_of_stock', label: 'Out of Stock' },
    { value: 'pre_order', label: 'Pre-Order' },
    { value: 'discontinued', label: 'Discontinued' },
];

const categoryOptions = computed(() =>
    props.categories.map(c => ({ value: c.id, label: c.name }))
);

const tagOptions = computed(() =>
    localTags.value.map(t => ({ value: t.id, label: t.name }))
);

const storeOptions = computed(() =>
    props.stores.map(s => ({ value: s.id, label: s.name }))
);

const isCurrentRtl = computed(() => {
    const lang = props.languages.find(l => l.code === activeTab.value);
    return lang?.direction === 'rtl';
});

const isDhivehi = computed(() => activeTab.value === 'dv');

// Slug generation
const slugManuallyEdited = ref(false);

function slugify(text: string): string {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

watch(() => form.title[props.languages[0]?.code || 'en'], (newTitle) => {
    if (!slugManuallyEdited.value && newTitle) {
        form.slug = slugify(newTitle);
    }
});

function onSlugInput() {
    slugManuallyEdited.value = true;
}

function regenerateSlug() {
    slugManuallyEdited.value = false;
    form.slug = slugify(form.title[props.languages[0]?.code || 'en'] || '');
}

// Tag creation
async function onCreateTag(name: string) {
    try {
        const response = await axios.post(cmsPath('/tags'), { name: { en: name } });
        const newTag = response.data;
        localTags.value.push({ id: newTag.id, name: newTag.name, slug: newTag.slug });
        form.tag_ids = [...form.tag_ids, newTag.id];
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

// Custom tag input state (matching Posts Edit behavior)
const tagInputRef = ref<HTMLInputElement | null>(null);
const tagSearchQuery = ref('');
const showTagSuggestions = ref(false);
const tagSuggestions = ref<{ id: number; name: string; slug: string }[]>([]);
const isSearchingTags = ref(false);
let tagSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

// Search tags via API with debounce
async function searchTags(query: string) {
    if (!query.trim()) {
        tagSuggestions.value = [];
        return;
    }

    isSearchingTags.value = true;
    try {
        const response = await axios.get(cmsPath('/tags/search'), {
            params: {
                q: query,
                exclude: form.tag_ids,
            },
        });
        tagSuggestions.value = response.data;
    } catch (error) {
        console.error('Failed to search tags:', error);
        tagSuggestions.value = [];
    } finally {
        isSearchingTags.value = false;
    }
}

// Debounced tag search
function debouncedTagSearch(query: string) {
    if (tagSearchDebounceTimer) {
        clearTimeout(tagSearchDebounceTimer);
    }
    tagSearchDebounceTimer = setTimeout(() => {
        searchTags(query);
    }, 300);
}

// Check if the search query exactly matches an existing tag in suggestions
const tagExistsInSuggestions = computed(() => {
    const query = tagSearchQuery.value.trim().toLowerCase();
    return tagSuggestions.value.some((tag) => tag.name.toLowerCase() === query);
});

// Get tag label by ID
function getTagLabel(tagId: number): string | undefined {
    const tag = localTags.value.find((t) => t.id === tagId);
    return tag?.name;
}

// Add a tag to selection
function addTag(tagId: number) {
    if (!form.tag_ids.includes(tagId)) {
        form.tag_ids = [...form.tag_ids, tagId];
    }
}

// Remove a tag from selection
function removeTag(tagId: number) {
    form.tag_ids = form.tag_ids.filter((id) => id !== tagId);
}

// Handle tag input
function onTagInput() {
    showTagSuggestions.value = true;
    debouncedTagSearch(tagSearchQuery.value);
}

// Handle enter key in tag input
function onTagEnter() {
    if (tagSuggestions.value.length > 0) {
        // Select first suggestion
        selectTagSuggestion(tagSuggestions.value[0].id, tagSuggestions.value[0].name);
    } else if (tagSearchQuery.value.trim() && !tagExistsInSuggestions.value) {
        // Create new tag
        createAndAddTag();
    }
}

// Handle backspace in tag input
function onTagBackspace() {
    if (!tagSearchQuery.value && form.tag_ids.length > 0) {
        // Remove last tag
        form.tag_ids = form.tag_ids.slice(0, -1);
    }
}

// Select a tag from suggestions
function selectTagSuggestion(tagId: number, tagName: string) {
    addTag(tagId);
    // Add to localTags so getTagLabel works
    if (!localTags.value.find((t) => t.id === tagId)) {
        localTags.value.push({ id: tagId, name: tagName, slug: '' });
    }
    // Clear input
    tagSearchQuery.value = '';
    tagSuggestions.value = [];
    showTagSuggestions.value = false;
}

// Create and add a new tag
async function createAndAddTag() {
    const name = tagSearchQuery.value.trim();
    if (!name) return;

    try {
        const langCode = props.languages[0]?.code || 'en';
        const response = await axios.post(cmsPath('/tags'), {
            name: { [langCode]: name },
        });
        const newTag = response.data;
        localTags.value.push({ id: newTag.id, name: newTag.name, slug: newTag.slug });
        form.tag_ids = [...form.tag_ids, newTag.id];
        toast.add({
            title: 'Tag Created',
            description: `Tag "${name}" has been created.`,
            color: 'success',
        });
        tagSearchQuery.value = '';
        tagSuggestions.value = [];
        showTagSuggestions.value = false;
    } catch (error: any) {
        toast.add({
            title: 'Error',
            description: error.response?.data?.message || 'Failed to create tag',
            color: 'error',
        });
    }
}

// Handle blur on tag input
function onTagInputBlur() {
    // Delay hiding suggestions to allow click events to fire
    setTimeout(() => {
        showTagSuggestions.value = false;
    }, 200);
}

async function onCreateFeaturedTag(name: string) {
    try {
        const response = await axios.post(cmsPath('/tags'), { name: { en: name } });
        const newTag = response.data;
        localTags.value.push({ id: newTag.id, name: newTag.name, slug: newTag.slug });
        form.featured_tag_id = newTag.id;
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

// Media handling
function openFeaturedPicker() {
    mediaPickerMultiple.value = false;
    mediaPickerPurpose.value = 'featured';
    mediaPickerOpen.value = true;
}

function openGalleryPicker() {
    mediaPickerMultiple.value = true;
    mediaPickerPurpose.value = 'gallery';
    mediaPickerOpen.value = true;
}

function handleMediaSelect(items: MediaItem[]) {
    if (mediaPickerPurpose.value === 'featured') {
        if (items.length > 0) {
            selectedFeaturedMedia.value = items[0];
            form.featured_media_id = items[0].id;
        }
    } else {
        selectedImages.value = [...selectedImages.value, ...items];
        form.image_ids = selectedImages.value.map(m => m.id);
    }
}

function removeFeaturedMedia() {
    selectedFeaturedMedia.value = null;
    form.featured_media_id = null;
}

function removeImage(index: number) {
    selectedImages.value.splice(index, 1);
    form.image_ids = selectedImages.value.map(m => m.id);
}

function hasTranslation(langCode: string): boolean {
    return !!(form.title[langCode]?.trim());
}

function submit() {
    const titleData = Object.fromEntries(
        Object.entries(form.title).filter(([_, v]) => v?.trim())
    );
    const descriptionData = Object.fromEntries(
        Object.entries(form.description).filter(([_, v]) => v?.trim())
    );
    const shortDescriptionData = Object.fromEntries(
        Object.entries(form.short_description).filter(([_, v]) => v?.trim())
    );

    form.transform(() => ({
        title: titleData,
        description: descriptionData,
        short_description: shortDescriptionData,
        slug: form.slug,
        brand: form.brand || null,
        product_category_id: form.product_category_id,
        featured_tag_id: form.featured_tag_id,
        featured_media_id: form.featured_media_id,
        price: form.price,
        currency: form.currency,
        compare_at_price: form.compare_at_price,
        availability: form.availability,
        affiliate_url: form.affiliate_url,
        product_store_id: form.product_store_id,
        is_active: form.is_active,
        is_featured: form.is_featured,
        sku: form.sku || null,
        tag_ids: form.tag_ids,
        image_ids: form.image_ids,
    })).post(cmsPath('/products'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                title: 'Product created',
                description: 'The product has been created successfully.',
                icon: 'i-lucide-check-circle',
                color: 'success',
            });
        },
    });
}

function goBack() {
    router.visit(cmsPath('/products'));
}
</script>

<template>
    <Head title="Create Product" />

    <DashboardLayout>
        <UDashboardPanel id="create-product" :ui="{ body: 'p-0 gap-0' }">
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
                            <span class="text-sm text-muted">New Product</span>
                        </div>
                    </template>

                    <template #right>
                        <div class="flex items-center gap-2">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                :disabled="form.processing"
                                @click="goBack"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                size="sm"
                                :loading="form.processing"
                                @click="submit"
                            >
                                Create Product
                            </UButton>
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="w-full max-w-2xl mx-auto px-6 py-8">
                    <!-- Featured Image at Top -->
                    <div class="mb-6">
                        <label class="text-sm font-medium text-muted mb-3 block">Featured Image</label>
                        <div v-if="selectedFeaturedMedia" class="relative rounded-xl overflow-hidden bg-muted/10 border border-default">
                            <img
                                :src="selectedFeaturedMedia.thumbnail_url || selectedFeaturedMedia.url || ''"
                                :alt="selectedFeaturedMedia.title || 'Product image'"
                                class="w-full h-64 object-contain bg-white"
                            />
                            <div class="absolute top-3 right-3 flex gap-2">
                                <UButton
                                    color="neutral"
                                    variant="solid"
                                    icon="i-lucide-replace"
                                    size="sm"
                                    @click="openFeaturedPicker"
                                >
                                    Change
                                </UButton>
                                <UButton
                                    color="error"
                                    variant="solid"
                                    icon="i-lucide-x"
                                    size="sm"
                                    @click="removeFeaturedMedia"
                                />
                            </div>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="w-full border-2 border-dashed border-default rounded-xl px-6 py-16 text-center hover:border-primary hover:bg-primary/5 transition-colors"
                            @click="openFeaturedPicker"
                        >
                            <UIcon name="i-lucide-image-plus" class="size-10 text-muted mx-auto mb-3" />
                            <p class="text-sm font-medium text-highlighted mb-1">Add featured image</p>
                            <p class="text-xs text-muted">Click to select from media library</p>
                        </button>
                    </div>

                    <!-- Product Images Gallery -->
                    <div class="mb-8">
                        <label class="text-sm font-medium text-muted mb-3 block">Additional Images</label>
                        <div class="flex flex-wrap gap-3">
                            <!-- Existing images -->
                            <div
                                v-for="(image, index) in selectedImages"
                                :key="image.id"
                                class="relative rounded-lg overflow-hidden bg-white border border-default w-24 h-24"
                            >
                                <img
                                    :src="image.thumbnail_url || image.url || ''"
                                    :alt="image.title || `Image ${index + 1}`"
                                    class="w-full h-full object-contain"
                                />
                                <UButton
                                    color="error"
                                    variant="solid"
                                    icon="i-lucide-x"
                                    size="xs"
                                    class="absolute top-1 right-1"
                                    @click="removeImage(index)"
                                />
                            </div>
                            <!-- Add more button (inline with images) -->
                            <button
                                type="button"
                                class="w-24 h-24 border-2 border-dashed border-default rounded-lg flex flex-col items-center justify-center hover:border-primary hover:bg-primary/5 transition-colors"
                                :disabled="form.processing"
                                @click="openGalleryPicker"
                            >
                                <UIcon name="i-lucide-plus" class="size-6 text-muted" />
                            </button>
                        </div>
                    </div>

                    <!-- Language Tabs -->
                    <div v-if="languages.length > 1" class="border-b border-default mb-6">
                        <nav class="flex gap-1 -mb-px">
                            <button
                                v-for="lang in languages"
                                :key="lang.code"
                                type="button"
                                :class="[
                                    'px-4 py-2.5 text-sm font-medium border-b-2 transition-colors',
                                    activeTab === lang.code
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                                ]"
                                @click="activeTab = lang.code"
                            >
                                <span class="flex items-center gap-2">
                                    {{ lang.native_name }}
                                    <span
                                        v-if="hasTranslation(lang.code)"
                                        class="size-2 rounded-full bg-success"
                                        title="Translation available"
                                    />
                                    <span
                                        v-else
                                        class="size-2 rounded-full bg-muted/30"
                                        title="No translation"
                                    />
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- Form Content -->
                    <div class="space-y-6">
                        <!-- Product Name -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">
                                Product Name
                                <span v-if="languages.length > 1" class="text-muted font-normal">({{ languages.find(l => l.code === activeTab)?.native_name }})</span>
                                <span class="text-error">*</span>
                            </label>
                            <DhivehiInput
                                v-if="isDhivehi"
                                v-model="form.title[activeTab]"
                                placeholder="ޕްރޮޑަކްޓް ނަން ލިޔުއްވާ"
                                :disabled="form.processing"
                                :default-enabled="true"
                                :show-toggle="false"
                                class="w-full"
                            />
                            <UInput
                                v-else
                                v-model="form.title[activeTab]"
                                placeholder="e.g., Cast Iron Skillet"
                                size="lg"
                                class="w-full"
                                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors[`title.${activeTab}`] || form.errors.title" class="text-error text-xs mt-1">
                                {{ form.errors[`title.${activeTab}`] || form.errors.title }}
                            </p>
                        </div>

                        <!-- Short Description -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">
                                Short Description
                                <span v-if="languages.length > 1" class="text-muted font-normal">({{ languages.find(l => l.code === activeTab)?.native_name }})</span>
                            </label>
                            <UInput
                                v-model="form.short_description[activeTab]"
                                placeholder="Brief summary for product cards..."
                                class="w-full"
                                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                                :disabled="form.processing"
                            />
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">
                                Description
                                <span v-if="languages.length > 1" class="text-muted font-normal">({{ languages.find(l => l.code === activeTab)?.native_name }})</span>
                            </label>
                            <UTextarea
                                v-model="form.description[activeTab]"
                                placeholder="Full product description..."
                                class="w-full"
                                :rows="4"
                                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                                :disabled="form.processing"
                            />
                        </div>

                        <!-- Non-translatable fields only on primary language tab -->
                        <template v-if="activeTab === languages[0]?.code">
                            <div class="border-t border-default pt-6">
                                <h3 class="text-sm font-semibold text-highlighted mb-4">Product Details</h3>

                                <div class="space-y-4">
                                    <!-- Slug -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">URL Slug</label>
                                        <div class="flex gap-2">
                                            <UInput
                                                v-model="form.slug"
                                                placeholder="product-slug"
                                                class="flex-1"
                                                :disabled="form.processing"
                                                @input="onSlugInput"
                                            />
                                            <UButton
                                                color="neutral"
                                                variant="ghost"
                                                icon="i-lucide-refresh-cw"
                                                @click="regenerateSlug"
                                            />
                                        </div>
                                    </div>

                                    <!-- Brand -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Brand</label>
                                        <UInput
                                            v-model="form.brand"
                                            placeholder="e.g., Lodge, KitchenAid"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Category <span class="text-error">*</span></label>
                                        <USelectMenu
                                            v-model="form.product_category_id"
                                            :items="categoryOptions"
                                            placeholder="Select category..."
                                            value-key="value"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                        <p v-if="form.errors.product_category_id" class="text-error text-xs mt-1">{{ form.errors.product_category_id }}</p>
                                    </div>

                                    <!-- Store -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Store</label>
                                        <USelectMenu
                                            v-model="form.product_store_id"
                                            :items="storeOptions"
                                            placeholder="Select store..."
                                            value-key="value"
                                            label-key="label"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-default pt-6">
                                <h3 class="text-sm font-semibold text-highlighted mb-4">Pricing</h3>

                                <div class="space-y-4">
                                    <!-- Price -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Price</label>
                                        <UInput
                                            v-model.number="form.price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                    </div>

                                    <!-- Currency -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Currency</label>
                                        <USelectMenu
                                            v-model="form.currency"
                                            :items="currencyOptions"
                                            value-key="value"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                    </div>

                                    <!-- Compare at Price -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Compare at Price</label>
                                        <UInput
                                            v-model.number="form.compare_at_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="Original price"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-default pt-6">
                                <h3 class="text-sm font-semibold text-highlighted mb-4">Affiliate & Availability</h3>

                                <div class="space-y-4">
                                    <!-- Affiliate URL -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Affiliate URL <span class="text-error">*</span></label>
                                        <UInput
                                            v-model="form.affiliate_url"
                                            type="url"
                                            placeholder="https://partner.com/product/123"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                        <p v-if="form.errors.affiliate_url" class="text-error text-xs mt-1">{{ form.errors.affiliate_url }}</p>
                                    </div>

                                    <!-- Availability -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Availability</label>
                                        <USelectMenu
                                            v-model="form.availability"
                                            :items="availabilityOptions"
                                            value-key="value"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                    </div>

                                    <!-- SKU -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">SKU</label>
                                        <UInput
                                            v-model="form.sku"
                                            placeholder="PRD-001"
                                            class="w-full"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-default pt-6">
                                <h3 class="text-sm font-semibold text-highlighted mb-4">Tags & Organization</h3>

                                <div class="space-y-4">
                                    <!-- Featured Tag -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Featured Tag</label>
                                        <USelectMenu
                                            v-model="form.featured_tag_id"
                                            :items="tagOptions"
                                            placeholder="Select or create..."
                                            value-key="value"
                                            searchable
                                            create-item
                                            size="sm"
                                            class="w-full"
                                            :disabled="form.processing"
                                            @create="onCreateFeaturedTag"
                                        />
                                        <p class="text-xs text-muted mt-1">Displayed as a badge on product cards</p>
                                    </div>

                                    <!-- Tags (Custom input with inline pills - matching Posts Edit) -->
                                    <div>
                                        <label class="text-sm font-medium mb-2 block">Tags</label>

                                        <!-- Custom Tag Input -->
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-1.5 px-2.5 py-1.5 border border-[var(--ui-border-accented)] rounded-[var(--ui-radius)] bg-[var(--ui-bg)] focus-within:ring-2 focus-within:ring-primary/50 focus-within:border-primary transition-all min-h-[38px]">
                                                <UIcon name="i-lucide-tag" class="size-3.5 text-muted shrink-0" />
                                                <div class="flex-1 flex flex-wrap items-center gap-1.5">
                                                    <!-- Selected Tags as inline pills -->
                                                    <span
                                                        v-for="tagId in form.tag_ids"
                                                        :key="tagId"
                                                        class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-primary/10 text-primary rounded text-xs font-medium"
                                                    >
                                                        {{ getTagLabel(tagId) }}
                                                        <button
                                                            type="button"
                                                            class="hover:bg-primary/20 rounded p-0.5 transition-colors"
                                                            @click="removeTag(tagId)"
                                                        >
                                                            <UIcon name="i-lucide-x" class="size-2.5" />
                                                        </button>
                                                    </span>
                                                    <!-- Text input -->
                                                    <input
                                                        ref="tagInputRef"
                                                        v-model="tagSearchQuery"
                                                        type="text"
                                                        class="flex-1 min-w-[100px] bg-transparent border-none outline-none text-sm placeholder:text-muted"
                                                        placeholder="Type to add tag..."
                                                        :disabled="form.processing"
                                                        @input="onTagInput"
                                                        @keydown.enter.prevent="onTagEnter"
                                                        @keydown.backspace="onTagBackspace"
                                                        @focus="showTagSuggestions = true"
                                                        @blur="onTagInputBlur"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Suggestions dropdown -->
                                            <div
                                                v-if="showTagSuggestions && (tagSuggestions.length > 0 || isSearchingTags || (tagSearchQuery.trim() && !tagExistsInSuggestions))"
                                                class="absolute z-50 w-full mt-1 bg-default border border-muted rounded-lg shadow-lg max-h-48 overflow-y-auto"
                                            >
                                                <!-- Loading state -->
                                                <div v-if="isSearchingTags" class="px-3 py-2 text-sm text-muted flex items-center gap-2">
                                                    <UIcon name="i-lucide-loader-2" class="size-3.5 animate-spin" />
                                                    <span>Searching...</span>
                                                </div>

                                                <!-- Existing tags matching search -->
                                                <button
                                                    v-for="tag in tagSuggestions"
                                                    :key="tag.id"
                                                    type="button"
                                                    class="w-full px-3 py-2 text-left text-sm hover:bg-muted/50 flex items-center gap-2 transition-colors"
                                                    @mousedown.prevent="selectTagSuggestion(tag.id, tag.name)"
                                                >
                                                    <UIcon name="i-lucide-tag" class="size-3.5 text-muted" />
                                                    <span>{{ tag.name }}</span>
                                                </button>

                                                <!-- Create new tag option -->
                                                <button
                                                    v-if="tagSearchQuery.trim() && !tagExistsInSuggestions && !isSearchingTags"
                                                    type="button"
                                                    class="w-full px-3 py-2 text-left text-sm hover:bg-muted/50 flex items-center gap-2 text-primary border-t border-muted transition-colors"
                                                    @mousedown.prevent="createAndAddTag"
                                                >
                                                    <UIcon name="i-lucide-plus" class="size-3.5" />
                                                    <span>Create "{{ tagSearchQuery.trim() }}"</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Toggles -->
                            <div class="border-t border-default pt-6">
                                <h3 class="text-sm font-semibold text-highlighted mb-4">Status</h3>

                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-3 rounded-lg bg-elevated/50 border border-default">
                                        <div>
                                            <p class="text-sm font-medium">Active</p>
                                            <p class="text-xs text-muted">Product is visible on the website</p>
                                        </div>
                                        <USwitch v-model="form.is_active" :disabled="form.processing" />
                                    </div>

                                    <div class="flex items-center justify-between p-3 rounded-lg bg-elevated/50 border border-default">
                                        <div>
                                            <p class="text-sm font-medium">Featured</p>
                                            <p class="text-xs text-muted">Show in featured products section</p>
                                        </div>
                                        <USwitch v-model="form.is_featured" :disabled="form.processing" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Actions -->
                    <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-default">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            :disabled="form.processing"
                            @click="goBack"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            :loading="form.processing"
                            @click="submit"
                        >
                            Create Product
                        </UButton>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Media Picker Modal -->
        <MediaPickerModal
            v-model:open="mediaPickerOpen"
            type="images"
            :multiple="mediaPickerMultiple"
            default-category="products"
            @select="handleMediaSelect"
        />
    </DashboardLayout>
</template>
