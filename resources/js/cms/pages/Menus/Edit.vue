<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import draggable from 'vuedraggable';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import DhivehiInput from '../../components/DhivehiInput.vue';
import { usePermission } from '../../composables/usePermission';
import type { Menu, MenuItemTreeItem, Language, CategoryOption } from '../../types';

const props = defineProps<{
    menu: Menu;
    languages: Language[];
    categories: CategoryOption[];
    itemTypes: Record<string, string>;
}>();

const { can } = usePermission();

// Menu settings
const showSettingsSlideover = ref(false);
const activeSettingsTab = ref(props.languages[0]?.code || 'en');

// Menu items
const menuItems = ref<MenuItemTreeItem[]>(props.menu.items || []);
const showAddItemModal = ref(false);
const showEditItemModal = ref(false);
const itemToEdit = ref<MenuItemTreeItem | null>(null);
const deleteItemModalOpen = ref(false);
const itemToDelete = ref<MenuItemTreeItem | null>(null);

// Add/Edit item form
const activeItemTab = ref(props.languages[0]?.code || 'en');

function initLabelTranslations(): Record<string, string> {
    const translations: Record<string, string> = {};
    props.languages.forEach(lang => {
        translations[lang.code] = '';
    });
    return translations;
}

const itemForm = useForm({
    label: initLabelTranslations(),
    type: 'custom' as 'custom' | 'external' | 'category' | 'post',
    url: '',
    linkable_id: null as number | null,
    target: '_self' as '_self' | '_blank',
    icon: '',
    is_active: true,
    parent_id: null as number | null,
});

// Menu settings form
const settingsForm = useForm({
    name: {} as Record<string, string>,
    location: props.menu.location,
    description: {} as Record<string, string>,
    is_active: props.menu.is_active,
});

// Initialize settings form
watch(() => props.menu, (newMenu) => {
    if (newMenu) {
        props.languages.forEach(lang => {
            settingsForm.name[lang.code] = newMenu.name_translations?.[lang.code] || '';
            settingsForm.description[lang.code] = newMenu.description_translations?.[lang.code] || '';
        });
        settingsForm.location = newMenu.location;
        settingsForm.is_active = newMenu.is_active;
    }
}, { immediate: true });

// Watch for items changes from props
watch(() => props.menu.items, (newItems) => {
    if (newItems) {
        menuItems.value = JSON.parse(JSON.stringify(newItems));
    }
}, { immediate: true });

// Computed for drag options
const dragOptions = {
    animation: 200,
    group: 'menu-items',
    ghostClass: 'ghost',
    handle: '.drag-handle',
};

// Check if current language is RTL
const isCurrentRtl = computed(() => {
    const lang = props.languages.find(l => l.code === activeItemTab.value);
    return lang?.direction === 'rtl';
});

const isSettingsRtl = computed(() => {
    const lang = props.languages.find(l => l.code === activeSettingsTab.value);
    return lang?.direction === 'rtl';
});

const isDhivehi = computed(() => activeItemTab.value === 'dv');
const isSettingsDhivehi = computed(() => activeSettingsTab.value === 'dv');

// Category options for select
const categoryOptions = computed(() => {
    return props.categories.map(cat => ({
        label: cat.name,
        value: cat.id,
    }));
});

// Type options for select
const typeOptions = computed(() => {
    return Object.entries(props.itemTypes).map(([value, label]) => ({
        label,
        value,
    }));
});

// Parent options (flatten tree for select)
const parentOptions = computed(() => {
    const options: { label: string; value: number | null }[] = [
        { label: 'None (Root Level)', value: null },
    ];

    function addItems(items: MenuItemTreeItem[], depth = 0) {
        for (const item of items) {
            // Don't allow item to be its own parent (when editing)
            if (itemToEdit.value && item.id === itemToEdit.value.id) continue;

            const prefix = '— '.repeat(depth);
            options.push({
                label: prefix + item.label,
                value: item.id,
            });

            if (item.children && item.children.length > 0) {
                addItems(item.children, depth + 1);
            }
        }
    }

    addItems(menuItems.value);
    return options;
});

