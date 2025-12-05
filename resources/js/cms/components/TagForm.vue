<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import type { Tag } from '../types';

const props = withDefaults(defineProps<{
    tag?: Tag;
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const isEditing = computed(() => props.mode === 'edit' && props.tag);

const form = useForm({
    name: props.tag?.name || '',
    slug: props.tag?.slug || '',
});

// Auto-generate slug from name (only in create mode)
watch(() => form.name, (newName) => {
    if (props.mode === 'create') {
        if (!form.slug || form.slug === slugify(form.name.slice(0, -1))) {
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

function onSubmit() {
    if (isEditing.value && props.tag) {
        form.put(`/cms/tags/${props.tag.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post('/cms/tags', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('success');
            },
        });
    }
}

function onCancel() {
    form.reset();
    form.clearErrors();
    emit('cancel');
}

// Reset form when tag changes (for edit mode)
watch(() => props.tag, (newTag) => {
    if (newTag) {
        form.name = newTag.name;
        form.slug = newTag.slug;
    }
}, { immediate: true });

// Expose reset method for parent components
function reset() {
    form.reset();
    form.clearErrors();
}

defineExpose({ reset, form });
</script>

<template>
    <UForm
        :state="form"
        class="space-y-4"
        @submit="onSubmit"
    >
        <UFormField label="Name" name="name" :error="form.errors.name" required>
            <UInput
                v-model="form.name"
                placeholder="e.g., Quick Meals"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <UFormField label="Slug" name="slug" :error="form.errors.slug" :help="mode === 'create' ? 'URL-friendly version (auto-generated)' : 'URL-friendly version'">
            <UInput
                v-model="form.slug"
                placeholder="quick-meals"
                class="w-full"
                :disabled="form.processing"
            />
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
                {{ isEditing ? 'Save' : 'Create' }}
            </UButton>
        </div>
    </UForm>
</template>
