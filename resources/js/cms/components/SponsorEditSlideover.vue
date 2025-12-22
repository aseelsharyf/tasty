<script setup lang="ts">
import { watch, ref, computed } from 'vue';
import SponsorForm from './SponsorForm.vue';
import type { Language } from '../types';

interface MediaItem {
    id: number;
    url: string;
    title?: string;
}

interface SponsorWithTranslations {
    id?: number;
    uuid?: string;
    name?: string;
    name_translations?: Record<string, string>;
    url?: string;
    url_translations?: Record<string, string>;
    slug?: string;
    featured_media_id?: number | null;
    featured_media?: MediaItem | null;
    is_active?: boolean;
    order?: number;
}

const props = defineProps<{
    sponsor: SponsorWithTranslations | null;
    languages: Language[];
}>();

const emit = defineEmits<{
    (e: 'close', updated: boolean): void;
}>();

const open = defineModel<boolean>('open', { default: false });
const formRef = ref<InstanceType<typeof SponsorForm> | null>(null);

const sponsorData = computed(() => props.sponsor);

function onSuccess() {
    emit('close', true);
    open.value = false;
}

function onCancel() {
    emit('close', false);
    open.value = false;
}

// When sponsor changes, update the form
watch(() => props.sponsor, (newSponsor) => {
    if (newSponsor && formRef.value) {
        // Form will update via its own watcher
    }
}, { deep: true });
</script>

<template>
    <USlideover
        v-model:open="open"
        title="Edit Sponsor"
        description="Update sponsor details."
    >
        <template #body>
            <SponsorForm
                v-if="sponsorData"
                ref="formRef"
                :sponsor="sponsorData"
                :languages="languages"
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
