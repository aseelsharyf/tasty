<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { useCmsPath } from '../../composables/useCmsPath';
import type { NavigationMenuItem } from '@nuxt/ui';

interface TargetProgress {
    current: number;
    target: number;
    percentage: number;
    remaining: number;
}

interface Target {
    id: number;
    target_type: string;
    target_type_label: string;
    target_type_icon: string;
    target_count: number;
    category_id: number | null;
    category_name: string | null;
    is_assigned: boolean;
    assigned_by_name: string | null;
    notes: string | null;
    progress: TargetProgress;
}

interface Writer {
    id: number;
    uuid: string;
    name: string;
    email: string;
    avatar_url: string | null;
    targets: Target[];
}

interface Category {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    writers: Writer[];
    categories: Category[];
    targetTypes: Record<string, string>;
    periodType: string;
    periodLabel: string;
    isAdmin: boolean;
}>();

const { cmsPath } = useCmsPath();

// Period navigation
const periodLinks = computed<NavigationMenuItem[][]>(() => [[
    {
        label: 'Weekly',
        icon: 'i-lucide-calendar-days',
        to: cmsPath('/targets?period=weekly'),
        active: props.periodType === 'weekly',
        onSelect: () => changePeriod('weekly'),
    },
    {
        label: 'Monthly',
        icon: 'i-lucide-calendar',
        to: cmsPath('/targets?period=monthly'),
        active: props.periodType === 'monthly',
        onSelect: () => changePeriod('monthly'),
    },
    {
        label: 'Yearly',
        icon: 'i-lucide-calendar-range',
        to: cmsPath('/targets?period=yearly'),
        active: props.periodType === 'yearly',
        onSelect: () => changePeriod('yearly'),
    },
]]);

function changePeriod(value: string) {
    router.get(cmsPath('/targets'), { period: value }, { preserveState: true });
}

// Writers with targets (filter out those without targets)
const writersWithTargets = computed(() => {
    return props.writers.filter(w => w.targets.length > 0);
});

// Writers without targets
const writersWithoutTargets = computed(() => {
    return props.writers.filter(w => w.targets.length === 0);
});

// Selected writer for adding target
const selectedWriterForTarget = ref<Writer | null>(null);

// Assign target modal
const showAssignModal = ref(false);
const assignForm = ref({
    period_type: props.periodType,
    target_type: 'post',
    target_count: 10,
    category_id: null as number | null,
    notes: '',
});
const savingAssignment = ref(false);
const editingTarget = ref<Target | null>(null);

function openAssignModal(writer: Writer, existingTarget?: Target) {
    selectedWriterForTarget.value = writer;
    editingTarget.value = existingTarget || null;
    assignForm.value = {
        period_type: props.periodType,
        target_type: existingTarget?.target_type || 'post',
        target_count: existingTarget?.target_count || 10,
        category_id: existingTarget?.category_id || null,
        notes: existingTarget?.notes || '',
    };
    showAssignModal.value = true;
}

async function submitAssignment() {
    if (!selectedWriterForTarget.value) return;

    savingAssignment.value = true;
    try {
        await router.post(cmsPath(`/targets/assign/${selectedWriterForTarget.value.uuid}`), assignForm.value, {
            preserveScroll: true,
            onSuccess: () => {
                showAssignModal.value = false;
            },
        });
    } finally {
        savingAssignment.value = false;
    }
}

// Delete target modal
const showDeleteModal = ref(false);
const targetToDelete = ref<{ target: Target; writer: Writer } | null>(null);
const deleting = ref(false);

function openDeleteModal(target: Target, writer: Writer) {
    targetToDelete.value = { target, writer };
    showDeleteModal.value = true;
}

async function confirmDelete() {
    if (!targetToDelete.value) return;

    deleting.value = true;
    try {
        await router.delete(cmsPath(`/targets/${targetToDelete.value.target.id}`), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                targetToDelete.value = null;
            },
        });
    } finally {
        deleting.value = false;
    }
}

