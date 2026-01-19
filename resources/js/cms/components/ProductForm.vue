<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import DhivehiInput from './DhivehiInput.vue';
import MediaPickerModal from './MediaPickerModal.vue';
import type { Language } from '../types';

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
}

interface ProductStore {
    id: number;
    name: string;
}

interface ProductWithTranslations {
    id?: number;
    uuid?: string;
    title?: string;
    title_translations?: Record<string, string>;
    description?: string;
    description_translations?: Record<string, string>;
    short_description?: string;
    short_description_translations?: Record<string, string>;
    slug?: string;
    brand?: string | null;
    product_category_id?: number | null;
    featured_tag_id?: number | null;
    featured_media_id?: number | null;
    featured_media?: MediaItem | null;
    price?: number | null;
    currency?: string;
    compare_at_price?: number | null;
    availability?: string;
    affiliate_url?: string;
    product_store_id?: number | null;
    is_active?: boolean;
    is_featured?: boolean;
    sku?: string | null;
    tag_ids?: number[];
    image_ids?: number[];
    images?: MediaItem[];
}

const props = withDefaults(defineProps<{
    product?: ProductWithTranslations;
    languages: Language[];
    categories: ProductCategory[];
    tags?: Tag[];
    stores?: ProductStore[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
    tags: () => [],
    stores: () => [],
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const isEditing = computed(() => props.mode === 'edit' && props.product?.uuid);
const activeTab = ref(props.languages[0]?.code || 'en');

function initTitleTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.product?.title_translations?.[lang.code] || '';
    });
    return translations;
}

function initDescriptionTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.product?.description_translations?.[lang.code] || '';
    });
    return translations;
}

function initShortDescriptionTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = props.product?.short_description_translations?.[lang.code] || '';
    });
    return translations;
}

const form = useForm({
    title: initTitleTranslations(),
    description: initDescriptionTranslations(),
    short_description: initShortDescriptionTranslations(),
    slug: props.product?.slug || '',
    brand: props.product?.brand || '',
    product_category_id: props.product?.product_category_id || null as number | null,
    featured_tag_id: props.product?.featured_tag_id || null as number | null,
    featured_media_id: props.product?.featured_media_id || null as number | null,
    price: props.product?.price || null as number | null,
    currency: props.product?.currency || 'USD',
    compare_at_price: props.product?.compare_at_price || null as number | null,
    availability: props.product?.availability || 'in_stock',
    affiliate_url: props.product?.affiliate_url || '',
    product_store_id: props.product?.product_store_id || null as number | null,
    is_active: props.product?.is_active ?? true,
    is_featured: props.product?.is_featured ?? false,
    sku: props.product?.sku || '',
    tag_ids: props.product?.tag_ids || [] as number[],
    image_ids: props.product?.image_ids || [] as number[],
});

const selectedMedia = ref<MediaItem | null>(props.product?.featured_media || null);
const mediaPickerOpen = ref(false);
const selectedImages = ref<MediaItem[]>(props.product?.images || []);
const imagesPickerOpen = ref(false);

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
    props.tags.map(t => ({ value: t.id, label: t.name }))
);

const storeOptions = computed(() =>
    props.stores.map(s => ({ value: s.id, label: s.name }))
);

