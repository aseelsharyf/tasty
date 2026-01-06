<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { decode } from 'blurhash';

const props = withDefaults(defineProps<{
    src: string;
    alt?: string;
    blurhash?: string | null;
    width?: number;
    height?: number;
    class?: string;
    imgClass?: string;
    objectFit?: 'cover' | 'contain' | 'fill' | 'none' | 'scale-down';
    lazy?: boolean;
}>(), {
    alt: '',
    blurhash: null,
    width: 32,
    height: 32,
    objectFit: 'cover',
    lazy: true,
});

const emit = defineEmits<{
    (e: 'load'): void;
    (e: 'error', error: Event): void;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const imageLoaded = ref(false);
const imageError = ref(false);

const shouldShowPlaceholder = computed(() => {
    return props.blurhash && !imageLoaded.value && !imageError.value;
});

const renderBlurhash = () => {
    if (!canvasRef.value || !props.blurhash) return;

    try {
        const pixels = decode(props.blurhash, props.width, props.height);
        const ctx = canvasRef.value.getContext('2d');
        if (!ctx) return;

        const imageData = ctx.createImageData(props.width, props.height);
        imageData.data.set(pixels);
        ctx.putImageData(imageData, 0, 0);
    } catch (e) {
        console.warn('Failed to decode blurhash:', e);
    }
};

const onImageLoad = () => {
    imageLoaded.value = true;
    emit('load');
};

const onImageError = (event: Event) => {
    imageError.value = true;
    emit('error', event);
};

onMounted(() => {
    renderBlurhash();
});

watch(() => props.blurhash, () => {
    imageLoaded.value = false;
    imageError.value = false;
    renderBlurhash();
});

watch(() => props.src, () => {
    imageLoaded.value = false;
    imageError.value = false;
});
</script>

<template>
    <div :class="['relative overflow-hidden', props.class]">
        <!-- Blurhash placeholder -->
        <canvas
            v-if="shouldShowPlaceholder"
            ref="canvasRef"
            :width="width"
            :height="height"
            class="absolute inset-0 w-full h-full"
            :style="{ objectFit: objectFit }"
        />

        <!-- Actual image -->
        <img
            :src="src"
            :alt="alt"
            :loading="lazy ? 'lazy' : 'eager'"
            :class="[
                'w-full h-full transition-opacity duration-300',
                imgClass,
                imageLoaded ? 'opacity-100' : 'opacity-0'
            ]"
            :style="{ objectFit: objectFit }"
            @load="onImageLoad"
            @error="onImageError"
        />

        <!-- Fallback when no blurhash and image not loaded -->
        <div
            v-if="!blurhash && !imageLoaded && !imageError"
            class="absolute inset-0 bg-gray-200 dark:bg-gray-700 animate-pulse"
        />
    </div>
</template>
