<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import type { ParentOption, Category } from '../types';

const props = withDefaults(defineProps<{
    category?: Category;
    parentOptions?: ParentOption[];
    mode?: 'create' | 'edit';
}>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'success'): void;
    (e: 'cancel'): void;
}>();

const isEditing = computed(() => props.mode === 'edit' && props.category);

const form = useForm({
    name: props.category?.name || '',
    slug: props.category?.slug || '',
    description: props.category?.description || '',
    parent_id: props.category?.parent_id ?? null,
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
    if (isEditing.value && props.category) {
        form.put(`/cms/categories/${props.category.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post('/cms/categories', {
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

const parentSelectOptions = computed(() => {
    return [
        { label: 'None (Root Category)', value: null },
        ...(props.parentOptions || []).map(p => ({
            label: p.name,
            value: p.id,
        })),
    ];
});

// Reset form when category changes (for edit mode)
watch(() => props.category, (newCategory) => {
    if (newCategory) {
        form.name = newCategory.name;
        form.slug = newCategory.slug;
        form.description = newCategory.description || '';
        form.parent_id = newCategory.parent_id ?? null;
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
                placeholder="e.g., Main Courses"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <UFormField label="Slug" name="slug" :error="form.errors.slug" :help="mode === 'create' ? 'URL-friendly version (auto-generated)' : 'URL-friendly version'">
            <UInput
                v-model="form.slug"
                placeholder="main-courses"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <UFormField label="Description" name="description" :error="form.errors.description">
            <UTextarea
                v-model="form.description"
                placeholder="Describe this category..."
                :rows="3"
                class="w-full"
                :disabled="form.processing"
            />
        </UFormField>

        <UFormField
            v-if="parentSelectOptions.length > 1"
            label="Parent Category"
            name="parent_id"
            :error="form.errors.parent_id"
            help="Leave empty for a root-level category"
        >
            <USelectMenu
                v-model="form.parent_id"
                :items="parentSelectOptions"
                placeholder="Select parent..."
                value-key="value"
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