// Flatten items for reorder submission
function flattenForReorder(items: MenuItemTreeItem[], parentId: number | null = null): { id: number; order: number; parent_id: number | null }[] {
    const result: { id: number; order: number; parent_id: number | null }[] = [];

    items.forEach((item, index) => {
        result.push({
            id: item.id,
            order: index,
            parent_id: parentId,
        });

        if (item.children && item.children.length > 0) {
            result.push(...flattenForReorder(item.children, item.id));
        }
    });

    return result;
}

// Handle drag end - save new order
function onDragEnd() {
    const items = flattenForReorder(menuItems.value);

    router.post(`/cms/menus/${props.menu.uuid}/items/reorder`, {
        items,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

// Open add item modal
function openAddItemModal(parentId: number | null = null) {
    itemForm.reset();
    props.languages.forEach(lang => {
        itemForm.label[lang.code] = '';
    });
    itemForm.parent_id = parentId;
    activeItemTab.value = props.languages[0]?.code || 'en';
    showAddItemModal.value = true;
}

// Open edit item modal
function openEditItemModal(item: MenuItemTreeItem) {
    itemToEdit.value = item;
    props.languages.forEach(lang => {
        itemForm.label[lang.code] = item.label_translations?.[lang.code] || '';
    });
    itemForm.type = item.type;
    itemForm.url = item.url || '';
    itemForm.linkable_id = item.linkable_id;
    itemForm.target = item.target;
    itemForm.icon = item.icon || '';
    itemForm.is_active = item.is_active;
    itemForm.parent_id = item.parent_id;
    activeItemTab.value = props.languages[0]?.code || 'en';
    showEditItemModal.value = true;
}

// Confirm delete item
function confirmDeleteItem(item: MenuItemTreeItem) {
    itemToDelete.value = item;
    deleteItemModalOpen.value = true;
}

// Delete item
function deleteItem() {
    if (!itemToDelete.value) return;

    router.delete(`/cms/menus/${props.menu.uuid}/items/${itemToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteItemModalOpen.value = false;
            itemToDelete.value = null;
        },
    });
}

// Add item
function addItem() {
    const labelData = Object.fromEntries(
        Object.entries(itemForm.label).filter(([_, v]) => v?.trim())
    );

    itemForm.transform(() => ({
        label: labelData,
        type: itemForm.type,
        url: itemForm.type === 'custom' || itemForm.type === 'external' ? itemForm.url : null,
        linkable_id: itemForm.type === 'category' ? itemForm.linkable_id : null,
        target: itemForm.target,
        icon: itemForm.icon || null,
        is_active: itemForm.is_active,
        parent_id: itemForm.parent_id,
    }));

    itemForm.post(`/cms/menus/${props.menu.uuid}/items`, {
        preserveScroll: true,
        onSuccess: () => {
            showAddItemModal.value = false;
            itemForm.reset();
        },
    });
}

// Update item
function updateItem() {
    if (!itemToEdit.value) return;

    const labelData = Object.fromEntries(
        Object.entries(itemForm.label).filter(([_, v]) => v?.trim())
    );

    itemForm.transform(() => ({
        label: labelData,
        type: itemForm.type,
        url: itemForm.type === 'custom' || itemForm.type === 'external' ? itemForm.url : null,
        linkable_id: itemForm.type === 'category' ? itemForm.linkable_id : null,
        target: itemForm.target,
        icon: itemForm.icon || null,
        is_active: itemForm.is_active,
    }));

    itemForm.put(`/cms/menus/${props.menu.uuid}/items/${itemToEdit.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditItemModal.value = false;
            itemToEdit.value = null;
            itemForm.reset();
        },
    });
}

// Update menu settings
function updateSettings() {
    const nameData = Object.fromEntries(
        Object.entries(settingsForm.name).filter(([_, v]) => v?.trim())
    );
    const descData = Object.fromEntries(
        Object.entries(settingsForm.description).filter(([_, v]) => v?.trim())
    );

    settingsForm.transform(() => ({
        name: nameData,
        location: settingsForm.location,
        description: Object.keys(descData).length > 0 ? descData : null,
        is_active: settingsForm.is_active,
    }));

    settingsForm.put(`/cms/menus/${props.menu.uuid}`, {
        preserveScroll: true,
        onSuccess: () => {
            showSettingsSlideover.value = false;
        },
    });
}

// Get type icon
function getTypeIcon(type: string): string {
    switch (type) {
        case 'custom': return 'i-lucide-link';
        case 'external': return 'i-lucide-external-link';
        case 'category': return 'i-lucide-folder';
        case 'post': return 'i-lucide-file-text';
        default: return 'i-lucide-link';
    }
}

// Check if has translation
function hasTranslation(langCode: string): boolean {
    return !!(itemForm.label[langCode]?.trim());
}

function hasSettingsTranslation(langCode: string): boolean {
    return !!(settingsForm.name[langCode]?.trim());
}
</script>

<template>
    <Head :title="`Edit Menu: ${menu.name}`" />

    <DashboardLayout>
        <UDashboardPanel id="menu-edit">
            <template #header>
                <UDashboardNavbar :ui="{ title: 'truncate max-w-[200px] sm:max-w-none' }">
                    <template #leading>
                        <UButton
                            icon="i-lucide-arrow-left"
                            color="neutral"
                            variant="ghost"
                            to="/cms/menus"
                            class="shrink-0"
                        />
                    </template>

                    <template #title>
                        <span class="hidden sm:inline">Edit:</span>
                        <span class="truncate">{{ menu.name }}</span>
                    </template>

                    <template #right>
                        <div class="flex items-center gap-2">
                            <UBadge
                                :color="menu.is_active ? 'success' : 'neutral'"
                                variant="subtle"
                            >
                                {{ menu.is_active ? 'Active' : 'Inactive' }}
                            </UBadge>
                            <UButton
                                v-if="can('menus.edit')"
                                icon="i-lucide-settings"
                                color="neutral"
                                variant="outline"
                                @click="showSettingsSlideover = true"
                            >
                                Settings
                            </UButton>
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-4xl mx-auto">
                    <!-- Menu Info -->
                    <div class="mb-6 p-4 rounded-lg border border-default bg-elevated/30">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-highlighted">{{ menu.name }}</h2>
                                <p class="text-sm text-muted">
                                    Location: <code class="px-1 py-0.5 rounded bg-muted/20">{{ menu.location }}</code>
                                </p>
                            </div>
                            <UButton
                                v-if="can('menus.edit')"
                                icon="i-lucide-plus"
                                @click="openAddItemModal(null)"
                            >
                                Add Item
                            </UButton>
                        </div>
                    </div>

                    <!-- Menu Items Tree -->
                    <div class="space-y-2">
                        <div v-if="menuItems.length === 0" class="text-center py-12 text-muted">
                            <UIcon name="i-lucide-list" class="size-12 mx-auto mb-4 opacity-50" />
                            <p>No menu items yet.</p>
                            <UButton
                                v-if="can('menus.edit')"
                                class="mt-4"
                                variant="outline"
                                @click="openAddItemModal(null)"
                            >
                                Add your first item
                            </UButton>
                        </div>

                        <draggable
                            v-else
                            v-model="menuItems"
                            v-bind="dragOptions"
                            item-key="id"
                            @end="onDragEnd"
                        >
                            <template #item="{ element }">
                                <div class="menu-item">
                                    <div
                                        class="flex items-center gap-3 p-3 rounded-lg border border-default bg-default hover:bg-elevated/50 transition-colors"
                                        :class="{ 'opacity-50': !element.is_active }"
                                    >
                                        <div class="drag-handle cursor-grab active:cursor-grabbing">
                                            <UIcon name="i-lucide-grip-vertical" class="size-5 text-muted" />
                                        </div>

                                        <UIcon
                                            :name="getTypeIcon(element.type)"
                                            class="size-5 text-muted"
                                        />

                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-highlighted truncate">
                                                {{ element.label }}
                                            </div>
                                            <div class="text-xs text-muted truncate">
                                                {{ element.url || (element.type === 'category' ? 'Category Link' : '') }}
                                            </div>
                                        </div>

                                        <UBadge
                                            :color="element.is_active ? 'success' : 'neutral'"
                                            variant="subtle"
                                            size="xs"
                                        >
                                            {{ element.is_active ? 'Active' : 'Inactive' }}
                                        </UBadge>

                                        <div class="flex items-center gap-1">
                                            <UButton
                                                v-if="can('menus.edit')"
                                                icon="i-lucide-plus"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                title="Add child item"
                                                @click="openAddItemModal(element.id)"
                                            />
                                            <UButton
                                                v-if="can('menus.edit')"
                                                icon="i-lucide-pencil"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                @click="openEditItemModal(element)"
                                            />
                                            <UButton
                                                v-if="can('menus.delete')"
                                                icon="i-lucide-trash"
                                                color="error"
                                                variant="ghost"
                                                size="xs"
                                                @click="confirmDeleteItem(element)"
                                            />
                                        </div>
                                    </div>

                                    <!-- Children -->
                                    <div v-if="element.children && element.children.length > 0" class="ml-6 mt-2 space-y-2">
                                        <draggable
                                            v-model="element.children"
                                            v-bind="dragOptions"
                                            item-key="id"
                                            @end="onDragEnd"
                                        >
                                            <template #item="{ element: child }">
                                                <div class="menu-item">
                                                    <div
                                                        class="flex items-center gap-3 p-3 rounded-lg border border-default bg-default hover:bg-elevated/50 transition-colors"
                                                        :class="{ 'opacity-50': !child.is_active }"
                                                    >
                                                        <div class="drag-handle cursor-grab active:cursor-grabbing">
                                                            <UIcon name="i-lucide-grip-vertical" class="size-5 text-muted" />
                                                        </div>

                                                        <UIcon name="i-lucide-corner-down-right" class="size-4 text-muted" />

                                                        <UIcon
                                                            :name="getTypeIcon(child.type)"
                                                            class="size-5 text-muted"
                                                        />

                                                        <div class="flex-1 min-w-0">
                                                            <div class="font-medium text-highlighted truncate">
                                                                {{ child.label }}
                                                            </div>
                                                            <div class="text-xs text-muted truncate">
                                                                {{ child.url || (child.type === 'category' ? 'Category Link' : '') }}
                                                            </div>
                                                        </div>

                                                        <UBadge
                                                            :color="child.is_active ? 'success' : 'neutral'"
                                                            variant="subtle"
                                                            size="xs"
                                                        >
                                                            {{ child.is_active ? 'Active' : 'Inactive' }}
                                                        </UBadge>

                                                        <div class="flex items-center gap-1">
                                                            <UButton
                                                                v-if="can('menus.edit')"
                                                                icon="i-lucide-pencil"
                                                                color="neutral"
                                                                variant="ghost"
                                                                size="xs"
                                                                @click="openEditItemModal(child)"
                                                            />
                                                            <UButton
                                                                v-if="can('menus.delete')"
                                                                icon="i-lucide-trash"
                                                                color="error"
                                                                variant="ghost"
                                                                size="xs"
                                                                @click="confirmDeleteItem(child)"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </draggable>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Menu Settings Slideover -->
        <USlideover
            v-model:open="showSettingsSlideover"
            title="Menu Settings"
            description="Update menu name, location, and status."
        >
            <template #body>
                <UForm
                    :state="settingsForm"
                    class="space-y-4"
                    @submit="updateSettings"
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
                                    activeSettingsTab === lang.code
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                                ]"
                                @click="activeSettingsTab = lang.code"
                            >
                                <span class="flex items-center gap-2">
                                    {{ lang.native_name }}
                                    <span
                                        v-if="hasSettingsTranslation(lang.code)"
                                        class="size-2 rounded-full bg-success"
                                    />
                                    <span
                                        v-else
                                        class="size-2 rounded-full bg-muted/30"
                                    />
                                </span>
                            </button>
                        </nav>
                    </div>

                    <UFormField
                        :label="languages.length > 1 ? `Name (${languages.find(l => l.code === activeSettingsTab)?.native_name})` : 'Name'"
                        name="name"
                        :error="settingsForm.errors[`name.${activeSettingsTab}`] || settingsForm.errors.name"
                        required
                    >
                        <DhivehiInput
                            v-if="isSettingsDhivehi"
                            v-model="settingsForm.name[activeSettingsTab]"
                            placeholder="މެނޫގެ ނަން ލިޔުއްވާ"
                            :disabled="settingsForm.processing"
                            :default-enabled="true"
                            :show-toggle="false"
                            class="w-full"
                        />
                        <UInput
                            v-else
                            v-model="settingsForm.name[activeSettingsTab]"
                            placeholder="Menu name"
                            class="w-full"
                            :dir="isSettingsRtl ? 'rtl' : 'ltr'"
                            :disabled="settingsForm.processing"
                        />
                    </UFormField>

                    <UFormField
                        v-if="activeSettingsTab === languages[0]?.code"
                        label="Location"
                        name="location"
                        :error="settingsForm.errors.location"
                    >
                        <UInput
                            v-model="settingsForm.location"
                            placeholder="header"
                            class="w-full"
                            disabled
                        />
                    </UFormField>

                    <UFormField
                        :label="languages.length > 1 ? `Description (${languages.find(l => l.code === activeSettingsTab)?.native_name})` : 'Description'"
                        name="description"
                    >
                        <DhivehiInput
                            v-if="isSettingsDhivehi"
                            v-model="settingsForm.description[activeSettingsTab]"
                            type="textarea"
                            placeholder="ތަފްޞީލް ލިޔުއްވާ"
                            :rows="3"
                            :disabled="settingsForm.processing"
                            :default-enabled="true"
                            :show-toggle="false"
                            class="w-full"
                        />
                        <UTextarea
                            v-else
                            v-model="settingsForm.description[activeSettingsTab]"
                            placeholder="Menu description"
                            :rows="3"
                            class="w-full"
                            :dir="isSettingsRtl ? 'rtl' : 'ltr'"
                            :disabled="settingsForm.processing"
                        />
                    </UFormField>

                    <UFormField
                        v-if="activeSettingsTab === languages[0]?.code"
                        label="Status"
                        name="is_active"
                    >
                        <USwitch v-model="settingsForm.is_active" :disabled="settingsForm.processing">
                            <template #label>
                                <span class="text-sm">{{ settingsForm.is_active ? 'Active' : 'Inactive' }}</span>
                            </template>
                        </USwitch>
                    </UFormField>

                    <div class="flex justify-end gap-2 pt-6">
                        <UButton
                            color="neutral"
                            variant="ghost"
                            :disabled="settingsForm.processing"
                            @click="showSettingsSlideover = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            type="submit"
                            :loading="settingsForm.processing"
                        >
                            Save Settings
                        </UButton>
                    </div>
                </UForm>
            </template>
        </USlideover>

        <!-- Add Item Modal -->
        <UModal v-model:open="showAddItemModal">
            <template #content>
                <UCard>
                    <template #header>
                        <h3 class="text-lg font-semibold text-highlighted">Add Menu Item</h3>
                    </template>

                    <UForm
                        :state="itemForm"
                        class="space-y-4"
                        @submit="addItem"
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
                                        activeItemTab === lang.code
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                                    ]"
                                    @click="activeItemTab = lang.code"
                                >
                                    <span class="flex items-center gap-2">
                                        {{ lang.native_name }}
                                        <span
                                            v-if="hasTranslation(lang.code)"
                                            class="size-2 rounded-full bg-success"
                                        />
                                        <span
                                            v-else
                                            class="size-2 rounded-full bg-muted/30"
                                        />
                                    </span>
                                </button>
                            </nav>
                        </div>

                        <UFormField
                            :label="languages.length > 1 ? `Label (${languages.find(l => l.code === activeItemTab)?.native_name})` : 'Label'"
                            name="label"
                            :error="itemForm.errors[`label.${activeItemTab}`] || itemForm.errors.label"
                            required
                        >
                            <DhivehiInput
                                v-if="isDhivehi"
                                v-model="itemForm.label[activeItemTab]"
                                placeholder="ލޭބަލް ލިޔުއްވާ"
                                :disabled="itemForm.processing"
                                :default-enabled="true"
                                :show-toggle="false"
                                class="w-full"
                            />
                            <UInput
                                v-else
                                v-model="itemForm.label[activeItemTab]"
                                placeholder="Menu item label"
                                class="w-full"
                                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code"
                            label="Type"
                            name="type"
                            required
                        >
                            <USelectMenu
                                v-model="itemForm.type"
                                :items="typeOptions"
                                value-key="value"
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code && (itemForm.type === 'custom' || itemForm.type === 'external')"
                            label="URL"
                            name="url"
                            :error="itemForm.errors.url"
                        >
                            <UInput
                                v-model="itemForm.url"
                                :placeholder="itemForm.type === 'external' ? 'https://example.com' : '/page'"
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code && itemForm.type === 'category'"
                            label="Category"
                            name="linkable_id"
                        >
                            <USelectMenu
                                v-model="itemForm.linkable_id"
                                :items="categoryOptions"
                                value-key="value"
                                placeholder="Select category..."
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code"
                            label="Open in"
                            name="target"
                        >
                            <USelectMenu
                                v-model="itemForm.target"
                                :items="[
                                    { label: 'Same window', value: '_self' },
                                    { label: 'New window', value: '_blank' },
                                ]"
                                value-key="value"
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code && parentOptions.length > 1"
                            label="Parent Item"
                            name="parent_id"
                        >
                            <USelectMenu
                                v-model="itemForm.parent_id"
                                :items="parentOptions"
                                value-key="value"
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code"
                            label="Status"
                            name="is_active"
                        >
                            <USwitch v-model="itemForm.is_active" :disabled="itemForm.processing">
                                <template #label>
                                    <span class="text-sm">{{ itemForm.is_active ? 'Active' : 'Inactive' }}</span>
                                </template>
                            </USwitch>
                        </UFormField>

                        <div class="flex justify-end gap-2 pt-4">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                :disabled="itemForm.processing"
                                @click="showAddItemModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :loading="itemForm.processing"
                            >
                                Add Item
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>

        <!-- Edit Item Modal -->
        <UModal v-model:open="showEditItemModal">
            <template #content>
                <UCard>
                    <template #header>
                        <h3 class="text-lg font-semibold text-highlighted">Edit Menu Item</h3>
                    </template>

                    <UForm
                        :state="itemForm"
                        class="space-y-4"
                        @submit="updateItem"
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
                                        activeItemTab === lang.code
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-muted hover:text-highlighted hover:border-muted',
                                    ]"
                                    @click="activeItemTab = lang.code"
                                >
                                    <span class="flex items-center gap-2">
                                        {{ lang.native_name }}
                                        <span
                                            v-if="hasTranslation(lang.code)"
                                            class="size-2 rounded-full bg-success"
                                        />
                                        <span
                                            v-else
                                            class="size-2 rounded-full bg-muted/30"
                                        />
                                    </span>
                                </button>
                            </nav>
                        </div>

                        <UFormField
                            :label="languages.length > 1 ? `Label (${languages.find(l => l.code === activeItemTab)?.native_name})` : 'Label'"
                            name="label"
                            :error="itemForm.errors[`label.${activeItemTab}`] || itemForm.errors.label"
                            required
                        >
                            <DhivehiInput
                                v-if="isDhivehi"
                                v-model="itemForm.label[activeItemTab]"
                                placeholder="ލޭބަލް ލިޔުއްވާ"
                                :disabled="itemForm.processing"
                                :default-enabled="true"
                                :show-toggle="false"
                                class="w-full"
                            />
                            <UInput
                                v-else
                                v-model="itemForm.label[activeItemTab]"
                                placeholder="Menu item label"
                                class="w-full"
                                :dir="isCurrentRtl ? 'rtl' : 'ltr'"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code"
                            label="Type"
                            name="type"
                            required
                        >
                            <USelectMenu
                                v-model="itemForm.type"
                                :items="typeOptions"
                                value-key="value"
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code && (itemForm.type === 'custom' || itemForm.type === 'external')"
                            label="URL"
                            name="url"
                            :error="itemForm.errors.url"
                        >
                            <UInput
                                v-model="itemForm.url"
                                :placeholder="itemForm.type === 'external' ? 'https://example.com' : '/page'"
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code && itemForm.type === 'category'"
                            label="Category"
                            name="linkable_id"
                        >
                            <USelectMenu
                                v-model="itemForm.linkable_id"
                                :items="categoryOptions"
                                value-key="value"
                                placeholder="Select category..."
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code"
                            label="Open in"
                            name="target"
                        >
                            <USelectMenu
                                v-model="itemForm.target"
                                :items="[
                                    { label: 'Same window', value: '_self' },
                                    { label: 'New window', value: '_blank' },
                                ]"
                                value-key="value"
                                class="w-full"
                                :disabled="itemForm.processing"
                            />
                        </UFormField>

                        <UFormField
                            v-if="activeItemTab === languages[0]?.code"
                            label="Status"
                            name="is_active"
                        >
                            <USwitch v-model="itemForm.is_active" :disabled="itemForm.processing">
                                <template #label>
                                    <span class="text-sm">{{ itemForm.is_active ? 'Active' : 'Inactive' }}</span>
                                </template>
                            </USwitch>
                        </UFormField>

                        <div class="flex justify-end gap-2 pt-4">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                :disabled="itemForm.processing"
                                @click="showEditItemModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                type="submit"
                                :loading="itemForm.processing"
                            >
                                Save Changes
                            </UButton>
                        </div>
                    </UForm>
                </UCard>
            </template>
        </UModal>

        <!-- Delete Item Confirmation Modal -->
        <UModal v-model:open="deleteItemModalOpen">
            <template #content>
                <UCard :ui="{ body: 'p-6' }">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center size-12 rounded-full bg-error/10 shrink-0">
                            <UIcon name="i-lucide-alert-triangle" class="size-6 text-error" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-highlighted">Delete Menu Item</h3>
                            <p class="mt-2 text-sm text-muted">
                                Are you sure you want to delete <strong class="text-highlighted">{{ itemToDelete?.label }}</strong>?
                            </p>
                            <p v-if="itemToDelete?.children && itemToDelete.children.length > 0" class="mt-1 text-sm text-warning">
                                Child items will be moved to the parent level.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <UButton
                            color="neutral"
                            variant="outline"
                            @click="deleteItemModalOpen = false"
                        >
                            Cancel
                        </UButton>
                        <UButton
                            color="error"
                            @click="deleteItem"
                        >
                            Delete
                        </UButton>
                    </div>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>

<style scoped>
.ghost {
    opacity: 0.5;
    background: var(--color-primary-50);
    border-color: var(--color-primary-500);
}

.menu-item {
    margin-bottom: 0.5rem;
}
</style>
