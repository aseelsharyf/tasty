<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { formatDistanceToNow, format } from 'date-fns';
import { usePermission } from '../composables/usePermission';
import { useCmsPath } from '../composables/useCmsPath';

interface Comment {
    id: number;
    uuid: string;
    content: string;
    content_excerpt?: string;
    status: 'pending' | 'approved' | 'spam' | 'trashed';
    author_display_name: string;
    author_display_email: string;
    author_ip: string | null;
    gravatar_url: string;
    is_registered_user: boolean;
    is_edited: boolean;
    replies_count?: number;
    post: {
        id: number;
        title: string;
        slug: string;
    } | null;
    parent: {
        id: number;
        uuid: string;
        author_name: string;
        content_excerpt: string;
    } | null;
    created_at: string;
    edited_at: string | null;
}

const props = defineProps<{
    open: boolean;
    comment: Comment | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'approve': [comment: Comment];
    'spam': [comment: Comment];
    'trash': [comment: Comment];
    'restore': [comment: Comment];
    'delete': [comment: Comment];
}>();

const { can } = usePermission();
const { cmsPath } = useCmsPath();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const isEditing = ref(false);
const editedContent = ref('');
const isSaving = ref(false);

watch(() => props.comment, (comment) => {
    if (comment) {
        editedContent.value = comment.content;
        isEditing.value = false;
    }
});

function startEditing() {
    if (props.comment) {
        editedContent.value = props.comment.content;
        isEditing.value = true;
    }
}

function cancelEditing() {
    isEditing.value = false;
    if (props.comment) {
        editedContent.value = props.comment.content;
    }
}

