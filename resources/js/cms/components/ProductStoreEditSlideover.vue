<script setup lang="ts">
import { watch, ref, computed } from 'vue';
import ProductStoreForm from './ProductStoreForm.vue';

interface MediaItem {
    id: number;
    url: string;
    title?: string;
}

interface ProductStore {
    id?: number;
    uuid?: string;
    name?: string;
    address?: string | null;
    logo_media_id?: number | null;
    logo?: MediaItem | null;
    hotline?: string | null;
    is_active?: boolean;
    order?: number;
    products_count?: number;
}

const props = defineProps<{
    store: ProductStore | null;
}>();

const emit = defineEmits<{
    (e: 'close', updated: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof ProductStoreForm> | null>(null);

const storeData = computed(() => props.store);

function onSuccess() {
    emit('close', true);
    open.value = false;
}

function onCancel() {
    emit('close', false);
    open.value = false;
}

// When store changes, update the form
watch(() => props.store, (newStore) => {
    if (newStore && formRef.value) {
        // Form will update via its own watcher
    }
}, { deep: true });
</script>

<template>
    <USlideover
        v-model:open="open"
        title="Edit Client"
        description="Update client details."
    >
        <template #body>
            <ProductStoreForm
                v-if="storeData"
                ref="formRef"
                :store="storeData"
                mode="edit"
                @success="onSuccess"
                @cancel="onCancel"
            />
            <div v-else class="flex items-center justify-center py-12">
                <UIcon name="i-lucide-loader-2" class="size-8 animate-spin text-muted" />
            </div>
        </template>
    </USlideover>
</template>