// Progress color
function getProgressColor(percentage: number): string {
    if (percentage >= 100) return 'success';
    if (percentage >= 75) return 'primary';
    if (percentage >= 50) return 'warning';
    return 'error';
}

// Category options for select
const categoryOptions = computed(() => [
    { label: 'Overall (All Categories)', value: null },
    ...props.categories.map(c => ({ label: c.name, value: c.id })),
]);

// Period options for modal
const periodOptions = [
    { label: 'Weekly', value: 'weekly' },
    { label: 'Monthly', value: 'monthly' },
    { label: 'Yearly', value: 'yearly' },
];

// Target type options
const targetTypeOptions = computed(() =>
    Object.entries(props.targetTypes).map(([value, label]) => ({
        label,
        value,
        icon: getTargetTypeIcon(value),
    }))
);

// Target type icons
function getTargetTypeIcon(type: string): string {
    const icons: Record<string, string> = {
        all: 'i-lucide-layers',
        post: 'i-lucide-file-text',
        image: 'i-lucide-image',
        audio: 'i-lucide-music',
        video: 'i-lucide-video',
    };
    return icons[type] || 'i-lucide-target';
}

// Writer options for adding new target
const writerOptions = computed(() => {
    return writersWithoutTargets.value.map(w => ({
        id: w.id,
        uuid: w.uuid,
        label: w.name,
        suffix: w.email,
        avatar: w.avatar_url,
        writer: w,
    }));
});

// Add target to new writer
const showAddWriterModal = ref(false);
const newWriterSearch = ref('');

function onSelectNewWriter(option: typeof writerOptions.value[number] | null) {
    if (option) {
        showAddWriterModal.value = false;
        openAssignModal(option.writer);
    }
}
</script>

