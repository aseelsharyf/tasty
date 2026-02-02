<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useCmsPath } from '../composables/useCmsPath';

interface User {
    id: number;
    name: string;
    avatar_url: string | null;
}

interface Comment {
    id: number;
    uuid: string;
    content: string;
    block_id: string | null;
    type: 'general' | 'revision_request' | 'approval';
    is_resolved: boolean;
    user: User;
    resolved_by: User | null;
    resolved_at: string | null;
    created_at: string;
}

const props = defineProps<{
    versionUuid: string | null;
}>();

const emit = defineEmits<{
    (e: 'comment-added', comment: Comment): void;
    (e: 'comment-resolved', comment: Comment): void;
}>();

const { cmsPath } = useCmsPath();

const loading = ref(false);
const comments = ref<Comment[]>([]);
const newComment = ref('');
const commentType = ref<'general' | 'revision_request' | 'approval'>('general');
const submitting = ref(false);
const showResolved = ref(false);

const unresolvedCount = computed(() => comments.value.filter(c => !c.is_resolved).length);

const filteredComments = computed(() => {
    if (showResolved.value) {
        return comments.value;
    }
    return comments.value.filter(c => !c.is_resolved);
});

const commentTypeOptions = [
    { label: 'General', value: 'general', icon: 'i-lucide-message-circle' },
    { label: 'Revision Request', value: 'revision_request', icon: 'i-lucide-alert-circle' },
    { label: 'Approval', value: 'approval', icon: 'i-lucide-check-circle' },
];

async function fetchComments() {
    if (!props.versionUuid) return;

    loading.value = true;
    try {
        const response = await axios.get(cmsPath(`/workflow/versions/${props.versionUuid}/comments`));
        comments.value = response.data.comments;
    } catch (error) {
        console.error('Failed to fetch comments:', error);
    } finally {
        loading.value = false;
    }
}

async function addComment() {
    if (!props.versionUuid || !newComment.value.trim()) return;

    submitting.value = true;
    try {
        const response = await axios.post(cmsPath(`/workflow/versions/${props.versionUuid}/comments`), {
            content: newComment.value,
            type: commentType.value,
        });

        comments.value.unshift(response.data.comment);
        newComment.value = '';
        commentType.value = 'general';
        emit('comment-added', response.data.comment);
    } catch (error) {
        console.error('Failed to add comment:', error);
    } finally {
        submitting.value = false;
    }
}

async function resolveComment(comment: Comment) {
    try {
        await axios.post(cmsPath(`/workflow/comments/${comment.uuid}/resolve`));
        comment.is_resolved = true;
        emit('comment-resolved', comment);
    } catch (error) {
        console.error('Failed to resolve comment:', error);
    }
}

async function unresolveComment(comment: Comment) {
    try {
        await axios.post(cmsPath(`/workflow/comments/${comment.uuid}/unresolve`));
        comment.is_resolved = false;
    } catch (error) {
        console.error('Failed to unresolve comment:', error);
    }
}

function getTypeIcon(type: string): string {
    const icons: Record<string, string> = {
        general: 'i-lucide-message-circle',
        revision_request: 'i-lucide-alert-circle',
        approval: 'i-lucide-check-circle',
    };
    return icons[type] || 'i-lucide-message-circle';
}

function getTypeColor(type: string): string {
    const colors: Record<string, string> = {
        general: 'neutral',
        revision_request: 'warning',
        approval: 'success',
    };
    return colors[type] || 'neutral';
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days}d ago`;
    if (hours > 0) return `${hours}h ago`;
    if (minutes > 0) return `${minutes}m ago`;
    return 'just now';
}

watch(() => props.versionUuid, () => {
    if (props.versionUuid) {
        fetchComments();
    }
}, { immediate: true });
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <UIcon name="i-lucide-message-square" class="size-4 text-muted" />
                <span class="text-xs font-medium text-muted uppercase tracking-wider">Editorial Comments</span>
                <UBadge v-if="unresolvedCount > 0" color="warning" variant="subtle" size="xs">
                    {{ unresolvedCount }}
                </UBadge>
            </div>
            <UButton
                v-if="comments.length > 0"
                color="neutral"
                variant="ghost"
                size="xs"
                @click="showResolved = !showResolved"
            >
                {{ showResolved ? 'Hide resolved' : 'Show all' }}
            </UButton>
        </div>

        <!-- No version message -->
        <div v-if="!versionUuid" class="text-xs text-muted text-center py-4">
            <p>Save to enable comments</p>
        </div>

        <!-- Add Comment Form -->
        <div v-else class="space-y-2">
            <UTextarea
                v-model="newComment"
                placeholder="Add a comment..."
                :rows="2"
                class="w-full"
            />
            <div class="flex items-center gap-2">
                <USelectMenu
                    v-model="commentType"
                    :items="commentTypeOptions"
                    value-key="value"
                    size="xs"
                    class="w-32"
                >
                    <template #leading>
                        <UIcon :name="getTypeIcon(commentType)" class="size-3" />
                    </template>
                </USelectMenu>
                <UButton
                    size="xs"
                    :disabled="!newComment.trim()"
                    :loading="submitting"
                    class="ml-auto"
                    @click="addComment"
                >
                    Add
                </UButton>
            </div>
        </div>

        <!-- Comments List -->
        <div v-if="loading" class="flex justify-center py-4">
            <UIcon name="i-lucide-loader-2" class="size-5 animate-spin text-muted" />
        </div>

        <div v-else-if="filteredComments.length > 0" class="space-y-3 max-h-64 overflow-y-auto">
            <div
                v-for="comment in filteredComments"
                :key="comment.id"
                :class="[
                    'p-3 rounded-lg border',
                    comment.is_resolved ? 'bg-elevated/25 border-default/50 opacity-60' : 'bg-elevated/50 border-default',
                ]"
            >
                <div class="flex items-start gap-2">
                    <UAvatar
                        :src="comment.user.avatar_url || undefined"
                        :alt="comment.user.name"
                        size="xs"
                    />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium">{{ comment.user.name }}</span>
                            <UBadge
                                :color="getTypeColor(comment.type)"
                                variant="subtle"
                                size="xs"
                            >
                                <UIcon :name="getTypeIcon(comment.type)" class="size-2.5 mr-0.5" />
                                {{ comment.type.replace('_', ' ') }}
                            </UBadge>
                            <span class="text-xs text-muted ml-auto">{{ formatDate(comment.created_at) }}</span>
                        </div>
                        <p class="text-sm mt-1 whitespace-pre-wrap">{{ comment.content }}</p>

                        <div v-if="comment.is_resolved" class="flex items-center gap-1 mt-2 text-xs text-success">
                            <UIcon name="i-lucide-check" class="size-3" />
                            <span>Resolved by {{ comment.resolved_by?.name }}</span>
                        </div>
                    </div>

                    <UButton
                        v-if="!comment.is_resolved"
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-check"
                        size="xs"
                        @click="resolveComment(comment)"
                    />
                    <UButton
                        v-else
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-rotate-ccw"
                        size="xs"
                        @click="unresolveComment(comment)"
                    />
                </div>
            </div>
        </div>

        <div v-else-if="versionUuid && comments.length === 0" class="text-xs text-muted text-center py-4">
            <UIcon name="i-lucide-message-square" class="size-5 mx-auto mb-1 opacity-30" />
            <p>No comments yet</p>
        </div>
    </div>
</template>
