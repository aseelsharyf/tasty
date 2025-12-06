<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';

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
    period_type: string;
    target_count: number;
    category_id: number | null;
    category_name: string | null;
    is_assigned: boolean;
    assigned_by_name: string | null;
    notes: string | null;
    can_edit: boolean;
    days_remaining: number;
    progress: TargetProgress;
    display_label: string;
}

interface Stats {
    total_posts: number;
    published_this_period: number;
    in_review: number;
    drafts: number;
}

interface Category {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    targets: Target[];
    stats: Stats;
    categories: Category[];
    targetTypes: Record<string, string>;
    periodType: string;
    periodLabel: string;
    isAdmin: boolean;
}>();

// Period selector
const selectedPeriod = ref(props.periodType);
const periodOptions = [
    { label: 'Weekly', value: 'weekly' },
    { label: 'Monthly', value: 'monthly' },
    { label: 'Yearly', value: 'yearly' },
];

function changePeriod(value: string) {
    router.get('/cms/targets', { period: value }, { preserveState: true });
}

// Add/Edit target modal
const showTargetModal = ref(false);
const editingTarget = ref<Target | null>(null);
const targetForm = ref({
    period_type: props.periodType,
    target_type: 'post',
    target_count: 10,
    category_id: null as number | null,
});
const savingTarget = ref(false);

function openAddModal() {
    editingTarget.value = null;
    targetForm.value = {
        period_type: props.periodType,
        target_type: 'post',
        target_count: 10,
        category_id: null,
    };
    showTargetModal.value = true;
}

function openEditModal(target: Target) {
    if (!target.can_edit) return;

    editingTarget.value = target;
    targetForm.value = {
        period_type: target.period_type,
        target_type: target.target_type,
        target_count: target.target_count,
        category_id: target.category_id,
    };
    showTargetModal.value = true;
}

async function saveTarget() {
    savingTarget.value = true;
    try {
        if (editingTarget.value) {
            // Update existing
            await router.put(`/cms/targets/${editingTarget.value.id}`, {
                target_count: targetForm.value.target_count,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    showTargetModal.value = false;
                },
            });
        } else {
            // Create new
            await router.post('/cms/targets', targetForm.value, {
                preserveScroll: true,
                onSuccess: () => {
                    showTargetModal.value = false;
                },
            });
        }
    } finally {
        savingTarget.value = false;
    }
}

// Delete target modal
const showDeleteModal = ref(false);
const targetToDelete = ref<Target | null>(null);
const deleting = ref(false);

function openDeleteModal(target: Target) {
    if (!target.can_edit) return;
    targetToDelete.value = target;
    showDeleteModal.value = true;
}

