<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface FocalPoint {
    x: number;
    y: number;
}

interface Props {
    modelValue: FocalPoint;
    imageUrl?: string | null;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({ x: 50, y: 0 }),
    imageUrl: null,
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: FocalPoint];
}>();

const containerRef = ref<HTMLElement | null>(null);
const isDragging = ref(false);

// Ensure we always have valid x/y values
const focalPoint = computed(() => ({
    x: props.modelValue?.x ?? 50,
    y: props.modelValue?.y ?? 0,
}));

// Convert focal point to CSS object-position
const objectPosition = computed(() => {
    return `${focalPoint.value.x}% ${focalPoint.value.y}%`;
});

// Pin position style
const pinStyle = computed(() => ({
    left: `${focalPoint.value.x}%`,
    top: `${focalPoint.value.y}%`,
}));

function updatePosition(clientX: number, clientY: number) {
    if (!containerRef.value || props.disabled) return;

    const rect = containerRef.value.getBoundingClientRect();

    // Calculate percentage position
    let x = ((clientX - rect.left) / rect.width) * 100;
    let y = ((clientY - rect.top) / rect.height) * 100;

    // Clamp values between 0 and 100
    x = Math.max(0, Math.min(100, x));
    y = Math.max(0, Math.min(100, y));

    // Round to 1 decimal place for cleaner values
    x = Math.round(x * 10) / 10;
    y = Math.round(y * 10) / 10;

    emit('update:modelValue', { x, y });
}

function handleMouseDown(e: MouseEvent) {
    if (props.disabled) return;
    isDragging.value = true;
    updatePosition(e.clientX, e.clientY);
}

function handleMouseMove(e: MouseEvent) {
    if (!isDragging.value) return;
    e.preventDefault();
    updatePosition(e.clientX, e.clientY);
}

function handleMouseUp() {
    isDragging.value = false;
}

function handleTouchStart(e: TouchEvent) {
    if (props.disabled || !e.touches[0]) return;
    isDragging.value = true;
    updatePosition(e.touches[0].clientX, e.touches[0].clientY);
}

function handleTouchMove(e: TouchEvent) {
    if (!isDragging.value || !e.touches[0]) return;
    e.preventDefault();
    updatePosition(e.touches[0].clientX, e.touches[0].clientY);
}

function handleTouchEnd() {
    isDragging.value = false;
}

// Global mouse/touch event listeners for drag outside container
onMounted(() => {
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
    document.addEventListener('touchmove', handleTouchMove, { passive: false });
    document.addEventListener('touchend', handleTouchEnd);
});

onUnmounted(() => {
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
    document.removeEventListener('touchmove', handleTouchMove);
    document.removeEventListener('touchend', handleTouchEnd);
});

// Reset to center
function resetToCenter() {
    if (!props.disabled) {
        emit('update:modelValue', { x: 50, y: 0 });
    }
}
</script>

<template>
    <div class="space-y-2">
        <!-- Image with draggable focal point -->
        <div
            v-if="imageUrl"
            ref="containerRef"
            class="relative rounded-lg overflow-hidden bg-muted/20 border border-default select-none"
            :class="[
                disabled ? 'cursor-not-allowed' : 'cursor-crosshair',
                isDragging ? 'ring-2 ring-primary' : '',
            ]"
            @mousedown="handleMouseDown"
            @touchstart="handleTouchStart"
        >
            <!-- Image preview with aspect ratio -->
            <div class="relative aspect-[16/9] overflow-hidden">
                <img
                    :src="imageUrl"
                    alt="Featured image preview"
                    class="absolute inset-0 w-full h-full object-cover transition-[object-position] duration-150"
                    :style="{ objectPosition }"
                    draggable="false"
                />

                <!-- Crosshair guide lines -->
                <div
                    class="absolute inset-0 pointer-events-none transition-opacity duration-150"
                    :class="isDragging ? 'opacity-100' : 'opacity-0'"
                >
                    <!-- Vertical line -->
                    <div
                        class="absolute top-0 bottom-0 w-px bg-white/50"
                        :style="{ left: `${focalPoint.x}%` }"
                    />
                    <!-- Horizontal line -->
                    <div
                        class="absolute left-0 right-0 h-px bg-white/50"
                        :style="{ top: `${focalPoint.y}%` }"
                    />
                </div>

                <!-- Focal point pin -->
                <div
                    class="absolute -translate-x-1/2 -translate-y-1/2 pointer-events-none transition-transform duration-150"
                    :class="isDragging ? 'scale-110' : 'scale-100'"
                    :style="pinStyle"
                >
                    <!-- Outer ring -->
                    <div class="relative">
                        <div
                            class="size-8 rounded-full border-2 border-white shadow-lg flex items-center justify-center"
                            :class="isDragging ? 'bg-primary/80' : 'bg-primary'"
                        >
                            <!-- Inner crosshair -->
                            <div class="relative size-4">
                                <div class="absolute inset-x-0 top-1/2 h-0.5 -translate-y-1/2 bg-white rounded-full" />
                                <div class="absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 bg-white rounded-full" />
                            </div>
                        </div>
                        <!-- Pulsing ring when dragging -->
                        <div
                            v-if="isDragging"
                            class="absolute inset-0 rounded-full border-2 border-primary animate-ping"
                        />
                    </div>
                </div>

                <!-- Position indicator -->
                <div class="absolute bottom-2 left-2 px-2 py-1 rounded bg-black/70 text-white text-xs font-medium flex items-center gap-2">
                    <span>{{ Math.round(focalPoint.x) }}%, {{ Math.round(focalPoint.y) }}%</span>
                    <button
                        v-if="!disabled && (focalPoint.x !== 50 || focalPoint.y !== 0)"
                        type="button"
                        class="hover:text-primary transition-colors"
                        title="Reset to top center"
                        @click.stop="resetToCenter"
                    >
                        <UIcon name="i-lucide-rotate-ccw" class="size-3" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Fallback when no image -->
        <div v-else class="flex items-center gap-3 p-4 rounded-lg bg-muted/10 border border-dashed border-default">
            <UIcon name="i-lucide-image" class="size-8 text-muted" />
            <div>
                <p class="text-sm text-muted">No image selected</p>
                <p class="text-xs text-muted/70">Focal point: {{ Math.round(focalPoint.x) }}%, {{ Math.round(focalPoint.y) }}%</p>
            </div>
        </div>
    </div>
</template>
