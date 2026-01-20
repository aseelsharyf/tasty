<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import draggable from 'vuedraggable';
import { useSettingsNav } from '../../composables/useSettingsNav';

const { mainNav: settingsNav } = useSettingsNav();

interface WorkflowState {
    key: string;
    label: string;
    color: string;
    icon: string;
}

interface WorkflowTransition {
    from: string;
    to: string;
    roles: string[];
    label: string;
}

interface WorkflowConfig {
    name: string;
    states: WorkflowState[];
    transitions: WorkflowTransition[];
    publish_roles: string[];
}

const props = defineProps<{
    workflows: Record<string, WorkflowConfig>;
    availableRoles: string[];
}>();

// Track which workflow is being edited
const activeWorkflow = ref<string>('default');

// Clone workflow for editing
const editingWorkflow = ref<WorkflowConfig | null>(null);
const isEditing = ref(false);

const colorOptions = [
    { label: 'Neutral', value: 'neutral', class: 'bg-neutral-500' },
    { label: 'Warning', value: 'warning', class: 'bg-yellow-500' },
    { label: 'Info', value: 'info', class: 'bg-blue-500' },
    { label: 'Success', value: 'success', class: 'bg-green-500' },
    { label: 'Error', value: 'error', class: 'bg-red-500' },
    { label: 'Primary', value: 'primary', class: 'bg-primary-500' },
];

const iconOptions = [
    { label: 'Edit', value: 'i-lucide-file-edit' },
    { label: 'Eye', value: 'i-lucide-eye' },
    { label: 'Spell Check', value: 'i-lucide-spell-check' },
    { label: 'Check Circle', value: 'i-lucide-check-circle' },
    { label: 'Alert Circle', value: 'i-lucide-alert-circle' },
    { label: 'Globe', value: 'i-lucide-globe' },
    { label: 'Clock', value: 'i-lucide-clock' },
    { label: 'Archive', value: 'i-lucide-archive' },
    { label: 'Send', value: 'i-lucide-send' },
    { label: 'Star', value: 'i-lucide-star' },
];

const workflowList = computed(() => {
    return Object.entries(props.workflows).map(([key, config]) => ({
        key,
        config,
        isDefault: key === 'default',
    }));
});

const currentWorkflow = computed(() => {
    return props.workflows[activeWorkflow.value] || props.workflows['default'];
});

function startEditing() {
    editingWorkflow.value = JSON.parse(JSON.stringify(currentWorkflow.value));
    isEditing.value = true;
}

function cancelEditing() {
    editingWorkflow.value = null;
    isEditing.value = false;
}

function addState() {
    if (!editingWorkflow.value) return;

    const newKey = `state_${Date.now()}`;
    editingWorkflow.value.states.push({
        key: newKey,
        label: 'New State',
        color: 'neutral',
        icon: 'i-lucide-circle',
    });
}

function removeState(index: number) {
    if (!editingWorkflow.value) return;

    const state = editingWorkflow.value.states[index];

    // Remove any transitions involving this state
    editingWorkflow.value.transitions = editingWorkflow.value.transitions.filter(
        (t) => t.from !== state.key && t.to !== state.key
    );

    editingWorkflow.value.states.splice(index, 1);
}

function addTransition() {
    if (!editingWorkflow.value || editingWorkflow.value.states.length < 2) return;

    editingWorkflow.value.transitions.push({
        from: editingWorkflow.value.states[0].key,
        to: editingWorkflow.value.states[1].key,
        roles: [],
        label: 'New Transition',
    });
}

function removeTransition(index: number) {
    if (!editingWorkflow.value) return;
    editingWorkflow.value.transitions.splice(index, 1);
}

const saving = ref(false);

async function saveWorkflow() {
    if (!editingWorkflow.value) return;

    saving.value = true;
    try {
        await router.put(
            `/cms/settings/workflows/${activeWorkflow.value}`,
            { workflow: editingWorkflow.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isEditing.value = false;
                    editingWorkflow.value = null;
                },
            }
        );
    } finally {
        saving.value = false;
    }
}

