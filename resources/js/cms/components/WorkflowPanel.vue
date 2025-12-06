<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

interface WorkflowState {
    key: string;
    label: string;
    color: string;
    icon: string;
}

interface Transition {
    from: string;
    to: string;
    roles: string[];
    label: string;
}

interface Version {
    id: number;
    uuid: string;
    version_number: number;
    workflow_status: string;
    is_active: boolean;
    version_note: string | null;
    created_by: {
        id: number;
        name: string;
        avatar_url: string | null;
    } | null;
    created_at: string;
}

const props = defineProps<{
    contentType: string;
    contentUuid: string;
    currentVersionUuid?: string | null;
    workflowStatus: string;
    workflowConfig: {
        name: string;
        states: WorkflowState[];
        transitions: Transition[];
        publish_roles: string[];
    };
}>();

const emit = defineEmits<{
    (e: 'transition', status: string): void;
    (e: 'refresh'): void;
}>();

const loading = ref(false);
const availableTransitions = ref<Transition[]>([]);
const transitionModalOpen = ref(false);
const selectedTransition = ref<Transition | null>(null);
const transitionComment = ref('');
const submittingTransition = ref(false);

// Get current state config
const currentState = computed(() => {
    return props.workflowConfig.states.find(s => s.key === props.workflowStatus) || {
        key: props.workflowStatus,
        label: props.workflowStatus,
        color: 'neutral',
        icon: 'i-lucide-circle',
    };
});

// Map color to Nuxt UI color
const stateColor = computed(() => {
    const colorMap: Record<string, string> = {
        neutral: 'neutral',
        warning: 'warning',
        info: 'info',
        success: 'success',
        error: 'error',
        primary: 'primary',
        gray: 'neutral',
        yellow: 'warning',
        blue: 'info',
        green: 'success',
        red: 'error',
        emerald: 'primary',
    };
    return colorMap[currentState.value.color] || 'neutral';
});

// Filter available transitions based on current status
const filteredTransitions = computed(() => {
    return props.workflowConfig.transitions.filter(t => t.from === props.workflowStatus);
});

// Fetch available transitions from API
async function fetchAvailableTransitions() {
    if (!props.currentVersionUuid) return;

    loading.value = true;
    try {
        const response = await axios.get(`/cms/workflow/versions/${props.currentVersionUuid}/transitions`);
        availableTransitions.value = response.data.transitions;
    } catch (error) {
        console.error('Failed to fetch transitions:', error);
        // Fall back to config-based transitions
        availableTransitions.value = filteredTransitions.value;
    } finally {
        loading.value = false;
    }
}

// Open transition modal
function openTransitionModal(transition: Transition) {
    selectedTransition.value = transition;
    transitionComment.value = '';
    transitionModalOpen.value = true;
}

// Perform transition
async function performTransition() {
    if (!props.currentVersionUuid || !selectedTransition.value) return;

    submittingTransition.value = true;
    try {
        await axios.post(`/cms/workflow/versions/${props.currentVersionUuid}/transition`, {
            to_status: selectedTransition.value.to,
            comment: transitionComment.value || null,
        });

        transitionModalOpen.value = false;
        emit('transition', selectedTransition.value.to);
        emit('refresh');
    } catch (error: any) {
        console.error('Transition failed:', error);
        alert(error.response?.data?.message || 'Transition failed');
    } finally {
        submittingTransition.value = false;
    }
}

// Get state label
function getStateLabel(key: string): string {
    const state = props.workflowConfig.states.find(s => s.key === key);
    return state?.label || key;
}

// Get state color
function getStateColor(key: string): string {
    const state = props.workflowConfig.states.find(s => s.key === key);
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

onMounted(() => {
    if (props.currentVersionUuid) {
        fetchAvailableTransitions();
    }
});
</script>

<template>
    <div class="space-y-4">
        <!-- Current Status -->
        <div>
            <div class="flex items-center gap-2 mb-3">
                <UIcon name="i-lucide-git-branch" class="size-4 text-muted" />
                <span class="text-xs font-medium text-muted uppercase tracking-wider">Workflow</span>
            </div>

            <div class="flex items-center gap-2 p-3 rounded-lg bg-elevated/50 border border-default">
                <UIcon :name="currentState.icon" :class="['size-5', `text-${stateColor}`]" />
                <div class="flex-1">
                    <p class="text-sm font-medium">{{ currentState.label }}</p>
                    <p class="text-xs text-muted">{{ workflowConfig.name }}</p>
                </div>
                <UBadge :color="stateColor" variant="subtle" size="sm">
                    {{ currentState.key }}
                </UBadge>
            </div>
        </div>

        <!-- Available Transitions -->
        <div v-if="filteredTransitions.length > 0 || availableTransitions.length > 0">
            <p class="text-xs text-muted mb-2">Actions</p>
            <div class="space-y-1.5">
                <UButton
                    v-for="transition in (availableTransitions.length > 0 ? availableTransitions : filteredTransitions)"
                    :key="`${transition.from}-${transition.to}`"
                    color="neutral"
                    variant="soft"
                    size="sm"
                    class="w-full justify-start"
                    :loading="loading"
                    @click="openTransitionModal(transition)"
                >
                    <UIcon name="i-lucide-arrow-right" class="size-3.5 mr-1" />
                    {{ transition.label }}
                    <UBadge :color="getStateColor(transition.to)" variant="subtle" size="xs" class="ml-auto">
                        {{ getStateLabel(transition.to) }}
                    </UBadge>
                </UButton>
            </div>
        </div>

        <!-- No version yet message -->
        <div v-else-if="!currentVersionId" class="text-xs text-muted text-center py-4">
            <UIcon name="i-lucide-info" class="size-4 mx-auto mb-1 opacity-50" />
            <p>Save to create first version</p>
        </div>

        <!-- Transition Modal -->
        <UModal v-model:open="transitionModalOpen">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <UIcon name="i-lucide-git-branch" class="size-5 text-primary" />
                            <h3 class="font-semibold">{{ selectedTransition?.label }}</h3>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-elevated/50">
                            <UBadge :color="getStateColor(selectedTransition?.from || '')" variant="soft">
                                {{ getStateLabel(selectedTransition?.from || '') }}
                            </UBadge>
                            <UIcon name="i-lucide-arrow-right" class="size-4 text-muted" />
                            <UBadge :color="getStateColor(selectedTransition?.to || '')" variant="soft">
                                {{ getStateLabel(selectedTransition?.to || '') }}
                            </UBadge>
                        </div>

                        <UFormField label="Comment (optional)">
                            <UTextarea
                                v-model="transitionComment"
                                placeholder="Add a note about this transition..."
                                :rows="3"
                                class="w-full"
                            />
                        </UFormField>
                    </div>

                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <UButton
                                color="neutral"
                                variant="ghost"
                                @click="transitionModalOpen = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                color="primary"
                                :loading="submittingTransition"
                                @click="performTransition"
                            >
                                {{ selectedTransition?.label }}
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>
    </div>
</template>
