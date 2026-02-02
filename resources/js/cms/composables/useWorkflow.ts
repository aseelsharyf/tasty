import { ref, computed } from 'vue';
import axios from 'axios';
import { useCmsPath } from './useCmsPath';

export interface WorkflowState {
    key: string;
    label: string;
    color: string;
    icon: string;
}

export interface WorkflowTransition {
    from: string;
    to: string;
    roles: string[];
    label: string;
}

export interface WorkflowConfig {
    name: string;
    states: WorkflowState[];
    transitions: WorkflowTransition[];
    publish_roles: string[];
}

// Color mapping for workflow states
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
};

export function useWorkflow(config: WorkflowConfig) {
    const { cmsPath } = useCmsPath();
    const loading = ref(false);

    /**
     * Get a workflow state by key
     */
    function getState(key: string): WorkflowState {
        return config.states.find(s => s.key === key) || {
            key,
            label: key,
            color: 'neutral',
            icon: 'i-lucide-circle',
        };
    }

    /**
     * Get the label for a workflow state
     */
    function getStateLabel(key: string): string {
        return getState(key).label;
    }

    /**
     * Get the color for a workflow state (mapped to UI colors)
     */
    function getStateColor(key: string): string {
        const state = getState(key);
        return colorMap[state.color] || 'neutral';
    }

    /**
     * Get the icon for a workflow state
     */
    function getStateIcon(key: string): string {
        return getState(key).icon;
    }

    /**
     * Get available transitions from a given status for a user with specific roles
     */
    function getAvailableTransitions(fromStatus: string, userRoles: string[] = []): WorkflowTransition[] {
        return config.transitions.filter(t => {
            // Must match the from status
            if (t.from !== fromStatus) return false;
            // If no user roles provided, return all transitions (for display purposes)
            if (userRoles.length === 0) return true;
            // Check if user has any of the required roles
            return t.roles.some(role => userRoles.includes(role));
        });
    }

    /**
     * Perform a workflow transition
     */
    async function transition(
        versionUuid: string,
        toStatus: string,
        comment?: string
    ): Promise<{ success: boolean; error?: string }> {
        loading.value = true;
        try {
            await axios.post(cmsPath(`/workflow/versions/${versionUuid}/transition`), {
                to_status: toStatus,
                comment: comment || null,
            });
            return { success: true };
        } catch (error: any) {
            return {
                success: false,
                error: error.response?.data?.message || 'Transition failed',
            };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Revert to a specific version
     */
    async function revertToVersion(versionUuid: string): Promise<{ success: boolean; error?: string }> {
        loading.value = true;
        try {
            await axios.post(cmsPath(`/workflow/versions/${versionUuid}/revert`));
            return { success: true };
        } catch (error: any) {
            return {
                success: false,
                error: error.response?.data?.message || 'Failed to revert',
            };
        } finally {
            loading.value = false;
        }
    }

    /**
     * Unpublish content directly (without workflow transition)
     * This handles edge cases like legacy posts or inconsistent states
     */
    async function unpublish(
        contentType: string,
        contentUuid: string
    ): Promise<{ success: boolean; error?: string }> {
        loading.value = true;
        try {
            await axios.post(cmsPath(`/workflow/${contentType}/${contentUuid}/unpublish`));
            return { success: true };
        } catch (error: any) {
            return {
                success: false,
                error: error.response?.data?.message || 'Failed to unpublish',
            };
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        getState,
        getStateLabel,
        getStateColor,
        getStateIcon,
        getAvailableTransitions,
        transition,
        revertToVersion,
        unpublish,
    };
}

/**
 * Standalone helper functions (when you don't have the full config)
 */
export function getWorkflowStateColor(color: string): string {
    return colorMap[color] || 'neutral';
}

export function formatRelativeDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 7) {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    }
    if (days > 0) return `${days}d ago`;
    if (hours > 0) return `${hours}h ago`;
    if (minutes > 0) return `${minutes}m ago`;
    return 'just now';
}

export function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}