function saveEdit() {
    if (!props.comment) return;

    isSaving.value = true;
    router.put(cmsPath(`/comments/${props.comment.uuid}`), {
        content: editedContent.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            isOpen.value = false;
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
}

function getStatusColor(status: string): 'warning' | 'success' | 'error' | 'neutral' {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'spam': return 'error';
        case 'trashed': return 'neutral';
        default: return 'neutral';
    }
}

function handleApprove() {
    if (props.comment) {
        emit('approve', props.comment);
        isOpen.value = false;
    }
}

function handleSpam() {
    if (props.comment) {
        emit('spam', props.comment);
        isOpen.value = false;
    }
}

function handleTrash() {
    if (props.comment) {
        emit('trash', props.comment);
        isOpen.value = false;
    }
}

function handleRestore() {
    if (props.comment) {
        emit('restore', props.comment);
        isOpen.value = false;
    }
}

function handleDelete() {
    if (props.comment) {
        emit('delete', props.comment);
    }
}
</script>

<template>
    <USlideover v-model:open="isOpen" :ui="{ width: 'max-w-md' }">
        <template #content>
            <UCard v-if="comment" class="flex flex-col h-full">
                <template #header>
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <UAvatar
                                :src="comment.gravatar_url"
                                :alt="comment.author_display_name"
                                size="lg"
                            />
                            <div>
                                <div class="font-medium text-highlighted flex items-center gap-1.5">
                                    {{ comment.author_display_name }}
                                    <UIcon
                                        v-if="comment.is_registered_user"
                                        name="i-lucide-badge-check"
                                        class="size-4 text-primary"
                                    />
                                </div>
                                <div class="text-sm text-muted">{{ comment.author_display_email }}</div>
                            </div>
                        </div>
                        <UButton
                            icon="i-lucide-x"
                            color="neutral"
                            variant="ghost"
                            @click="isOpen = false"
                        />
                    </div>
                </template>

                <div class="flex-1 overflow-y-auto space-y-4">
                    <!-- Status -->
                    <div class="flex items-center gap-2">
                        <UBadge
                            :color="getStatusColor(comment.status)"
                            variant="subtle"
                        >
                            {{ comment.status.charAt(0).toUpperCase() + comment.status.slice(1) }}
                        </UBadge>
                        <UBadge v-if="comment.is_edited" color="neutral" variant="subtle">
                            Edited
                        </UBadge>
                    </div>

                    <!-- Post -->
                    <div v-if="comment.post" class="flex items-start gap-2">
                        <UIcon name="i-lucide-file-text" class="size-4 text-muted mt-0.5" />
                        <div>
                            <span class="text-sm text-muted">On post:</span>
                            <a
                                :href="cmsPath(`/posts/${comment.post.slug}`)"
                                class="text-sm text-primary hover:underline block"
                            >
                                {{ comment.post.title }}
                            </a>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="flex items-start gap-2">
                        <UIcon name="i-lucide-calendar" class="size-4 text-muted mt-0.5" />
                        <div>
                            <span class="text-sm text-muted">Submitted:</span>
                            <span class="text-sm text-default block">
                                {{ format(new Date(comment.created_at), 'PPpp') }}
                                ({{ formatDistanceToNow(new Date(comment.created_at), { addSuffix: true }) }})
                            </span>
                        </div>
                    </div>

                    <!-- IP Address -->
                    <div v-if="comment.author_ip" class="flex items-start gap-2">
                        <UIcon name="i-lucide-globe" class="size-4 text-muted mt-0.5" />
                        <div>
                            <span class="text-sm text-muted">IP Address:</span>
                            <span class="text-sm text-default font-mono block">{{ comment.author_ip }}</span>
                        </div>
                    </div>

                    <!-- Parent Comment -->
                    <div v-if="comment.parent" class="rounded-lg bg-muted/30 border border-default p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted mb-2">
                            <UIcon name="i-lucide-corner-down-right" class="size-3" />
                            <span>In reply to <span class="font-medium text-highlighted">{{ comment.parent.author_name }}</span></span>
                        </div>
                        <p class="text-sm text-default italic">"{{ comment.parent.content_excerpt }}"</p>
                    </div>

                    <USeparator />

                    <!-- Comment Content -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-highlighted">Comment</h4>
                            <UButton
                                v-if="can('comments.edit') && !isEditing"
                                icon="i-lucide-pencil"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                @click="startEditing"
                            >
                                Edit
                            </UButton>
                        </div>

                        <div v-if="isEditing">
                            <UTextarea
                                v-model="editedContent"
                                :rows="6"
                                class="w-full"
                                autofocus
                            />
                            <div class="flex justify-end gap-2 mt-2">
                                <UButton
                                    color="neutral"
                                    variant="ghost"
                                    size="sm"
                                    :disabled="isSaving"
                                    @click="cancelEditing"
                                >
                                    Cancel
                                </UButton>
                                <UButton
                                    color="primary"
                                    size="sm"
                                    :loading="isSaving"
                                    @click="saveEdit"
                                >
                                    Save Changes
                                </UButton>
                            </div>
                        </div>
                        <div v-else class="prose prose-sm dark:prose-invert max-w-none">
                            <p class="whitespace-pre-wrap text-sm text-default">{{ comment.content }}</p>
                        </div>
                    </div>
                </div>

                <template #footer>
                    <div class="space-y-3">
                        <!-- Status action buttons -->
                        <div v-if="can('comments.moderate')" class="flex items-center justify-end gap-2">
                            <UButton
                                v-if="comment.status === 'trashed'"
                                icon="i-lucide-undo"
                                color="neutral"
                                variant="soft"
                                size="sm"
                                @click="handleRestore"
                            >
                                Restore
                            </UButton>
                            <template v-else>
                                <UButton
                                    v-if="comment.status !== 'approved'"
                                    icon="i-lucide-check"
                                    color="success"
                                    variant="soft"
                                    size="sm"
                                    @click="handleApprove"
                                >
                                    Approve
                                </UButton>
                                <UButton
                                    v-if="comment.status !== 'spam'"
                                    icon="i-lucide-shield-alert"
                                    color="warning"
                                    variant="soft"
                                    size="sm"
                                    @click="handleSpam"
                                >
                                    Spam
                                </UButton>
                                <UButton
                                    v-if="comment.status !== 'trashed'"
                                    icon="i-lucide-trash"
                                    color="neutral"
                                    variant="soft"
                                    size="sm"
                                    @click="handleTrash"
                                >
                                    Trash
                                </UButton>
                            </template>
                        </div>

                        <!-- Delete button -->
                        <div v-if="can('comments.delete')" class="flex justify-end border-t border-default pt-3 -mb-1">
                            <UButton
                                icon="i-lucide-trash-2"
                                color="error"
                                variant="ghost"
                                size="xs"
                                @click="handleDelete"
                            >
                                Delete Permanently
                            </UButton>
                        </div>
                    </div>
                </template>
            </UCard>
        </template>
    </USlideover>
</template>