async function confirmDelete() {
    if (!targetToDelete.value) return;

    deleting.value = true;
    try {
        await router.delete(`/cms/targets/${targetToDelete.value.id}`, {
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
</script>

<template>
    <Head title="My Targets" />

    <DashboardLayout>
        <UDashboardPanel id="my-targets">
            <template #header>
                <UDashboardNavbar title="My Targets">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <USelectMenu
                            v-model="selectedPeriod"
                            :items="periodOptions"
                            value-key="value"
                            class="w-32"
                            @update:model-value="changePeriod"
                        />
                        <UButton
                            icon="i-lucide-plus"
                            label="Add Target"
                            @click="openAddModal"
                        />
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- Header -->
                    <div>
                        <h1 class="text-2xl font-bold text-highlighted">{{ periodLabel }} Targets</h1>
                        <p class="text-muted">Track your article writing goals</p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-success">{{ stats.published_this_period }}</p>
                                <p class="text-xs text-muted">Published</p>
                            </div>
                        </UPageCard>
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-warning">{{ stats.in_review }}</p>
                                <p class="text-xs text-muted">In Review</p>
                            </div>
                        </UPageCard>
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-muted">{{ stats.drafts }}</p>
                                <p class="text-xs text-muted">Drafts</p>
                            </div>
                        </UPageCard>
                        <UPageCard variant="outline" :ui="{ body: 'p-4' }">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-primary">{{ stats.total_posts }}</p>
                                <p class="text-xs text-muted">Total</p>
                            </div>
                        </UPageCard>
                    </div>

                    <!-- My Targets -->
                    <UPageCard
                        title="My Targets"
                        :description="`${targets.length} active targets for this ${periodType} period`"
                        variant="outline"
                    >
                        <div v-if="targets.length === 0" class="flex flex-col items-center py-8">
                            <UIcon name="i-lucide-target" class="size-16 text-muted mb-4" />
                            <p class="text-muted mb-4">No targets set for this period</p>
                            <UButton
                                label="Set Your First Target"
                                icon="i-lucide-plus"
                                @click="openAddModal"
                            />
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="target in targets"
                                :key="target.id"
                                class="p-4 rounded-lg border border-default"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center size-10 rounded-lg bg-primary/10">
                                            <UIcon :name="target.target_type_icon" class="size-5 text-primary" />
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-highlighted">{{ target.target_type_label }}</h4>
                                            <p class="text-xs text-muted">
                                                {{ target.category_name || 'All categories' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <UBadge
                                            v-if="target.is_assigned"
                                            color="info"
                                            variant="subtle"
                                            size="xs"
                                        >
                                            <template #leading>
                                                <UIcon name="i-lucide-user-check" class="size-3" />
                                            </template>
                                            {{ target.assigned_by_name }}
                                        </UBadge>
                                        <UButton
                                            v-if="target.can_edit"
                                            size="xs"
                                            variant="ghost"
                                            color="neutral"
                                            icon="i-lucide-edit"
                                            @click="openEditModal(target)"
                                        />
                                        <UButton
                                            v-if="target.can_edit"
                                            size="xs"
                                            variant="ghost"
                                            color="error"
                                            icon="i-lucide-trash-2"
                                            @click="openDeleteModal(target)"
                                        />
                                    </div>
                                </div>

                                <!-- Progress -->
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <div class="h-3 bg-muted/20 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="`bg-${getProgressColor(target.progress.percentage)}`"
                                                :style="{ width: `${target.progress.percentage}%` }"
                                            />
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-lg font-bold text-highlighted">
                                            {{ target.progress.current }}/{{ target.progress.target }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between mt-2 text-xs text-muted">
                                    <span>{{ target.progress.percentage }}% complete • {{ target.progress.remaining }} to go</span>
                                    <span>{{ target.days_remaining }} days left</span>
                                </div>

                                <p v-if="target.notes" class="mt-3 text-sm text-muted italic border-l-2 border-primary/30 pl-3">
                                    {{ target.notes }}
                                </p>
                            </div>

                            <div class="pt-2">
                                <UButton
                                    variant="ghost"
                                    color="neutral"
                                    size="sm"
                                    icon="i-lucide-plus"
                                    block
                                    @click="openAddModal"
                                >
                                    Add Another Target
                                </UButton>
                            </div>
                        </div>
                    </UPageCard>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Add/Edit Target Slideover -->
        <USlideover
            v-model:open="showTargetModal"
            :title="editingTarget ? 'Edit Target' : 'Set New Target'"
            :description="editingTarget ? 'Update your target count' : 'Create a new goal to track your progress'"
        >
            <template #body>
                <div class="space-y-4">
                    <UFormField v-if="!editingTarget" label="Content Type">
                        <USelectMenu
                            v-model="targetForm.target_type"
                            :items="targetTypeOptions"
                            :icon="getTargetTypeIcon(targetForm.target_type)"
                            value-key="value"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField v-if="!editingTarget" label="Category" help="Select 'Overall' to set a target across all categories">
                        <USelectMenu
                            v-model="targetForm.category_id"
                            :items="categoryOptions"
                            value-key="value"
                            placeholder="Select category..."
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField v-if="!editingTarget" label="Period">
                        <USelectMenu
                            v-model="targetForm.period_type"
                            :items="periodOptions"
                            value-key="value"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Target Count">
                        <UInput
                            v-model.number="targetForm.target_count"
                            type="number"
                            min="1"
                            max="1000"
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
                        @click="showTargetModal = false"
                    >
                        Cancel
                    </UButton>
                    <UButton
                        :loading="savingTarget"
                        @click="saveTarget"
                    >
                        {{ editingTarget ? 'Update' : 'Set Target' }}
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
                        Are you sure you want to remove your
                        <span class="font-medium text-highlighted">
                            {{ targetToDelete?.target_type_label }}
                        </span>
                        target
                        <template v-if="targetToDelete?.category_name">
                            for <span class="text-highlighted">{{ targetToDelete?.category_name }}</span>
                        </template>?
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