watch(() => form.title[props.languages[0]?.code || 'en'], (newTitle) => {
    if (props.mode === 'create' && newTitle) {
        const currentSlug = form.slug;
        const previousTitle = form.title[props.languages[0]?.code || 'en']?.slice(0, -1) || '';
        if (!currentSlug || currentSlug === slugify(previousTitle)) {
            form.slug = slugify(newTitle);
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

function onMediaSelect(media: MediaItem[]) {
    if (media.length > 0) {
        selectedMedia.value = media[0];
        form.featured_media_id = media[0].id;
    }
}

function removeMedia() {
    selectedMedia.value = null;
    form.featured_media_id = null;
}

function onImagesSelect(media: MediaItem[]) {
    selectedImages.value = [...selectedImages.value, ...media];
    form.image_ids = selectedImages.value.map(m => m.id);
}

function removeImage(index: number) {
    selectedImages.value.splice(index, 1);
    form.image_ids = selectedImages.value.map(m => m.id);
}

function onSubmit() {
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
    }));

    if (isEditing.value && props.product?.uuid) {
        form.put(`/cms/products/${props.product.uuid}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    title: 'Product updated',
                    description: 'The product has been updated successfully.',
                    icon: 'i-lucide-check-circle',
                    color: 'success',
                });
                emit('success');
            },
        });
    } else {
        form.post('/cms/products', {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    title: 'Product created',
                    description: 'The product has been created successfully.',
                    icon: 'i-lucide-check-circle',
                    color: 'success',
                });
                reset();
                emit('success');
            },
        });
    }
}

function onCancel() {
    reset();
    emit('cancel');
}

function reset() {
    props.languages.forEach(lang => {
        form.title[lang.code] = '';
        form.description[lang.code] = '';
        form.short_description[lang.code] = '';
    });
    form.slug = '';
    form.brand = '';
    form.product_category_id = null;
    form.featured_tag_id = null;
    form.featured_media_id = null;
    form.price = null;
    form.currency = 'USD';
    form.compare_at_price = null;
    form.availability = 'in_stock';
    form.affiliate_url = '';
    form.product_store_id = null;
    form.is_active = true;
    form.is_featured = false;
    form.sku = '';
    form.tag_ids = [];
    form.image_ids = [];
    selectedMedia.value = null;
    selectedImages.value = [];
    form.clearErrors();
    activeTab.value = props.languages[0]?.code || 'en';
}

watch(() => props.product, (newProduct) => {
    if (newProduct) {
        props.languages.forEach(lang => {
            form.title[lang.code] = newProduct.title_translations?.[lang.code] || '';
            form.description[lang.code] = newProduct.description_translations?.[lang.code] || '';
            form.short_description[lang.code] = newProduct.short_description_translations?.[lang.code] || '';
        });
        form.slug = newProduct.slug || '';
        form.brand = newProduct.brand || '';
        form.product_category_id = newProduct.product_category_id || null;
        form.featured_tag_id = newProduct.featured_tag_id || null;
        form.featured_media_id = newProduct.featured_media_id || null;
        form.price = newProduct.price || null;
        form.currency = newProduct.currency || 'USD';
        form.compare_at_price = newProduct.compare_at_price || null;
        form.availability = newProduct.availability || 'in_stock';
        form.affiliate_url = newProduct.affiliate_url || '';
        form.product_store_id = newProduct.product_store_id || null;
        form.is_active = newProduct.is_active ?? true;
        form.is_featured = newProduct.is_featured ?? false;
        form.sku = newProduct.sku || '';
        form.tag_ids = newProduct.tag_ids || [];
        form.image_ids = newProduct.image_ids || [];
        selectedMedia.value = newProduct.featured_media || null;
        selectedImages.value = newProduct.images || [];
    }
}, { immediate: true, deep: true });

function hasTranslation(langCode: string): boolean {
    return !!(form.title[langCode]?.trim());
}

const isCurrentRtl = computed(() => {
    const lang = props.languages.find(l => l.code === activeTab.value);
    return lang?.direction === 'rtl';
});

const isDhivehi = computed(() => activeTab.value === 'dv');

defineExpose({ reset, form });
</script>

<template>
    <UForm
        :state="form"
        class="space-y-4"
        @submit="onSubmit"
    >
        <!-- Language Tabs -->
        <div v-if="languages.length > 1" class="border-b border-default">
            <nav class="flex gap-1 -mb-px">
                <button
                    v-for="lang in languages"
                    :key="lang.code"
                    type="button"
                    :class="[
                        'px-3 py-2 text-sm font-medium border-b-2 transition-colors',
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

        <!-- Product Name Input -->
        <UFormField
            :label="languages.length > 1 ? `Product Name (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Product Name'"
            name="title"
            :error="form.errors[`title.${activeTab}`] || form.errors.title"
            required
        >
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
                :placeholder="languages.length > 1 ? `Enter product name in ${languages.find(l => l.code === activeTab)?.name}` : 'e.g., Organic Olive Oil'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Product Description Input -->
        <UFormField
            :label="languages.length > 1 ? `Product Description (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Product Description'"
            name="description"
            :error="form.errors[`description.${activeTab}`] || form.errors.description"
        >
            <UTextarea
                v-model="form.description[activeTab]"
                :placeholder="languages.length > 1 ? `Enter product description in ${languages.find(l => l.code === activeTab)?.name}` : 'Product description...'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
                :rows="3"
            />
        </UFormField>

        <!-- Short Description Input -->
        <UFormField
            :label="languages.length > 1 ? `Short Description (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Short Description'"
            name="short_description"
            :error="form.errors[`short_description.${activeTab}`] || form.errors.short_description"
            help="A brief summary displayed in product cards"
        >
            <UInput
                v-model="form.short_description[activeTab]"
                :placeholder="languages.length > 1 ? `Enter short description in ${languages.find(l => l.code === activeTab)?.name}` : 'Brief product summary...'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Non-translatable fields only on primary language tab -->
        <template v-if="activeTab === languages[0]?.code">
            <!-- Slug -->
            <UFormField
                label="Slug"
                name="slug"
                :error="form.errors.slug"
                :help="mode === 'create' ? 'URL-friendly version (auto-generated)' : 'URL-friendly version'"
            >
                <UInput
                    v-model="form.slug"
                    placeholder="organic-olive-oil"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Brand -->
            <UFormField
                label="Brand"
                name="brand"
                :error="form.errors.brand"
            >
                <UInput
                    v-model="form.brand"
                    placeholder="e.g., Nestlé"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Category -->
            <UFormField
                label="Category"
                name="product_category_id"
                :error="form.errors.product_category_id"
                required
            >
                <USelectMenu
                    v-model="form.product_category_id"
                    :items="categoryOptions"
                    placeholder="Select category..."
                    value-key="value"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Featured Tag -->
            <UFormField
                label="Featured Tag"
                name="featured_tag_id"
                :error="form.errors.featured_tag_id"
                help="Displays as a secondary badge on product cards"
            >
                <USelectMenu
                    v-model="form.featured_tag_id"
                    :items="tagOptions"
                    placeholder="Select featured tag..."
                    value-key="value"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Featured Image -->
            <UFormField
                label="Featured Image"
                name="featured_media_id"
                :error="form.errors.featured_media_id"
            >
                <div v-if="selectedMedia" class="flex items-center gap-4 p-3 border border-default rounded-lg bg-elevated/50">
                    <img
                        :src="selectedMedia.thumbnail_url || selectedMedia.url || ''"
                        :alt="selectedMedia.title || 'Product image'"
                        class="size-16 object-contain rounded bg-white"
                    >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-highlighted truncate">
                            {{ selectedMedia.title || 'Product Image' }}
                        </p>
                    </div>
                    <UButton
                        color="error"
                        variant="ghost"
                        icon="i-lucide-x"
                        size="sm"
                        @click="removeMedia"
                    />
                </div>
                <UButton
                    v-else
                    color="neutral"
                    variant="outline"
                    icon="i-lucide-image"
                    :disabled="form.processing"
                    @click="mediaPickerOpen = true"
                >
                    Select Image
                </UButton>
            </UFormField>

            <!-- Price Row -->
            <div class="grid grid-cols-2 gap-4">
                <UFormField
                    label="Price"
                    name="price"
                    :error="form.errors.price"
                >
                    <UInput
                        v-model.number="form.price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="w-full"
                        :disabled="form.processing"
                    />
                </UFormField>

                <UFormField
                    label="Currency"
                    name="currency"
                    :error="form.errors.currency"
                >
                    <USelectMenu
                        v-model="form.currency"
                        :items="currencyOptions"
                        value-key="value"
                        class="w-full"
                        :disabled="form.processing"
                    />
                </UFormField>
            </div>

            <!-- Compare at Price -->
            <UFormField
                label="Compare at Price"
                name="compare_at_price"
                :error="form.errors.compare_at_price"
                help="Original price to show a discount"
            >
                <UInput
                    v-model.number="form.compare_at_price"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Availability -->
            <UFormField
                label="Availability"
                name="availability"
                :error="form.errors.availability"
            >
                <USelectMenu
                    v-model="form.availability"
                    :items="availabilityOptions"
                    value-key="value"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Affiliate URL -->
            <UFormField
                label="Affiliate URL"
                name="affiliate_url"
                :error="form.errors.affiliate_url"
                required
            >
                <UInput
                    v-model="form.affiliate_url"
                    type="url"
                    placeholder="https://partner.com/product/123"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Store -->
            <UFormField
                label="Store"
                name="product_store_id"
                :error="form.errors.product_store_id"
                help="Select the store where this product is available"
            >
                <USelectMenu
                    v-model="form.product_store_id"
                    :items="storeOptions"
                    placeholder="Select store..."
                    value-key="value"
                    label-key="label"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- SKU -->
            <UFormField
                label="SKU"
                name="sku"
                :error="form.errors.sku"
                help="Stock Keeping Unit (optional)"
            >
                <UInput
                    v-model="form.sku"
                    placeholder="PRD-001"
                    class="w-full"
                    :disabled="form.processing"
                />
            </UFormField>

            <!-- Product Images -->
            <UFormField
                label="Product Images"
                name="image_ids"
                :error="form.errors.image_ids"
                help="Additional product images (gallery)"
            >
                <div v-if="selectedImages.length > 0" class="space-y-2 mb-3">
                    <div
                        v-for="(image, index) in selectedImages"
                        :key="image.id"
                        class="flex items-center gap-4 p-2 border border-default rounded-lg bg-elevated/50"
                    >
                        <img
                            :src="image.thumbnail_url || image.url || ''"
                            :alt="image.title || 'Product image'"
                            class="size-12 object-contain rounded bg-white"
                        >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-highlighted truncate">
                                {{ image.title || `Image ${index + 1}` }}
                            </p>
                        </div>
                        <UButton
                            color="error"
                            variant="ghost"
                            icon="i-lucide-x"
                            size="xs"
                            @click="removeImage(index)"
                        />
                    </div>
                </div>
                <UButton
                    color="neutral"
                    variant="outline"
                    icon="i-lucide-images"
                    :disabled="form.processing"
                    @click="imagesPickerOpen = true"
                >
                    Add Images
                </UButton>
            </UFormField>

            <!-- Active Status -->
            <UFormField
                label="Status"
                name="is_active"
            >
                <USwitch
                    v-model="form.is_active"
                    :disabled="form.processing"
                >
                    <template #label>
                        {{ form.is_active ? 'Active' : 'Inactive' }}
                    </template>
                </USwitch>
            </UFormField>

            <!-- Featured -->
            <UFormField
                label="Featured Product"
                name="is_featured"
            >
                <USwitch
                    v-model="form.is_featured"
                    :disabled="form.processing"
                >
                    <template #label>
                        {{ form.is_featured ? 'Featured' : 'Not Featured' }}
                    </template>
                </USwitch>
            </UFormField>
        </template>

        <MediaPickerModal
            v-model:open="mediaPickerOpen"
            type="images"
            :multiple="false"
            default-category="products"
            @select="onMediaSelect"
        />

        <MediaPickerModal
            v-model:open="imagesPickerOpen"
            type="images"
            :multiple="true"
            default-category="products"
            @select="onImagesSelect"
        />

        <div class="flex justify-end gap-2 pt-6">
            <UButton
                color="neutral"
                variant="ghost"
                :disabled="form.processing"
                @click="onCancel"
            >
                Cancel
            </UButton>
            <UButton
                type="submit"
                :loading="form.processing"
            >
                {{ isEditing ? 'Save Changes' : 'Create Product' }}
            </UButton>
        </div>
    </UForm>
</template>
