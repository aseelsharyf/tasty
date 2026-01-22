<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { usePermission } from '../../composables/usePermission';

interface Ingredient {
    ingredient: string;
    quantity?: string;
    unit?: string;
    prep_note?: string;
}

interface IngredientGroup {
    group_name?: string;
    items: Ingredient[];
}

interface InstructionGroup {
    group_name?: string;
    steps: string[];
}

interface ChildSubmission {
    id: number;
    uuid: string;
    recipe_name: string;
    description: string;
    status: string;
}

interface Author {
    id: number;
    name: string;
    email: string;
}

interface Language {
    code: string;
    name: string;
    native_name: string;
}

interface Submission {
    id: number;
    uuid: string;
    submission_type: 'single' | 'composite';
    submitter_name: string;
    submitter_email: string;
    submitter_phone?: string;
    is_chef: boolean;
    chef_name?: string;
    chef_display_name: string;
    recipe_name: string;
    headline?: string;
    slug: string;
    description: string;
    prep_time?: number;
    cook_time?: number;
    total_time?: number;
    servings?: number;
    categories?: string[];
    category_names?: Record<string, string>[];
    meal_times?: string[];
    ingredients?: IngredientGroup[];
    instructions?: InstructionGroup[];
    image_url?: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'converted';
    review_notes?: string;
    reviewer?: { id: number; name: string } | null;
    reviewed_at?: string;
    converted_post?: { id: number; uuid: string; title: string; status: string } | null;
    child_submissions: ChildSubmission[];
    parent_submission?: { id: number; uuid: string; recipe_name: string } | null;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    submission: Submission;
    authors: Author[];
    languages: Language[];
}>();

const { can } = usePermission();

const processing = ref(false);
const actionNotes = ref('');
const convertModalOpen = ref(false);
const selectedLanguage = ref(props.languages[0]?.code || 'en');
const authorMode = ref<'existing' | 'create'>('existing');
const selectedAuthorId = ref<number | null>(null);

// Language options for select
const languageOptions = computed(() =>
    props.languages.map(l => ({ value: l.code, label: `${l.name} (${l.native_name})` }))
);

// Author options for select
const authorOptions = computed(() =>
    props.authors.map(a => ({ value: a.id, label: `${a.name} (${a.email})` }))
);

function getStatusColor(status: string): 'warning' | 'success' | 'error' | 'info' | 'neutral' {
    switch (status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'error';
        case 'converted': return 'info';
        default: return 'neutral';
    }
}

function approve() {
    processing.value = true;
    router.post(`/cms/recipe-submissions/${props.submission.uuid}/approve`, {
        notes: actionNotes.value,
    }, {
        onFinish: () => {
            processing.value = false;
            actionNotes.value = '';
        },
    });
}

function reject() {
    processing.value = true;
    router.post(`/cms/recipe-submissions/${props.submission.uuid}/reject`, {
        notes: actionNotes.value,
    }, {
        onFinish: () => {
            processing.value = false;
            actionNotes.value = '';
        },
    });
}

function openConvertModal() {
    convertModalOpen.value = true;
    authorMode.value = 'existing';
    selectedAuthorId.value = null;
}

function convertToPost() {
    processing.value = true;
    router.post(`/cms/recipe-submissions/${props.submission.uuid}/convert`, {
        language_code: selectedLanguage.value,
        author_id: authorMode.value === 'existing' ? selectedAuthorId.value : null,
        create_author: authorMode.value === 'create',
    }, {
        onFinish: () => {
            processing.value = false;
            convertModalOpen.value = false;
        },
    });
}

function deleteSubmission() {
    if (confirm('Are you sure you want to delete this submission? This cannot be undone.')) {
        router.delete(`/cms/recipe-submissions/${props.submission.uuid}`);
    }
}

function goBack() {
    router.get('/cms/recipe-submissions');
}

function formatIngredient(item: Ingredient): string {
    let text = '';
    if (item.quantity) text += `${item.quantity} `;
    if (item.unit) text += `${item.unit} `;
    text += item.ingredient;
    if (item.prep_note) text += `, ${item.prep_note}`;
    return text;
}
</script>

