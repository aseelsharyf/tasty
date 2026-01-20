<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useSettingsNav } from '../../composables/useSettingsNav';
import type { BreadcrumbItem } from '@nuxt/ui';

const { mainNav: settingsNav } = useSettingsNav();

interface PostTypeField {
    name: string;
    label: string;
    type: 'text' | 'number' | 'textarea' | 'select' | 'toggle' | 'repeater' | 'grouped-repeater';
    suffix?: string;
    options?: string[];
}

interface PostType {
    slug: string;
    name: string;
    icon: string;
    is_default?: boolean;
    fields: PostTypeField[];
}

const props = defineProps<{
    postTypes: PostType[];
    defaultPostTypes: PostType[];
}>();

const form = useForm({
    post_types: [...props.postTypes] as PostType[],
});

const editingPostType = ref<number | null>(null);
const editingField = ref<{ postTypeIndex: number; fieldIndex: number } | null>(null);
const addPostTypeModal = ref(false);
const addFieldModal = ref(false);
const editFieldModal = ref(false);
const deletePostTypeModal = ref(false);
const postTypeToDelete = ref<number | null>(null);

const newPostType = ref<PostType>({
    slug: '',
    name: '',
    icon: 'i-lucide-file-text',
    fields: [],
});

const newField = ref<PostTypeField>({
    name: '',
    label: '',
    type: 'text',
    suffix: '',
    options: [],
});

const fieldTypeOptions = [
    { label: 'Text', value: 'text' },
    { label: 'Number', value: 'number' },
    { label: 'Textarea', value: 'textarea' },
    { label: 'Select', value: 'select' },
    { label: 'Toggle', value: 'toggle' },
    { label: 'Repeater', value: 'repeater' },
    { label: 'Grouped Repeater', value: 'grouped-repeater' },
];

const iconOptions = [
    { label: 'File Text', value: 'i-lucide-file-text' },
    { label: 'Chef Hat', value: 'i-lucide-chef-hat' },
    { label: 'Newspaper', value: 'i-lucide-newspaper' },
    { label: 'Video', value: 'i-lucide-video' },
    { label: 'Image', value: 'i-lucide-image' },
    { label: 'Calendar', value: 'i-lucide-calendar' },
    { label: 'Star', value: 'i-lucide-star' },
    { label: 'Heart', value: 'i-lucide-heart' },
    { label: 'Book', value: 'i-lucide-book-open' },
    { label: 'Mic', value: 'i-lucide-mic' },
];

function slugify(text: string): string {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function openAddPostTypeModal() {
    newPostType.value = {
        slug: '',
        name: '',
        icon: 'i-lucide-file-text',
        fields: [],
    };
    addPostTypeModal.value = true;
}

function addPostType() {
    if (!newPostType.value.slug) {
        newPostType.value.slug = slugify(newPostType.value.name);
    }
    form.post_types.push({ ...newPostType.value });
    addPostTypeModal.value = false;
    saveChanges();
}

function confirmDeletePostType(index: number) {
    postTypeToDelete.value = index;
    deletePostTypeModal.value = true;
}

function deletePostType() {
    if (postTypeToDelete.value !== null) {
        form.post_types.splice(postTypeToDelete.value, 1);
        deletePostTypeModal.value = false;
        postTypeToDelete.value = null;
        saveChanges();
    }
}

function openAddFieldModal(postTypeIndex: number) {
    editingPostType.value = postTypeIndex;
    newField.value = {
        name: '',
        label: '',
        type: 'text',
        suffix: '',
        options: [],
    };
    addFieldModal.value = true;
}

function addField() {
    if (editingPostType.value !== null) {
        if (!newField.value.name) {
            newField.value.name = slugify(newField.value.label).replace(/-/g, '_');
        }
        form.post_types[editingPostType.value].fields.push({ ...newField.value });
        addFieldModal.value = false;
        saveChanges();
    }
}

function openEditFieldModal(postTypeIndex: number, fieldIndex: number) {
    editingField.value = { postTypeIndex, fieldIndex };
    const field = form.post_types[postTypeIndex].fields[fieldIndex];
    newField.value = { ...field, options: field.options ? [...field.options] : [] };
    editFieldModal.value = true;
}

function updateField() {
    if (editingField.value !== null) {
        const { postTypeIndex, fieldIndex } = editingField.value;
        form.post_types[postTypeIndex].fields[fieldIndex] = { ...newField.value };
        editFieldModal.value = false;
        editingField.value = null;
        saveChanges();
    }
}

function removeField(postTypeIndex: number, fieldIndex: number) {
    form.post_types[postTypeIndex].fields.splice(fieldIndex, 1);
    saveChanges();
}

function moveFieldUp(postTypeIndex: number, fieldIndex: number) {
    if (fieldIndex > 0) {
        const fields = form.post_types[postTypeIndex].fields;
        [fields[fieldIndex - 1], fields[fieldIndex]] = [fields[fieldIndex], fields[fieldIndex - 1]];
        saveChanges();
    }
}

function moveFieldDown(postTypeIndex: number, fieldIndex: number) {
    const fields = form.post_types[postTypeIndex].fields;
    if (fieldIndex < fields.length - 1) {
        [fields[fieldIndex], fields[fieldIndex + 1]] = [fields[fieldIndex + 1], fields[fieldIndex]];
        saveChanges();
    }
}

function resetToDefaults() {
    form.post_types = [...props.defaultPostTypes];
    saveChanges();
}

const toast = useToast();

function saveChanges() {
    form.put('/cms/settings/post-types', {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ title: 'Saved', description: 'Post types updated successfully.', color: 'success' });
        },
        onError: (errors) => {
            toast.add({ title: 'Error', description: 'Failed to save post types.', color: 'error' });
            console.error('Post types save error:', errors);
        },
    });
}

