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

interface ProductWithTranslations {
    id?: number;
    uuid?: string;
    title?: string;
    title_translations?: Record<string, string>;
    description?: string;
    description_translations?: Record<string, string>;
    slug?: string;
    product_category_id?: number | null;
    featured_tag_id?: number | null;
    featured_media_id?: number | null;
    featured_media?: MediaItem | null;
    price?: number | null;
    currency?: string;
    compare_at_price?: number | null;
    affiliate_url?: string;
    affiliate_source?: string | null;
    is_active?: boolean;
    sku?: string | null;
    tag_ids?: number[];
}

const props = withDefaults(defineProps<{
    product?: ProductWithTranslations;
    languages: Language[];
    categories: ProductCategory[];
    tags?: Tag[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
    tags: () => [],
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

const form = useForm({
    title: initTitleTranslations(),
    description: initDescriptionTranslations(),
    slug: props.product?.slug || '',
    product_category_id: props.product?.product_category_id || null as number | null,
    featured_tag_id: props.product?.featured_tag_id || null as number | null,
    featured_media_id: props.product?.featured_media_id || null as number | null,
    price: props.product?.price || null as number | null,
    currency: props.product?.currency || 'USD',
    compare_at_price: props.product?.compare_at_price || null as number | null,
    affiliate_url: props.product?.affiliate_url || '',
    affiliate_source: props.product?.affiliate_source || '',
    is_active: props.product?.is_active ?? true,
    sku: props.product?.sku || '',
    tag_ids: props.product?.tag_ids || [] as number[],
});

const selectedMedia = ref<MediaItem | null>(props.product?.featured_media || null);
const mediaPickerOpen = ref(false);

const currencyOptions = [
    { value: 'USD', label: 'USD ($)' },
    { value: 'EUR', label: 'EUR (\u20AC)' },
    { value: 'GBP', label: 'GBP (\u00A3)' },
    { value: 'MVR', label: 'MVR' },
];

const categoryOptions = computed(() =>
    props.categories.map(c => ({ value: c.id, label: c.name }))
);

const tagOptions = computed(() =>
    props.tags.map(t => ({ value: t.id, label: t.name }))
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

function onSubmit() {
    const titleData = Object.fromEntries(
        Object.entries(form.title).filter(([_, v]) => v?.trim())
    );
    const descriptionData = Object.fromEntries(
        Object.entries(form.description).filter(([_, v]) => v?.trim())
    );

    form.transform(() => ({
        title: titleData,
        description: descriptionData,
        slug: form.slug,
        product_category_id: form.product_category_id,
        featured_tag_id: form.featured_tag_id,
        featured_media_id: form.featured_media_id,
        price: form.price,
        currency: form.currency,
        compare_at_price: form.compare_at_price,
        affiliate_url: form.affiliate_url,
        affiliate_source: form.affiliate_source || null,
        is_active: form.is_active,
        sku: form.sku || null,
        tag_ids: form.tag_ids,
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
    });
    form.slug = '';
    form.product_category_id = null;
    form.featured_tag_id = null;
    form.featured_media_id = null;
    form.price = null;
    form.currency = 'USD';
    form.compare_at_price = null;
    form.affiliate_url = '';
    form.affiliate_source = '';
    form.is_active = true;
    form.sku = '';
    form.tag_ids = [];
    selectedMedia.value = null;
    form.clearErrors();
    activeTab.value = props.languages[0]?.code || 'en';
}

watch(() => props.product, (newProduct) => {
    if (newProduct) {
        props.languages.forEach(lang => {
            form.title[lang.code] = newProduct.title_translations?.[lang.code] || '';
            form.description[lang.code] = newProduct.description_translations?.[lang.code] || '';
        });
        form.slug = newProduct.slug || '';
        form.product_category_id = newProduct.product_category_id || null;
        form.featured_tag_id = newProduct.featured_tag_id || null;
        form.featured_media_id = newProduct.featured_media_id || null;
        form.price = newProduct.price || null;
        form.currency = newProduct.currency || 'USD';
        form.compare_at_price = newProduct.compare_at_price || null;
        form.affiliate_url = newProduct.affiliate_url || '';
        form.affiliate_source = newProduct.affiliate_source || '';
        form.is_active = newProduct.is_active ?? true;
        form.sku = newProduct.sku || '';
        form.tag_ids = newProduct.tag_ids || [];
        selectedMedia.value = newProduct.featured_media || null;
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

        <!-- Title Input -->
        <UFormField
            :label="languages.length > 1 ? `Title (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Title'"
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
                :placeholder="languages.length > 1 ? `Enter product title in ${languages.find(l => l.code === activeTab)?.name}` : 'e.g., Organic Olive Oil'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Description Input -->
        <UFormField
            :label="languages.length > 1 ? `Description (${languages.find(l => l.code === activeTab)?.native_name || activeTab})` : 'Description'"
            name="description"
            :error="form.errors[`description.${activeTab}`] || form.errors.description"
        >
            <UTextarea
                v-model="form.description[activeTab]"
                :placeholder="languages.length > 1 ? `Enter description in ${languages.find(l => l.code === activeTab)?.name}` : 'Product description...'"
                class="w-full"
                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                :disabled="form.processing"
                :rows="3"
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

            <!-- Affiliate Source -->
            <UFormField
                label="Affiliate Source"
                name="affiliate_source"
                :error="form.errors.affiliate_source"
                help="e.g., Amazon, eBay, Partner Store"
            >
                <UInput
                    v-model="form.affiliate_source"
                    placeholder="Amazon"
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
        </template>

        <MediaPickerModal
            v-model:open="mediaPickerOpen"
            type="images"
            :multiple="false"
            @select="onMediaSelect"
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
