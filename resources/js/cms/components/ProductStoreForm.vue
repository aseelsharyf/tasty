<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import MediaPickerModal from './MediaPickerModal.vue';

interface MediaItem {
    id: number;
    uuid?: string;
    url: string | null;
    thumbnail_url?: string | null;
    title?: string | null;
    is_image?: boolean;
}

interface ProductStore {
    id?: number;
    uuid?: string;
    name?: string;
    slug?: string | null;
    business_type?: string | null;
    address?: string | null;
    location_label?: string | null;
    logo_media_id?: number | null;
    logo?: MediaItem | null;
    hotline?: string | null;
    contact_email?: string | null;
    website_url?: string | null;
    is_active?: boolean;
    order?: number;
    products_count?: number;
}

const props = withDefaults(defineProps<{
    store?: ProductStore;
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const isEditing = computed(() => props.mode === 'edit' && props.store?.uuid);

const businessTypeOptions = [
    { label: 'Retail', value: 'retail' },
    { label: 'Distributor', value: 'distributor' },
    { label: 'Restaurant', value: 'restaurant' },
];

const form = useForm({
    name: props.store?.name || '',
    slug: props.store?.slug || '',
    business_type: props.store?.business_type || null as string | null,
    address: props.store?.address || '',
    location_label: props.store?.location_label || '',
    logo_media_id: props.store?.logo_media_id || null as number | null,
    hotline: props.store?.hotline || '',
    contact_email: props.store?.contact_email || '',
    website_url: props.store?.website_url || '',
    is_active: props.store?.is_active ?? true,
});

const selectedMedia = ref<MediaItem | null>(props.store?.logo || null);
const mediaPickerOpen = ref(false);

function onMediaSelect(media: MediaItem[]) {
    if (media.length > 0) {
        selectedMedia.value = media[0];
        form.logo_media_id = media[0].id;
    }
}

function removeMedia() {
    selectedMedia.value = null;
    form.logo_media_id = null;
}

function onSubmit() {
    if (isEditing.value && props.store?.uuid) {
        form.put(`/cms/product-stores/${props.store.uuid}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post('/cms/product-stores', {
            preserveScroll: true,
            onSuccess: () => {
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
    form.name = '';
    form.slug = '';
    form.business_type = null;
    form.address = '';
    form.location_label = '';
    form.logo_media_id = null;
    form.hotline = '';
    form.contact_email = '';
    form.website_url = '';
    form.is_active = true;
    selectedMedia.value = null;
    form.clearErrors();
}

watch(() => props.store, (newStore) => {
    if (newStore) {
        form.name = newStore.name || '';
        form.slug = newStore.slug || '';
        form.business_type = newStore.business_type || null;
        form.address = newStore.address || '';
        form.location_label = newStore.location_label || '';
        form.logo_media_id = newStore.logo_media_id || null;
        form.hotline = newStore.hotline || '';
        form.contact_email = newStore.contact_email || '';
        form.website_url = newStore.website_url || '';
        form.is_active = newStore.is_active ?? true;
        selectedMedia.value = newStore.logo || null;
    }
}, { immediate: true, deep: true });

defineExpose({ reset, form });
</script>

<template>
    <UForm
        :state="form"
        class="space-y-4"
        @submit="onSubmit"
    >
        <!-- Client Name -->
        <UFormField
            label="Client Name"
            name="name"
            :error="form.errors.name"
            required
        >
            <UInput
                v-model="form.name"
                placeholder="e.g., Sosun Fihaara"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Slug -->
        <UFormField
            label="Slug"
            name="slug"
            :error="form.errors.slug"
            help="URL-friendly identifier. Leave empty to auto-generate from name."
        >
            <UInput
                v-model="form.slug"
                placeholder="e.g., sosun-fihaara"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Business Type -->
        <UFormField
            label="Business Type"
            name="business_type"
            :error="form.errors.business_type"
        >
            <USelectMenu
                v-model="form.business_type"
                :items="businessTypeOptions"
                value-key="value"
                placeholder="Select business type"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Logo -->
        <UFormField
            label="Logo"
            name="logo_media_id"
            :error="form.errors.logo_media_id"
        >
            <div v-if="selectedMedia" class="flex items-center gap-4 p-3 border border-default rounded-lg bg-elevated/50">
                <img
                    :src="selectedMedia.thumbnail_url || selectedMedia.url || ''"
                    :alt="selectedMedia.title || 'Client logo'"
                    class="size-16 object-contain rounded bg-white"
                >
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-highlighted truncate">
                        {{ selectedMedia.title || 'Client Logo' }}
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
                Select Logo
            </UButton>
        </UFormField>

        <MediaPickerModal
            v-model:open="mediaPickerOpen"
            type="images"
            :multiple="false"
            default-category="clients"
            @select="onMediaSelect"
        />

        <!-- Contact Email -->
        <UFormField
            label="Contact Email"
            name="contact_email"
            :error="form.errors.contact_email"
        >
            <UInput
                v-model="form.contact_email"
                type="email"
                placeholder="e.g., sales@brand.com"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Hotline -->
        <UFormField
            label="Hotline Phone"
            name="hotline"
            :error="form.errors.hotline"
        >
            <UInput
                v-model="form.hotline"
                placeholder="e.g., +960 123 4567"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Website URL -->
        <UFormField
            label="General Website"
            name="website_url"
            :error="form.errors.website_url"
        >
            <UInput
                v-model="form.website_url"
                type="url"
                placeholder="e.g., https://example.com"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Location Label -->
        <UFormField
            label="Location Label"
            name="location_label"
            :error="form.errors.location_label"
            help="Human readable location (e.g., Malé, Henveiru)"
        >
            <UInput
                v-model="form.location_label"
                placeholder="e.g., Malé, Henveiru"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <!-- Address -->
        <UFormField
            label="Address"
            name="address"
            :error="form.errors.address"
            help="Full address (e.g., Building, Street, Malé)"
        >
            <UTextarea
                v-model="form.address"
                placeholder="Full address..."
                class="w-full"
                :disabled="form.processing"
                rows="2"
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
                {{ isEditing ? 'Save Changes' : 'Create Client' }}
            </UButton>
        </div>
    </UForm>
</template>