const breadcrumbs: BreadcrumbItem[] = [
    { label: 'Settings', to: '/cms/settings' },
    { label: 'Post Types' },
];

const hasChanges = computed(() => {
    return JSON.stringify(form.post_types) !== JSON.stringify(props.postTypes);
});
</script>

<template>
    <Head title="Post Types Settings" />

    <DashboardLayout>
        <UDashboardPanel id="settings-post-types" :ui="{ body: 'lg:py-12' }">
            <template #header>
                <UDashboardNavbar title="Settings">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="settingsNav" highlight class="-mx-1 flex-1 overflow-x-auto" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-4 sm:gap-6 lg:gap-12 w-full lg:max-w-3xl mx-auto">
                    <UForm
                        id="post-types-settings"
                        :state="form"
                        @submit="saveChanges"
                    >
                        <UPageCard
                            title="Post Types"
                            description="Configure the types of content you can create."
                            variant="naked"
                            orientation="horizontal"
                            class="mb-4"
                        >
                            <div class="flex gap-2 lg:ms-auto">
                                <UButton
                                    color="neutral"
                                    variant="ghost"
                                    @click="resetToDefaults"
                                    :disabled="form.processing"
                                >
                                    Reset to Defaults
                                </UButton>
                                <UButton
                                    form="post-types-settings"
                                    type="submit"
                                    :loading="form.processing"
                                    :disabled="!hasChanges"
                                >
                                    Save Changes
                                </UButton>
                            </div>
                        </UPageCard>

                        <!-- Post Types List -->
                        <div class="space-y-4">
                            <div
                                v-for="(postType, ptIndex) in form.post_types"
                                :key="postType.slug"
                                class="border border-default rounded-xl overflow-hidden"
                            >
                                <!-- Post Type Header -->
                                <div class="bg-elevated/50 p-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center size-10 rounded-lg bg-primary/10">
                                            <UIcon :name="postType.icon" class="size-5 text-primary" />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-highlighted">{{ postType.name }}</h3>
                                            <p class="text-sm text-muted">Slug: {{ postType.slug }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <UBadge v-if="postType.is_default" color="neutral" variant="subtle" size="xs">
                                            Default
                                        </UBadge>
                                        <UButton
                                            icon="i-lucide-plus"
                                            color="neutral"
                                            variant="ghost"
                                            size="sm"
                                            @click="openAddFieldModal(ptIndex)"
                                        >
                                            Add Field
                                        </UButton>
                                        <UButton
                                            v-if="!postType.is_default"
                                            icon="i-lucide-trash"
                                            color="error"
                                            variant="ghost"
                                            size="sm"
                                            @click="confirmDeletePostType(ptIndex)"
                                        />
                                    </div>
                                </div>

                                <!-- Custom Fields -->
                                <div v-if="postType.fields.length > 0" class="p-4 space-y-2">
                                    <p class="text-xs font-medium text-muted uppercase tracking-wide mb-3">
                                        Custom Fields
                                    </p>
                                    <div
                                        v-for="(field, fIndex) in postType.fields"
                                        :key="field.name"
                                        class="flex items-center gap-3 p-3 bg-elevated/30 rounded-lg group"
                                    >
                                        <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                type="button"
                                                class="p-0.5 hover:bg-muted/20 rounded"
                                                :disabled="fIndex === 0"
                                                @click="moveFieldUp(ptIndex, fIndex)"
                                            >
                                                <UIcon name="i-lucide-chevron-up" class="size-3" />
                                            </button>
                                            <button
                                                type="button"
                                                class="p-0.5 hover:bg-muted/20 rounded"
                                                :disabled="fIndex === postType.fields.length - 1"
                                                @click="moveFieldDown(ptIndex, fIndex)"
                                            >
                                                <UIcon name="i-lucide-chevron-down" class="size-3" />
                                            </button>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-highlighted">{{ field.label }}</span>
                                                <UBadge color="neutral" variant="subtle" size="xs">
                                                    {{ field.type }}
                                                </UBadge>
                                                <span v-if="field.suffix" class="text-xs text-muted">
                                                    ({{ field.suffix }})
                                                </span>
                                            </div>
                                            <p class="text-xs text-muted font-mono">{{ field.name }}</p>
                                            <p v-if="field.type === 'select' && field.options?.length" class="text-xs text-muted mt-0.5">
                                                Options: {{ field.options.join(', ') }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <UButton
                                                icon="i-lucide-pencil"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                @click="openEditFieldModal(ptIndex, fIndex)"
                                            />
                                            <UButton
                                                icon="i-lucide-x"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                @click="removeField(ptIndex, fIndex)"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div v-else class="p-4 text-center text-muted">
                                    <p class="text-sm">No custom fields. Standard fields (title, content, etc.) are always available.</p>
                                </div>
                            </div>

                            <!-- Add Post Type Button -->
                            <button
                                type="button"
                                class="w-full p-4 border-2 border-dashed border-default rounded-xl text-muted hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-2"
                                @click="openAddPostTypeModal"
                            >
                                <UIcon name="i-lucide-plus" class="size-5" />
                                <span>Add Post Type</span>
                            </button>
                        </div>
                    </UForm>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Add Post Type Modal -->
        <UModal v-model:open="addPostTypeModal">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Add Post Type</h3>

                    <UForm :state="newPostType" class="space-y-4" @submit="addPostType">
                        <UFormField label="Name" name="name" required>
                            <UInput
                                v-model="newPostType.name"
                                placeholder="e.g., Video"
                                class="w-full"
                                @input="newPostType.slug = slugify(newPostType.name)"
                            />
                        </UFormField>

                        <UFormField label="Slug" name="slug" help="URL-friendly version (auto-generated)">
                            <UInput
                                v-model="newPostType.slug"
                                placeholder="video"
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField label="Icon" name="icon">
                            <USelectMenu
                                v-model="newPostType.icon"
                                :items="iconOptions"
                                value-key="value"
                                class="w-full"
                            >
                                <template #leading>
                                    <UIcon :name="newPostType.icon" class="size-4" />
                                </template>
                            </USelectMenu>
                        </UFormField>

                        <div class="flex justify-end gap-2 pt-6">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                @click="addPostTypeModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :disabled="!newPostType.name"
                            >
                                Create
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>

        <!-- Add Field Modal -->
        <UModal v-model:open="addFieldModal">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Add Custom Field</h3>

                    <UForm :state="newField" class="space-y-4" @submit="addField">
                        <UFormField label="Label" name="label" required>
                            <UInput
                                v-model="newField.label"
                                placeholder="e.g., Prep Time"
                                class="w-full"
                                @input="newField.name = slugify(newField.label).replace(/-/g, '_')"
                            />
                        </UFormField>

                        <UFormField label="Name (key)" name="name" help="Auto-generated from label">
                            <UInput
                                v-model="newField.name"
                                placeholder="prep_time"
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField label="Type" name="type">
                            <USelectMenu
                                v-model="newField.type"
                                :items="fieldTypeOptions"
                                value-key="value"
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField v-if="newField.type === 'number'" label="Suffix" name="suffix">
                            <UInput
                                v-model="newField.suffix"
                                placeholder="e.g., min, kg, etc."
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField v-if="newField.type === 'select'" label="Options" name="options" help="Comma-separated list">
                            <UInput
                                :model-value="newField.options?.join(', ') ?? ''"
                                placeholder="Easy, Medium, Hard"
                                class="w-full"
                                @update:model-value="(v: string) => newField.options = v.split(',').map(s => s.trim()).filter(Boolean)"
                            />
                        </UFormField>

                        <div class="flex justify-end gap-2 pt-6">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                @click="addFieldModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :disabled="!newField.label"
                            >
                                Create
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>

        <!-- Delete Post Type Modal -->
        <UModal v-model:open="deletePostTypeModal">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Delete Post Type</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete this post type? Existing posts of this type will not be affected, but you won't be able to create new ones.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-6">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            @click="deletePostTypeModal = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="error"
                            @click="deletePostType"
                        >
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Field Modal -->
        <UModal v-model:open="editFieldModal">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <h3 class="text-lg font-semibold text-highlighted mb-4">Edit Field</h3>

                    <UForm :state="newField" class="space-y-4" @submit="updateField">
                        <UFormField label="Label" name="label" required>
                            <UInput
                                v-model="newField.label"
                                placeholder="e.g., Prep Time"
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField label="Name (key)" name="name" help="Used in code and database">
                            <UInput
                                v-model="newField.name"
                                placeholder="prep_time"
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField label="Type" name="type">
                            <USelectMenu
                                v-model="newField.type"
                                :items="fieldTypeOptions"
                                value-key="value"
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField v-if="newField.type === 'number'" label="Suffix" name="suffix">
                            <UInput
                                v-model="newField.suffix"
                                placeholder="e.g., min, kg, etc."
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField v-if="newField.type === 'select'" label="Options" name="options" help="Comma-separated list of options">
                            <UInput
                                :model-value="newField.options?.join(', ') ?? ''"
                                placeholder="Easy, Medium, Hard"
                                class="w-full"
                                @update:model-value="(v: string) => newField.options = v.split(',').map(s => s.trim()).filter(Boolean)"
                            />
                        </UFormField>

                        <div class="flex justify-end gap-2 pt-6">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                @click="editFieldModal = false; editingField = null;"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :disabled="!newField.label"
                            >
                                Save Changes
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