<template>
    <Head :title="`Submission: ${submission.recipe_name}`" />

    <DashboardLayout>
        <UDashboardPanel id="recipe-submission-show">
            <template #header>
                <UDashboardNavbar :title="submission.recipe_name">
                    <template #leading>
                        <UButton
                            color="neutral"
                            variant="ghost"
                            icon="i-lucide-arrow-left"
                            @click="goBack"
                        />
                    </template>

                    <template #right>
                        <div class="flex items-center gap-2">
                            <UBadge
                                :color="getStatusColor(submission.status)"
                                size="lg"
                            >
                                {{ submission.status.charAt(0).toUpperCase() + submission.status.slice(1) }}
                            </UBadge>

                            <template v-if="submission.status === 'pending' && can('posts.edit')">
                                <UButton
                                    color="success"
                                    icon="i-lucide-check"
                                    @click="approve"
                                    :loading="processing"
                                >
                                    Approve
                                </UButton>
                                <UButton
                                    color="warning"
                                    variant="soft"
                                    icon="i-lucide-x"
                                    @click="reject"
                                    :loading="processing"
                                >
                                    Reject
                                </UButton>
                            </template>


                            <UButton
                                v-if="submission.converted_post"
                                color="info"
                                variant="soft"
                                icon="i-lucide-external-link"
                                :href="`/cms/posts/en/${submission.converted_post.uuid}/edit`"
                            >
                                View Post
                            </UButton>

                            <UButton
                                v-if="can('posts.delete')"
                                color="error"
                                variant="ghost"
                                icon="i-lucide-trash"
                                @click="deleteSubmission"
                            />
                        </div>
                    </template>
                </UDashboardNavbar>
            </template>

            <template #body>
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- Image -->
                    <div v-if="submission.image_url" class="rounded-lg overflow-hidden">
                        <img
                            :src="submission.image_url"
                            :alt="submission.recipe_name"
                            class="w-full h-64 object-cover"
                        />
                    </div>

                    <!-- Submitter Info -->
                    <UCard>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <UIcon name="i-lucide-user" class="size-5" />
                                <h3 class="font-semibold">Submitter Information</h3>
                            </div>
                        </template>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-muted">Name</dt>
                                <dd class="font-medium">{{ submission.submitter_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-muted">Email</dt>
                                <dd>
                                    <a :href="`mailto:${submission.submitter_email}`" class="text-primary hover:underline">
                                        {{ submission.submitter_email }}
                                    </a>
                                </dd>
                            </div>
                            <div v-if="submission.submitter_phone">
                                <dt class="text-sm text-muted">Phone</dt>
                                <dd>{{ submission.submitter_phone }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-muted">Chef Attribution</dt>
                                <dd>
                                    <template v-if="submission.is_chef">
                                        <UBadge color="success" variant="subtle">Submitter is the chef</UBadge>
                                    </template>
                                    <template v-else>
                                        <span class="text-warning">Chef: {{ submission.chef_name }}</span>
                                    </template>
                                </dd>
                            </div>
                        </div>
                    </UCard>

                    <!-- Recipe Details -->
                    <UCard>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <UIcon name="i-lucide-chef-hat" class="size-5" />
                                <h3 class="font-semibold">Recipe Details</h3>
                                <UBadge v-if="submission.submission_type === 'composite'" color="info" variant="subtle">
                                    Composite Meal
                                </UBadge>
                            </div>
                        </template>

                        <div class="space-y-4">
                            <div v-if="submission.headline">
                                <dt class="text-sm text-muted">Headline</dt>
                                <dd class="mt-1 font-medium text-highlighted">{{ submission.headline }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm text-muted">Description</dt>
                                <dd class="mt-1">{{ submission.description }}</dd>
                            </div>

                            <div class="grid grid-cols-4 gap-4">
                                <div v-if="submission.prep_time">
                                    <dt class="text-sm text-muted">Prep Time</dt>
                                    <dd class="font-medium">{{ submission.prep_time }} min</dd>
                                </div>
                                <div v-if="submission.cook_time">
                                    <dt class="text-sm text-muted">Cook Time</dt>
                                    <dd class="font-medium">{{ submission.cook_time }} min</dd>
                                </div>
                                <div v-if="submission.total_time">
                                    <dt class="text-sm text-muted">Total Time</dt>
                                    <dd class="font-medium">{{ submission.total_time }} min</dd>
                                </div>
                                <div v-if="submission.servings">
                                    <dt class="text-sm text-muted">Servings</dt>
                                    <dd class="font-medium">{{ submission.servings }}</dd>
                                </div>
                            </div>

                            <div v-if="submission.categories?.length">
                                <dt class="text-sm text-muted mb-2">Categories</dt>
                                <dd class="flex flex-wrap gap-2">
                                    <UBadge
                                        v-for="cat in submission.categories"
                                        :key="cat"
                                        color="neutral"
                                        variant="subtle"
                                    >
                                        {{ cat }}
                                    </UBadge>
                                </dd>
                            </div>

                            <div v-if="submission.meal_times?.length">
                                <dt class="text-sm text-muted mb-2">Meal Times</dt>
                                <dd class="flex flex-wrap gap-2">
                                    <UBadge
                                        v-for="time in submission.meal_times"
                                        :key="time"
                                        color="info"
                                        variant="subtle"
                                    >
                                        {{ time }}
                                    </UBadge>
                                </dd>
                            </div>
                        </div>
                    </UCard>

                    <!-- Ingredients -->
                    <UCard v-if="submission.ingredients?.length">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <UIcon name="i-lucide-list" class="size-5" />
                                <h3 class="font-semibold">Ingredients</h3>
                            </div>
                        </template>

                        <div class="space-y-4">
                            <div v-for="(group, gi) in submission.ingredients" :key="gi">
                                <h4 v-if="group.group_name" class="font-medium text-highlighted mb-2">
                                    {{ group.group_name }}
                                </h4>
                                <ul class="list-disc list-inside space-y-1">
                                    <li v-for="(item, ii) in group.items" :key="ii">
                                        {{ formatIngredient(item) }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </UCard>

                    <!-- Instructions -->
                    <UCard v-if="submission.instructions?.length">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <UIcon name="i-lucide-book-open" class="size-5" />
                                <h3 class="font-semibold">Instructions</h3>
                            </div>
                        </template>

                        <div class="space-y-4">
                            <div v-for="(group, gi) in submission.instructions" :key="gi" class="flex gap-3">
                                <div class="flex items-center justify-center size-7 rounded-full bg-primary/10 text-primary font-bold text-sm shrink-0">
                                    {{ gi + 1 }}
                                </div>
                                <div class="flex-1">
                                    <h4 v-if="group.group_name" class="font-medium text-highlighted">
                                        {{ group.group_name }}
                                    </h4>
                                    <p v-if="group.steps?.[0]" class="text-muted mt-1">
                                        {{ group.steps[0] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </UCard>

                    <!-- Child Submissions (Composite Meals) -->
                    <UCard v-if="submission.child_submissions?.length">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <UIcon name="i-lucide-layers" class="size-5" />
                                <h3 class="font-semibold">Included Recipes</h3>
                            </div>
                        </template>

                        <div class="space-y-3">
                            <div
                                v-for="child in submission.child_submissions"
                                :key="child.id"
                                class="p-3 rounded-lg bg-elevated"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-medium">{{ child.recipe_name }}</h4>
                                    <UBadge :color="getStatusColor(child.status)" size="xs">
                                        {{ child.status }}
                                    </UBadge>
                                </div>
                                <p class="text-sm text-muted">{{ child.description }}</p>
                            </div>
                        </div>
                    </UCard>

                    <!-- Review History -->
                    <UCard v-if="submission.reviewer || submission.review_notes">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <UIcon name="i-lucide-history" class="size-5" />
                                <h3 class="font-semibold">Review History</h3>
                            </div>
                        </template>

                        <div class="space-y-3">
                            <div v-if="submission.reviewer" class="flex items-center gap-3">
                                <UAvatar
                                    :alt="submission.reviewer.name"
                                    size="sm"
                                />
                                <div>
                                    <div class="font-medium">{{ submission.reviewer.name }}</div>
                                    <div class="text-sm text-muted">{{ submission.reviewed_at }}</div>
                                </div>
                            </div>
                            <p v-if="submission.review_notes" class="text-muted p-3 bg-elevated rounded-lg">
                                {{ submission.review_notes }}
                            </p>
                        </div>
                    </UCard>

                    <!-- Action Notes (for pending submissions) -->
                    <UCard v-if="submission.status === 'pending' && can('posts.edit')">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <UIcon name="i-lucide-message-square" class="size-5" />
                                <h3 class="font-semibold">Review Notes</h3>
                            </div>
                        </template>

                        <UTextarea
                            v-model="actionNotes"
                            placeholder="Add notes for the submitter (optional)..."
                            :rows="3"
                        />
                    </UCard>

                    <!-- Meta Info -->
                    <div class="text-sm text-muted flex items-center gap-4">
                        <span>Submitted: {{ submission.created_at }}</span>
                        <span>Last updated: {{ submission.updated_at }}</span>
                    </div>
                </div>
            </template>
        </UDashboardPanel>

        <!-- Convert to Post Modal -->
        <UModal v-model:open="convertModalOpen">
            <template #content>
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center size-10 rounded-full bg-primary/10">
                                <UIcon name="i-lucide-file-plus" class="size-5 text-primary" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold">Convert to Post</h3>
                                <p class="text-sm text-muted">Create a draft recipe post from this submission</p>
                            </div>
                        </div>
                    </template>

                    <div class="space-y-5">
                        <!-- Language Selection -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Language *</label>
                            <USelectMenu
                                v-model="selectedLanguage"
                                :items="languageOptions"
                                value-key="value"
                                class="w-full"
                            />
                        </div>

                        <!-- Author Selection -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Author</label>
                            <div class="space-y-3">
                                <!-- Author Mode Toggle -->
                                <div class="flex gap-2">
                                    <UButton
                                        :color="authorMode === 'existing' ? 'primary' : 'neutral'"
                                        :variant="authorMode === 'existing' ? 'solid' : 'outline'"
                                        size="sm"
                                        @click="authorMode = 'existing'"
                                    >
                                        Select Existing
                                    </UButton>
                                    <UButton
                                        v-if="submission.chef_display_name"
                                        :color="authorMode === 'create' ? 'primary' : 'neutral'"
                                        :variant="authorMode === 'create' ? 'solid' : 'outline'"
                                        size="sm"
                                        @click="authorMode = 'create'"
                                    >
                                        Create from Chef
                                    </UButton>
                                </div>

                                <!-- Existing Author Select -->
                                <div v-if="authorMode === 'existing'">
                                    <USelectMenu
                                        v-model="selectedAuthorId"
                                        :items="authorOptions"
                                        value-key="value"
                                        placeholder="Select an author (or leave blank for yourself)"
                                        class="w-full"
                                        searchable
                                        :search-input="{ placeholder: 'Search authors...' }"
                                    />
                                    <p class="text-xs text-muted mt-1">Leave blank to use yourself as the author</p>
                                </div>

                                <!-- Create from Chef -->
                                <div v-else-if="authorMode === 'create'" class="p-3 rounded-lg bg-elevated">
                                    <div class="flex items-center gap-2 text-sm">
                                        <UIcon name="i-lucide-user-plus" class="size-4 text-primary" />
                                        <span>Create contributor: <strong>{{ submission.chef_display_name }}</strong></span>
                                    </div>
                                    <p class="text-xs text-muted mt-1">
                                        A new user account will be created for attribution purposes (cannot login).
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="p-3 rounded-lg border border-default bg-elevated/50">
                            <h4 class="text-sm font-medium mb-2">Will create post with:</h4>
                            <ul class="text-sm text-muted space-y-1">
                                <li class="flex items-center gap-2">
                                    <UIcon name="i-lucide-type" class="size-3.5" />
                                    Title: {{ submission.recipe_name }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <UIcon name="i-lucide-file-text" class="size-3.5" />
                                    Description pre-filled
                                </li>
                                <li class="flex items-center gap-2">
                                    <UIcon name="i-lucide-clock" class="size-3.5" />
                                    Times: {{ submission.prep_time || 0 }}min prep, {{ submission.cook_time || 0 }}min cook
                                </li>
                                <li class="flex items-center gap-2">
                                    <UIcon name="i-lucide-list" class="size-3.5" />
                                    {{ submission.ingredients?.reduce((acc, g) => acc + g.items.length, 0) || 0 }} ingredients
                                </li>
                                <li class="flex items-center gap-2">
                                    <UIcon name="i-lucide-book-open" class="size-3.5" />
                                    {{ submission.instructions?.reduce((acc, g) => acc + g.steps.length, 0) || 0 }} instruction steps
                                </li>
                            </ul>
                        </div>
                    </div>

                    <template #footer>
                        <div class="flex justify-end gap-3">
                            <UButton
                                color="neutral"
                                variant="outline"
                                :disabled="processing"
                                @click="convertModalOpen = false"
                            >
                                Cancel
                            </UButton>
                            <UButton
                                color="primary"
                                :loading="processing"
                                @click="convertToPost"
                            >
                                Convert to Post
                            </UButton>
                        </div>
                    </template>
                </UCard>
            </template>
        </UModal>
    </DashboardLayout>
</template>