function getStateByKey(key: string): WorkflowState | undefined {
    return (editingWorkflow.value || currentWorkflow.value)?.states.find((s) => s.key === key);
}

function getStateColor(key: string): string {
    const state = getStateByKey(key);
    const colorMap: Record<string, string> = {
        neutral: 'neutral',
        warning: 'warning',
        info: 'info',
        success: 'success',
        error: 'error',
        primary: 'primary',
    };
    return colorMap[state?.color || 'neutral'] || 'neutral';
}

// Create new workflow for post type
const newWorkflowModalOpen = ref(false);
const newWorkflowPostType = ref('');
const newWorkflowName = ref('');

async function createWorkflow() {
    if (!newWorkflowPostType.value || !newWorkflowName.value) return;

    saving.value = true;
    try {
        await router.post(
            '/cms/settings/workflows',
            {
                post_type: newWorkflowPostType.value,
                name: newWorkflowName.value,
                // Clone from default workflow
                workflow: { ...props.workflows['default'], name: newWorkflowName.value },
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    newWorkflowModalOpen.value = false;
                    newWorkflowPostType.value = '';
                    newWorkflowName.value = '';
                    activeWorkflow.value = newWorkflowPostType.value;
                },
            }
        );
    } finally {
        saving.value = false;
    }
}