<template>
    <Head title="Writer Targets" />

    <DashboardLayout>
        <UDashboardPanel id="targets-admin">
            <template #header>
                <UDashboardNavbar title="Writer Targets">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <UButton
                            icon="i-lucide-plus"
                            label="Add Target"
                            @click="showAddWriterModal = true"
                        />
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="periodLinks" highlight class="-mx-1 flex-1" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <div class="max-w-5xl mx-auto space-y-6">
                    <!-- Header -->
                    <div class="text-center">
                        <h1 class="text-2xl font-bold text-highlighted">{{ periodLabel }} Targets</h1>
                        <p class="text-muted">{{ writersWithTargets.length }} writers with targets</p>
                    </div>

                    <!-- Writers with Targets -->
                    <div v-if="writersWithTargets.length === 0" class="flex flex-col items-center justify-center py-16">
                        <UIcon name="i-lucide-target" class="size-16 text-muted mb-4" />
                        <h3 class="text-lg font-medium text-highlighted mb-2">No targets set</h3>
                        <p class="text-muted mb-6">Start by assigning targets to your writers</p>
                        <UButton
                            icon="i-lucide-plus"
                            label="Add First Target"
                            @click="showAddWriterModal = true"
                        />
                    </div>

                    <div v-else class="space-y-4">
                        <UPageCard
                            v-for="writer in writersWithTargets"
                            :key="writer.id"
                            variant="outline"
                            :ui="{ body: 'p-4 sm:p-5' }"
                        >
                            <!-- Writer Header -->
                            <div class="flex items-start gap-4">
                                <UAvatar
                                    v-if="writer.avatar_url"
                                    :src="writer.avatar_url"
                                    :alt="writer.name"
                                    size="lg"
                                />
                                <div v-else class="flex items-center justify-center size-12 rounded-full bg-primary/10 text-primary text-lg font-medium shrink-0">
                                    {{ writer.name.charAt(0).toUpperCase() }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-semibold text-highlighted truncate">{{ writer.name }}</h3>
                                        <span class="text-xs text-muted hidden sm:inline">{{ writer.email }}</span>
                                    </div>

                                    <!-- Targets Grid -->
                                    <div class="space-y-3 mt-3">
                                        <div
                                            v-for="target in writer.targets"
                                            :key="target.id"
                                            class="flex items-center gap-4"
                                        >
                                            <div class="flex items-center gap-2 w-40 shrink-0">
                                                <UIcon :name="target.target_type_icon" class="size-4 text-primary" />
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium">{{ target.target_type_label }}</span>
                                                    <span v-if="target.category_name" class="text-xs text-muted truncate">
                                                        {{ target.category_name }}
                                                    </span>
                                                    <span v-else class="text-xs text-muted">Overall</span>
                                                </div>
                                            </div>
                                            <div class="flex-1 flex items-center gap-3">
                                                <div class="flex-1 max-w-xs">
                                                    <div class="h-2 bg-muted/20 rounded-full overflow-hidden">
                                                        <div
                                                            class="h-full rounded-full transition-all"
                                                            :class="`bg-${getProgressColor(target.progress.percentage)}`"
                                                            :style="{ width: `${target.progress.percentage}%` }"
                                                        />
                                                    </div>
                                                </div>
                                                <span class="text-sm font-medium whitespace-nowrap w-20">
                                                    {{ target.progress.current }}/{{ target.progress.target }}
                                                </span>
                                                <UBadge
                                                    v-if="target.is_assigned"
                                                    color="info"
                                                    variant="subtle"
                                                    size="xs"
                                                    class="hidden sm:flex"
                                                >
                                                    {{ target.assigned_by_name }}
                                                </UBadge>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <UButton
                                                    size="xs"
                                                    variant="ghost"
                                                    color="neutral"
                                                    icon="i-lucide-edit"
                                                    @click="openAssignModal(writer, target)"
                                                />
                                                <UButton
                                                    size="xs"
                                                    variant="ghost"
                                                    color="error"
                                                    icon="i-lucide-trash-2"
                                                    @click="openDeleteModal(target, writer)"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Target Button -->
                                <UButton
                                    variant="ghost"
                                    color="neutral"
                                    icon="i-lucide-plus"
                                    size="sm"
                                    class="shrink-0"
                                    @click="openAssignModal(writer)"
                                />
                            </div>
                        </UPageCard>
                    </div>

                    <!-- Writers Without Targets (collapsed section) -->
                    <UAccordion
                        v-if="writersWithoutTargets.length > 0"
                        :items="[{
                            label: `Writers without targets (${writersWithoutTargets.length})`,
                            icon: 'i-lucide-users',
                            slot: 'no-targets'
                        }]"
                        :ui="{ item: { body: 'p-0' } }"
                    >
                        <template #no-targets>
                            <div class="divide-y divide-default">
                                <div
                                    v-for="writer in writersWithoutTargets"
                                    :key="writer.id"
                                    class="flex items-center gap-3 p-3 hover:bg-elevated/50"
                                >
                                    <UAvatar
                                        v-if="writer.avatar_url"
                                        :src="writer.avatar_url"
                                        :alt="writer.name"
                                        size="sm"
                                    />
                                    <div v-else class="flex items-center justify-center size-8 rounded-full bg-muted/20 text-muted text-xs font-medium">
                                        {{ writer.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium truncate">{{ writer.name }}</p>
                                        <p class="text-xs text-muted truncate">{{ writer.email }}</p>
                                    </div>
                                    <UButton
                                        size="xs"
                                        variant="soft"
                                        icon="i-lucide-plus"
                                        @click="openAssignModal(writer)"
                                    >
                                        Add Target
                                    </UButton>
                                </div>
                            </div>
                        </template>
                    </UAccordion>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Add Target to New Writer Slideover -->
        <USlideover
            v-model:open="showAddWriterModal"
            title="Add Target"
            description="Select a writer to assign a target"
        >
            <template #body>
                <div class="space-y-4">
                    <UFormField label="Select Writer">
                        <UInputMenu
                            v-model:query="newWriterSearch"
                            :items="writerOptions"
                            value-key="uuid"
                            placeholder="Search by name or email..."
                            icon="i-lucide-search"
                            class="w-full"
                            @update:model-value="onSelectNewWriter"
                        >
                            <template #item="{ item }">
                                <div class="flex items-center gap-3 w-full">
                                    <UAvatar
                                        v-if="item.avatar"
                                        :src="item.avatar"
                                        :alt="item.label"
                                        size="sm"
                                    />
                                    <div v-else class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                        {{ item.label.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium truncate">{{ item.label }}</p>
                                        <p class="text-xs text-muted truncate">{{ item.suffix }}</p>
                                    </div>
                                </div>
                            </template>
                        </UInputMenu>
                    </UFormField>

                    <p v-if="writerOptions.length === 0" class="text-sm text-muted text-center py-4">
                        All writers already have targets for this period.
                    </p>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end">
                    <UButton
                        color="neutral"
                        variant="ghost"
                        @click="showAddWriterModal = false"
                    >
                        Cancel
                    </UButton>
                </div>
            </template>
        </USlideover>

        <!-- Assign Target Slideover -->
        <USlideover
            v-model:open="showAssignModal"
            :title="editingTarget ? 'Edit Target' : 'Assign Target'"
            :description="selectedWriterForTarget ? `Set target for ${selectedWriterForTarget.name}` : ''"
        >
            <template #body>
                <div class="space-y-4">
                    <UFormField label="Content Type">
                        <USelectMenu
                            v-model="assignForm.target_type"
                            :items="targetTypeOptions"
                            :icon="getTargetTypeIcon(assignForm.target_type)"
                            value-key="value"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Category" help="Leave as 'Overall' to set a target across all categories">
                        <USelectMenu
                            v-model="assignForm.category_id"
                            :items="categoryOptions"
                            value-key="value"
                            placeholder="Select category..."
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Period">
                        <USelectMenu
                            v-model="assignForm.period_type"
                            :items="periodOptions"
                            value-key="value"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Target Count">
                        <UInput
                            v-model.number="assignForm.target_count"
                            type="number"
                            min="1"
                            max="1000"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Notes (optional)">
                        <UTextarea
                            v-model="assignForm.notes"
                            placeholder="Add any notes for the writer..."
                            :rows="3"
                            class="w-full"
                        />
                    </UFormField>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <UButton
                        color="neutral"
                        variant="ghost"
                        @click="showAssignModal = false"
                    >
                        Cancel
                    </UButton>
                    <UButton
                        :loading="savingAssignment"
                        @click="submitAssignment"
                    >
                        {{ editingTarget ? 'Update' : 'Assign' }} Target
                    </UButton>
                </div>
            </template>
        </USlideover>

        <!-- Delete Confirmation Modal -->
        <UModal v-model:open="showDeleteModal">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <UIcon name="i-lucide-trash-2" class="size-5 text-error" />
                            <h3 class="font-semibold">Delete Target</h3>
                        </div>
                    </template>

                    <p class="text-muted">
                        Are you sure you want to remove the
                        <span class="font-medium text-highlighted">
                            {{ targetToDelete?.target.target_type_label }}
                        </span>
                        target
                        <template v-if="targetToDelete?.target.category_name">
                            (<span class="text-highlighted">{{ targetToDelete?.target.category_name }}</span>)
                        </template>
                        for
                        <span class="font-medium text-highlighted">{{ targetToDelete?.writer.name }}</span>?
                    </p>
                    <p class="text-sm text-muted mt-2">
                        This action cannot be undone.
                    </p>

                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                @click="showDeleteModal = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                color="error"
                                :loading="deleting"
                                @click="confirmDelete"
                            >
                                Delete Target
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