async function deleteWorkflow(key: string) {
    if (key === 'default') return;

    if (!confirm(`Delete the "${props.workflows[key]?.name}" workflow? Posts using this type will fall back to the default workflow.`)) {
        return;
    }

    await router.delete(`/cms/settings/workflows/${key}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (activeWorkflow.value === key) {
                activeWorkflow.value = 'default';
            }
        },
    });
}
</script>

<template>
    <Head title="Workflow Settings" />

    <DashboardLayout>
        <UDashboardPanel id="workflow-settings" :ui="{ body: 'lg:py-12' }">
            <template #header>
                <UDashboardNavbar title="Settings">
                    <template #leading>
                        <UDashboardSidebarCollapse />
                    </template>
                    <template #right>
                        <UButton
                            label="New Workflow"
                            icon="i-lucide-plus"
                            color="neutral"
                            @click="newWorkflowModalOpen = true"
                        />
                    </template>
                </UDashboardNavbar>

                <UDashboardToolbar>
                    <UNavigationMenu :items="settingsNav" highlight class="-mx-1 flex-1 overflow-x-auto" />
                </UDashboardToolbar>
            </template>

            <template #body>
                <div class="flex flex-col gap-6 w-full max-w-4xl mx-auto px-4">
                    <!-- Workflow selector tabs -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-2">
                        <UButton
                            v-for="wf in workflowList"
                            :key="wf.key"
                            :color="activeWorkflow === wf.key ? 'primary' : 'neutral'"
                            :variant="activeWorkflow === wf.key ? 'soft' : 'ghost'"
                            size="sm"
                            @click="activeWorkflow = wf.key; cancelEditing()"
                        >
                            {{ wf.config.name }}
                            <UBadge v-if="wf.isDefault" color="neutral" variant="subtle" size="xs" class="ml-1">
                                Default
                            </UBadge>
                        </UButton>
                    </div>

                    <!-- Current workflow display/edit -->
                    <UPageCard
                        :title="currentWorkflow?.name || 'Workflow'"
                        :description="activeWorkflow === 'default' ? 'Default workflow for all content types' : `Custom workflow for ${activeWorkflow} post type`"
                        variant="naked"
                        orientation="horizontal"
                    >
                        <div class="flex items-center gap-2">
                            <UButton
                                v-if="!isEditing"
                                label="Edit"
                                icon="i-lucide-edit"
                                color="neutral"
                                @click="startEditing"
                            />
                            <UButton
                                v-if="activeWorkflow !== 'default' && !isEditing"
                                label="Delete"
                                icon="i-lucide-trash"
                                color="error"
                                variant="ghost"
                                @click="deleteWorkflow(activeWorkflow)"
                            />
                            <template v-if="isEditing">
                                <UButton
                                    label="Cancel"
                                    color="neutral"
                                    variant="ghost"
                                    @click="cancelEditing"
                                />
                                <UButton
                                    label="Save"
                                    icon="i-lucide-check"
                                    :loading="saving"
                                    @click="saveWorkflow"
                                />
                            </template>
                        </div>
                    </UPageCard>

                    <!-- View mode -->
                    <template v-if="!isEditing">
                        <!-- States visualization -->
                        <UPageCard title="States" description="The stages content moves through in this workflow." variant="subtle">
                            <div class="flex flex-wrap items-center gap-2">
                                <template v-for="(state, index) in currentWorkflow?.states" :key="state.key">
                                    <div class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-elevated border border-default">
                                        <UIcon :name="state.icon" :class="`size-4 text-${getStateColor(state.key)}`" />
                                        <span class="text-sm font-medium">{{ state.label }}</span>
                                        <UBadge :color="getStateColor(state.key)" variant="subtle" size="xs">
                                            {{ state.key }}
                                        </UBadge>
                                    </div>
                                    <UIcon
                                        v-if="index < (currentWorkflow?.states.length || 0) - 1"
                                        name="i-lucide-arrow-right"
                                        class="size-4 text-muted"
                                    />
                                </template>
                            </div>
                        </UPageCard>

                        <!-- Transitions table -->
                        <UPageCard title="Transitions" description="How content moves between states and who can perform each action." variant="subtle">
                            <UTable
                                :data="currentWorkflow?.transitions || []"
                                :columns="[
                                    { key: 'label', header: 'Action' },
                                    { key: 'from', header: 'From' },
                                    { key: 'to', header: 'To' },
                                    { key: 'roles', header: 'Allowed Roles' },
                                ]"
                            >
                                <template #from-cell="{ row }">
                                    <UBadge :color="getStateColor(row.original.from)" variant="subtle" size="sm">
                                        {{ getStateByKey(row.original.from)?.label || row.original.from }}
                                    </UBadge>
                                </template>
                                <template #to-cell="{ row }">
                                    <UBadge :color="getStateColor(row.original.to)" variant="subtle" size="sm">
                                        {{ getStateByKey(row.original.to)?.label || row.original.to }}
                                    </UBadge>
                                </template>
                                <template #roles-cell="{ row }">
                                    <div class="flex flex-wrap gap-1">
                                        <UBadge
                                            v-for="role in row.original.roles"
                                            :key="role"
                                            color="neutral"
                                            variant="subtle"
                                            size="xs"
                                        >
                                            {{ role }}
                                        </UBadge>
                                    </div>
                                </template>
                            </UTable>
                        </UPageCard>

                        <!-- Publish roles -->
                        <UPageCard title="Publish Roles" description="Roles that can publish approved content." variant="subtle">
                            <div class="flex flex-wrap gap-2">
                                <UBadge
                                    v-for="role in currentWorkflow?.publish_roles"
                                    :key="role"
                                    color="success"
                                    variant="subtle"
                                >
                                    {{ role }}
                                </UBadge>
                            </div>
                        </UPageCard>
                    </template>

                    <!-- Edit mode -->
                    <template v-else-if="editingWorkflow">
                        <!-- Workflow name -->
                        <UPageCard title="Workflow Name" variant="subtle">
                            <UInput v-model="editingWorkflow.name" placeholder="Workflow name" class="max-w-sm" />
                        </UPageCard>

                        <!-- States editor -->
                        <UPageCard title="States" description="Define the stages content moves through." variant="subtle">
                            <div class="space-y-3">
                                <div
                                    v-for="(state, index) in editingWorkflow.states"
                                    :key="state.key"
                                    class="flex items-start gap-3 p-3 rounded-lg bg-elevated border border-default"
                                >
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-4 gap-3">
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">Key</label>
                                            <UInput v-model="state.key" size="sm" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">Label</label>
                                            <UInput v-model="state.label" size="sm" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">Color</label>
                                            <USelectMenu
                                                v-model="state.color"
                                                :items="colorOptions"
                                                value-key="value"
                                                size="sm"
                                            >
                                                <template #leading>
                                                    <span :class="['size-3 rounded-full', colorOptions.find(c => c.value === state.color)?.class]" />
                                                </template>
                                            </USelectMenu>
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">Icon</label>
                                            <USelectMenu
                                                v-model="state.icon"
                                                :items="iconOptions"
                                                value-key="value"
                                                size="sm"
                                            >
                                                <template #leading>
                                                    <UIcon :name="state.icon" class="size-4" />
                                                </template>
                                            </USelectMenu>
                                        </div>
                                    </div>
                                    <UButton
                                        color="error"
                                        variant="ghost"
                                        icon="i-lucide-trash"
                                        size="sm"
                                        :disabled="editingWorkflow.states.length <= 2"
                                        @click="removeState(index)"
                                    />
                                </div>

                                <UButton
                                    label="Add State"
                                    icon="i-lucide-plus"
                                    color="neutral"
                                    variant="soft"
                                    size="sm"
                                    @click="addState"
                                />
                            </div>
                        </UPageCard>

                        <!-- Transitions editor -->
                        <UPageCard title="Transitions" description="Define how content moves between states." variant="subtle">
                            <div class="space-y-3">
                                <div
                                    v-for="(transition, index) in editingWorkflow.transitions"
                                    :key="index"
                                    class="flex items-start gap-3 p-3 rounded-lg bg-elevated border border-default"
                                >
                                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-4 gap-3">
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">Label</label>
                                            <UInput v-model="transition.label" size="sm" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">From</label>
                                            <USelectMenu
                                                v-model="transition.from"
                                                :items="editingWorkflow.states.map(s => ({ label: s.label, value: s.key }))"
                                                value-key="value"
                                                size="sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">To</label>
                                            <USelectMenu
                                                v-model="transition.to"
                                                :items="editingWorkflow.states.map(s => ({ label: s.label, value: s.key }))"
                                                value-key="value"
                                                size="sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted mb-1 block">Roles</label>
                                            <USelectMenu
                                                v-model="transition.roles"
                                                :items="availableRoles.map(r => ({ label: r, value: r }))"
                                                value-key="value"
                                                multiple
                                                size="sm"
                                            />
                                        </div>
                                    </div>
                                    <UButton
                                        color="error"
                                        variant="ghost"
                                        icon="i-lucide-trash"
                                        size="sm"
                                        @click="removeTransition(index)"
                                    />
                                </div>

                                <UButton
                                    label="Add Transition"
                                    icon="i-lucide-plus"
                                    color="neutral"
                                    variant="soft"
                                    size="sm"
                                    :disabled="editingWorkflow.states.length < 2"
                                    @click="addTransition"
                                />
                            </div>
                        </UPageCard>

                        <!-- Publish roles editor -->
                        <UPageCard title="Publish Roles" description="Select which roles can publish approved content." variant="subtle">
                            <USelectMenu
                                v-model="editingWorkflow.publish_roles"
                                :items="availableRoles.map(r => ({ label: r, value: r }))"
                                value-key="value"
                                multiple
                                class="max-w-sm"
                            />
                        </UPageCard>
                    </template>
                </div>
            </template>
        </UDashboardPanel>

        <!-- New workflow modal -->
        <UModal v-model:open="newWorkflowModalOpen">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <UIcon name="i-lucide-git-branch" class="size-5 text-primary" />
                            <h3 class="font-semibold">Create Custom Workflow</h3>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <p class="text-sm text-muted">
                            Create a custom workflow for a specific post type. This will override the default workflow for that type.
                        </p>

                        <UFormField label="Post Type" required>
                            <UInput
                                v-model="newWorkflowPostType"
                                placeholder="e.g., recipe, news, review"
                            />
                        </UFormField>

                        <UFormField label="Workflow Name" required>
                            <UInput
                                v-model="newWorkflowName"
                                placeholder="e.g., Recipe Fast-Track"
                            />
                        </UFormField>
                    </div>

                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                @click="newWorkflowModalOpen = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                color="primary"
                                :disabled="!newWorkflowPostType || !newWorkflowName"
                                :loading="saving"
                                @click="createWorkflow"
                            >
                                Create Workflow
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
