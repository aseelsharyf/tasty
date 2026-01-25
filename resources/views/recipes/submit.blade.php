@extends('layouts.app')

@section('content')
<div class="w-full px-5 lg:px-10 pt-32 lg:pt-40 pb-20 bg-tasty-off-white"
     x-data="recipeSubmission()"
     x-init="init()">

    <!-- Login Required Modal -->
    <div
        x-show="showLoginModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="showLoginModal = false"
    >
        <div
            x-show="showLoginModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center"
        >
            <!-- Icon -->
            <div class="w-16 h-16 bg-tasty-yellow/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-tasty-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>

            <h3 class="text-h4 text-tasty-blue-black mb-2">Sign in to Continue</h3>
            <p class="text-body-md text-tasty-blue-black/70 mb-6">
                You need to be signed in to submit a recipe. It only takes a moment!
            </p>

            <!-- Google Sign In Button -->
            <a
                href="{{ route('auth.google', ['redirect' => url()->current()]) }}"
                class="inline-flex items-center justify-center gap-3 w-full px-6 py-3 bg-white border border-gray-200 rounded-xl text-tasty-blue-black font-medium hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm"
            >
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Continue with Google
            </a>

            <p class="text-xs text-gray-500 mt-4">
                By signing in, you agree to our Terms of Service and Privacy Policy.
            </p>

            <button
                @click="showLoginModal = false"
                class="mt-4 text-sm text-gray-500 hover:text-tasty-blue-black transition-colors"
            >
                Cancel
            </button>
        </div>
    </div>

    <div class="max-w-2xl mx-auto">
        <div class="flex flex-col gap-8">
            <!-- Header -->
            <div class="text-center">
                <x-ui.heading
                    level="h1"
                    text="Submit a Recipe"
                    align="center"
                />
                <p class="text-body-md text-tasty-blue-black/70 mt-4">
                    Share your delicious creations with the Tasty community.
                </p>
            </div>

            <!-- Auth Error Message -->
            @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-red-800">Authentication Error</h3>
                        <p class="mt-1 text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Mode Selector -->
            <div class="flex justify-center gap-3" :class="!authUser ? 'opacity-60 pointer-events-none' : ''">
                <button
                    type="button"
                    @click="mode = 'single'"
                    :class="mode === 'single' ? 'bg-tasty-yellow' : 'bg-white border border-gray-200 hover:border-tasty-blue-black'"
                    class="px-5 py-2.5 rounded-full text-sm font-medium transition-all text-tasty-blue-black"
                    :disabled="!authUser"
                >
                    Single Recipe
                </button>
                <button
                    type="button"
                    @click="mode = 'composite'"
                    :class="mode === 'composite' ? 'bg-tasty-yellow' : 'bg-white border border-gray-200 hover:border-tasty-blue-black'"
                    class="px-5 py-2.5 rounded-full text-sm font-medium transition-all text-tasty-blue-black"
                    :disabled="!authUser"
                >
                    Composite Meal
                </button>
            </div>

            <!-- Error Summary -->
            <div
                x-show="Object.keys(errors).length > 0"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                class="bg-red-50 border border-red-200 rounded-2xl p-4"
            >
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside space-y-1">
                            <template x-for="(message, field) in errors" :key="field">
                                <li x-text="message"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form
                action="{{ route('recipes.submit.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="flex flex-col gap-6 relative"
                @submit.prevent="submitForm"
                @keydown.enter.prevent="focusNextField($event)"
            >
                @csrf
                <input type="hidden" name="submission_type" :value="mode">

                <!-- Login Required Overlay (when not authenticated) -->
                <template x-if="!authUser">
                    <div class="absolute inset-0 z-10 flex items-start justify-center pt-32 pointer-events-none">
                        <div class="pointer-events-auto">
                            <a
                                href="{{ route('auth.google', ['redirect' => url()->current()]) }}"
                                class="group flex flex-col items-center gap-3 px-8 py-6 bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl hover:scale-105 transition-all duration-300"
                            >
                                <div class="relative">
                                    <div class="w-14 h-14 bg-tasty-yellow/20 rounded-full flex items-center justify-center group-hover:bg-tasty-yellow/30 transition-colors">
                                        <svg class="w-7 h-7 text-tasty-blue-black/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-sm border border-gray-100">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-medium text-tasty-blue-black">Sign in to submit</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Continue with Google</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Submitter Information -->
                <div class="bg-white rounded-2xl p-6 flex flex-col gap-5" :class="!authUser ? 'opacity-50 pointer-events-none blur-[1px]' : ''">
                    <div class="flex items-center justify-between">
                        <h2 class="text-h4 text-tasty-blue-black">Your Information</h2>
                        <template x-if="authUser">
                            <form action="{{ route('auth.logout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="redirect" value="{{ url()->current() }}">
                                <button type="submit" class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                                    Switch account
                                </button>
                            </form>
                        </template>
                    </div>

                    <!-- Logged in user - editable name and avatar -->
                    <template x-if="authUser">
                        <div class="flex flex-col gap-4">
                            <!-- Avatar and Name row -->
                            <div class="flex items-start gap-4">
                                <!-- Avatar with edit/remove options -->
                                <div class="relative group">
                                    <img
                                        :src="form.avatar_preview || authUser.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(form.submitter_name || authUser.name) + '&background=FFD93D&color=1a1a1a'"
                                        :alt="form.submitter_name || authUser.name"
                                        class="w-16 h-16 rounded-full object-cover"
                                    />
                                    <!-- Overlay with actions -->
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                        <!-- Upload button -->
                                        <label class="p-1.5 hover:bg-white/20 rounded-full cursor-pointer transition-colors">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                @change="handleAvatarSelect($event)"
                                            />
                                        </label>
                                        <!-- Remove button (only show if has custom avatar) -->
                                        <button
                                            x-show="authUser.avatar || form.avatar_preview"
                                            type="button"
                                            @click="removeAvatar()"
                                            class="p-1.5 hover:bg-white/20 rounded-full cursor-pointer transition-colors"
                                        >
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Name and Email -->
                                <div class="flex-1 flex flex-col gap-3">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-medium text-gray-500">Your Name *</label>
                                        <input
                                            type="text"
                                            x-model="form.submitter_name"
                                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                            :class="errors.submitter_name ? 'border-red-500' : ''"
                                            placeholder="Your name"
                                            required
                                        />
                                        <span x-show="errors.submitter_name" x-text="errors.submitter_name" class="text-red-500 text-xs"></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                        <span x-text="authUser.email"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Profile Button -->
                            <div
                                x-show="hasProfileChanges()"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="flex items-center justify-between pt-3 border-t border-gray-100"
                            >
                                <p class="text-xs text-gray-500">You have unsaved changes</p>
                                <button
                                    type="button"
                                    @click="saveProfile()"
                                    :disabled="savingProfile"
                                    class="px-4 py-1.5 bg-tasty-yellow text-tasty-blue-black text-sm font-medium rounded-lg hover:bg-tasty-yellow/80 transition-colors disabled:opacity-50"
                                >
                                    <span x-show="!savingProfile">Save changes</span>
                                    <span x-show="savingProfile">Saving...</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Manual input for non-logged in users (won't show due to overlay, but kept for structure) -->
                    <template x-if="!authUser">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="submitter_name" class="text-xs font-medium text-gray-500">Your Name *</label>
                                <input
                                    type="text"
                                    id="submitter_name"
                                    name="submitter_name"
                                    x-model="form.submitter_name"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                    :class="errors.submitter_name ? 'border-red-500' : ''"
                                    placeholder="Full name"
                                    required
                                />
                                <span x-show="errors.submitter_name" x-text="errors.submitter_name" class="text-red-500 text-xs"></span>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="submitter_email" class="text-xs font-medium text-gray-500">Email *</label>
                                <input
                                    type="email"
                                    id="submitter_email"
                                    name="submitter_email"
                                    x-model="form.submitter_email"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                    :class="errors.submitter_email ? 'border-red-500' : ''"
                                    placeholder="your@email.com"
                                    required
                                />
                                <span x-show="errors.submitter_email" x-text="errors.submitter_email" class="text-red-500 text-xs"></span>
                            </div>
                        </div>
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="submitter_phone" class="text-xs font-medium text-gray-500">Phone (Optional)</label>
                            <input
                                type="tel"
                                id="submitter_phone"
                                name="submitter_phone"
                                x-model="form.submitter_phone"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                placeholder="+960 xxx xxxx"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5" x-show="!form.is_chef" x-collapse>
                            <label for="chef_name" class="text-xs font-medium text-gray-500">Chef's Name *</label>
                            <input
                                type="text"
                                id="chef_name"
                                name="chef_name"
                                x-model="form.chef_name"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                :class="errors.chef_name ? 'border-red-500' : ''"
                                placeholder="Name of the original chef"
                            />
                            <span x-show="errors.chef_name" x-text="errors.chef_name" class="text-red-500 text-xs"></span>
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_chef"
                            x-model="form.is_chef"
                            class="w-4 h-4 rounded border-gray-300 text-tasty-yellow focus:ring-tasty-yellow"
                        />
                        <span class="text-sm text-tasty-blue-black">I am the chef/creator of this recipe</span>
                    </label>
                </div>

                <!-- Recipe Basic Information -->
                <div class="bg-white rounded-2xl p-6 flex flex-col gap-5" :class="!authUser ? 'opacity-50 pointer-events-none blur-[1px]' : ''">
                    <h2 class="text-h4 text-tasty-blue-black">Recipe Details</h2>

                    <div class="flex flex-col gap-1.5">
                        <label for="recipe_name" class="text-xs font-medium text-gray-500">Recipe Name *</label>
                        <input
                            type="text"
                            id="recipe_name"
                            name="recipe_name"
                            x-model="form.recipe_name"
                            @input="generateSlug()"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                            :class="errors.recipe_name ? 'border-red-500' : ''"
                            placeholder="e.g., Traditional Mas Huni"
                            required
                        />
                        <span x-show="errors.recipe_name" x-text="errors.recipe_name" class="text-red-500 text-xs"></span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="headline" class="text-xs font-medium text-gray-500">Headline *</label>
                        <input
                            type="text"
                            id="headline"
                            name="headline"
                            x-model="form.headline"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                            :class="errors.headline ? 'border-red-500' : ''"
                            placeholder="A short catchy headline for your recipe"
                            required
                        />
                        <span x-show="errors.headline" x-text="errors.headline" class="text-red-500 text-xs"></span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="description" class="text-xs font-medium text-gray-500">Description *</label>
                        <textarea
                            id="description"
                            name="description"
                            x-model="form.description"
                            rows="3"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all resize-none overflow-hidden"
                            :class="errors.description ? 'border-red-500' : ''"
                            placeholder="Describe your recipe..."
                            required
                            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                            x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'; })"
                        ></textarea>
                        <span x-show="errors.description" x-text="errors.description" class="text-red-500 text-xs"></span>
                    </div>

                    <!-- Time & Servings -->
                    <div class="grid grid-cols-4 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="prep_time" class="text-xs font-medium text-gray-500">Prep (min)</label>
                            <input
                                type="number"
                                id="prep_time"
                                name="prep_time"
                                x-model="form.prep_time"
                                @input="calculateTotalTime()"
                                min="0"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                placeholder="15"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="cook_time" class="text-xs font-medium text-gray-500">Cook (min)</label>
                            <input
                                type="number"
                                id="cook_time"
                                name="cook_time"
                                x-model="form.cook_time"
                                @input="calculateTotalTime()"
                                min="0"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                placeholder="30"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-500">Total</label>
                            <input
                                type="text"
                                :value="form.total_time ? form.total_time + ' min' : '—'"
                                class="w-full px-3 py-2.5 border border-gray-100 rounded-xl text-sm bg-gray-50 text-gray-500"
                                readonly
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="servings" class="text-xs font-medium text-gray-500">Servings</label>
                            <input
                                type="number"
                                id="servings"
                                name="servings"
                                x-model="form.servings"
                                min="1"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-tasty-blue-black focus:ring-1 focus:ring-tasty-blue-black transition-all"
                                placeholder="4"
                            />
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-500">Recipe Image (Optional)</label>
                        <div
                            class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-tasty-blue-black/30 transition-colors"
                            @click="$refs.imageInput.click()"
                            @dragover.prevent="dragover = true"
                            @dragleave.prevent="dragover = false"
                            @drop.prevent="handleImageDrop($event)"
                            :class="dragover ? 'border-tasty-yellow bg-yellow-50' : ''"
                        >
                            <input
                                type="file"
                                name="image"
                                accept="image/*"
                                class="hidden"
                                x-ref="imageInput"
                                @change="handleImageSelect($event)"
                            />
                            <template x-if="!imagePreview">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm text-gray-500">Click or drag to upload</span>
                                    <span class="text-xs text-gray-400">PNG, JPG up to 10MB</span>
                                </div>
                            </template>
                            <template x-if="imagePreview">
                                <div class="relative inline-block">
                                    <img :src="imagePreview" class="max-h-40 rounded-lg" alt="Recipe preview" />
                                    <button
                                        type="button"
                                        @click.stop="removeImage()"
                                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 text-xs"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-medium text-gray-500">Category (Optional)</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($categories as $category)
                            <label class="cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="categories[]"
                                    value="{{ $category->slug }}"
                                    x-model="form.categories"
                                    class="hidden peer"
                                />
                                <span class="inline-block px-3 py-1.5 rounded-full border border-gray-200 text-xs text-tasty-blue-black peer-checked:bg-tasty-yellow peer-checked:border-tasty-yellow transition-all hover:border-gray-400">
                                    {{ $category->getTranslation('name', 'en') }}
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Meal Times -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-medium text-gray-500">Meal Time (Optional)</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="mealTime in mealTimes" :key="mealTime.id">
                                <label class="cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :name="`meal_times[]`"
                                        :value="mealTime.id"
                                        x-model="form.meal_times"
                                        class="hidden peer"
                                    />
                                    <span class="inline-block px-3 py-1.5 rounded-full border border-gray-200 text-xs text-tasty-blue-black peer-checked:bg-tasty-yellow peer-checked:border-tasty-yellow transition-all hover:border-gray-400" x-text="mealTime.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Ingredients Section -->
                <div class="bg-white rounded-2xl p-6 flex flex-col gap-5" :class="!authUser ? 'opacity-50 pointer-events-none blur-[1px]' : ''">
                    <div class="flex items-center justify-between">
                        <h2 class="text-h4 text-tasty-blue-black">Ingredients *</h2>
                    </div>

                    <template x-for="(group, groupIndex) in form.ingredients" :key="groupIndex">
                        <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3 bg-gray-50/50">
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    :name="`ingredients[${groupIndex}][group_name]`"
                                    x-model="group.group_name"
                                    class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-tasty-blue-black transition-colors bg-white"
                                    placeholder="Group name (e.g., For the sauce)"
                                />
                                <button
                                    type="button"
                                    @click="removeIngredientGroup(groupIndex)"
                                    x-show="form.ingredients.length > 1"
                                    class="text-red-400 hover:text-red-600 p-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <template x-for="(item, itemIndex) in group.items" :key="itemIndex">
                                <div class="flex gap-2 items-center">
                                    <!-- Quantity + Unit combined -->
                                    <div class="flex border border-gray-200 rounded-lg bg-white">
                                        <input
                                            type="text"
                                            :name="`ingredients[${groupIndex}][items][${itemIndex}][quantity]`"
                                            x-model="item.quantity"
                                            class="w-12 px-2 py-2 text-sm focus:outline-none text-center border-r border-gray-200 rounded-l-lg"
                                            placeholder="Qty"
                                        />
                                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                                            <input
                                                type="text"
                                                :name="`ingredients[${groupIndex}][items][${itemIndex}][unit]`"
                                                x-model="item.unit"
                                                @input="open = true"
                                                @focus="open = true"
                                                class="w-14 px-2 py-2 text-sm focus:outline-none text-center rounded-r-lg"
                                                placeholder="unit"
                                                autocomplete="off"
                                            />
                                            <div
                                                x-show="open"
                                                x-transition
                                                class="absolute z-30 w-36 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto left-0"
                                                style="top: 100%;"
                                            >
                                                <template x-for="unit in filteredUnits(item.unit)" :key="unit.id">
                                                    <button
                                                        type="button"
                                                        @click="item.unit = unit.abbreviation || unit.name; open = false"
                                                        class="w-full px-3 py-2 text-left hover:bg-gray-50 text-sm border-b border-gray-100 last:border-b-0"
                                                        x-text="unit.abbreviation ? unit.abbreviation + ' (' + unit.name + ')' : unit.name"
                                                    ></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ingredient Name with Autocomplete -->
                                    <div class="flex-1 relative" x-data="{ open: false }" @click.away="open = false">
                                        <input
                                            type="text"
                                            :name="`ingredients[${groupIndex}][items][${itemIndex}][ingredient]`"
                                            x-model="item.ingredient"
                                            @input="open = $event.target.value.length > 0"
                                            @focus="open = item.ingredient.length > 0"
                                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-tasty-blue-black transition-colors"
                                            placeholder="Ingredient *"
                                            required
                                            autocomplete="off"
                                        />
                                        <div
                                            x-show="open && filteredIngredients(item.ingredient).length > 0"
                                            x-transition
                                            class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-40 overflow-y-auto"
                                        >
                                            <template x-for="ing in filteredIngredients(item.ingredient)" :key="ing.id">
                                                <button
                                                    type="button"
                                                    @click="item.ingredient = ing.name; open = false"
                                                    class="w-full px-3 py-2 text-left hover:bg-gray-50 text-sm border-b border-gray-100 last:border-b-0"
                                                    x-text="ing.name"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Prep Note (compact) -->
                                    <input
                                        type="text"
                                        :name="`ingredients[${groupIndex}][items][${itemIndex}][prep_note]`"
                                        x-model="item.prep_note"
                                        class="w-28 px-2 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-tasty-blue-black transition-colors text-gray-500"
                                        placeholder="chopped..."
                                    />

                                    <!-- Remove Button -->
                                    <button
                                        type="button"
                                        @click="removeIngredient(groupIndex, itemIndex)"
                                        x-show="group.items.length > 1"
                                        class="text-red-400 hover:text-red-600 p-1 shrink-0"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <button
                                type="button"
                                @click="addIngredient(groupIndex)"
                                class="self-start text-xs text-gray-500 hover:text-tasty-blue-black transition-colors flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Ingredient
                            </button>
                        </div>
                    </template>

                    <!-- Add Group button at bottom (centered) -->
                    <button
                        type="button"
                        @click="addIngredientGroup()"
                        class="mx-auto text-xs text-gray-500 hover:text-tasty-blue-black transition-colors flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Group
                    </button>
                    <span x-show="errors.ingredients" x-text="errors.ingredients" class="text-red-500 text-xs"></span>
                </div>

                <!-- Instructions Section -->
                <div class="bg-white rounded-2xl p-6 flex flex-col gap-5" :class="!authUser ? 'opacity-50 pointer-events-none blur-[1px]' : ''">
                    <div class="flex items-center justify-between">
                        <h2 class="text-h4 text-tasty-blue-black">Instructions *</h2>
                    </div>

                    <template x-for="(group, groupIndex) in form.instructions" :key="groupIndex">
                        <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3 bg-gray-50/50">
                            <!-- Step header with number badge -->
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 bg-tasty-yellow rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-tasty-blue-black" x-text="groupIndex + 1"></span>
                                </div>
                                <input
                                    type="text"
                                    :name="`instructions[${groupIndex}][group_name]`"
                                    x-model="group.group_name"
                                    class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-tasty-blue-black transition-colors bg-white"
                                    :placeholder="'Step ' + (groupIndex + 1) + ' (e.g., Prepare the dough)'"
                                />
                                <button
                                    type="button"
                                    @click="removeInstructionGroup(groupIndex)"
                                    x-show="form.instructions.length > 1"
                                    class="text-red-400 hover:text-red-600 p-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Step description -->
                            <div class="ml-10">
                                <textarea
                                    :name="`instructions[${groupIndex}][steps][0]`"
                                    x-model="group.steps[0]"
                                    rows="2"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-tasty-blue-black transition-colors resize-none overflow-hidden"
                                    placeholder="Describe this step..."
                                    required
                                    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                    x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'; })"
                                ></textarea>
                            </div>
                        </div>
                    </template>

                    <!-- Add Step button at bottom (centered) -->
                    <button
                        type="button"
                        @click="addInstructionGroup()"
                        class="mx-auto text-xs text-gray-500 hover:text-tasty-blue-black transition-colors flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Step
                    </button>
                    <span x-show="errors.instructions" x-text="errors.instructions" class="text-red-500 text-xs"></span>
                </div>

                <!-- Composite Meal: Child Recipes -->
                <template x-if="mode === 'composite'">
                    <div class="bg-white rounded-2xl p-6 flex flex-col gap-5" :class="!authUser ? 'opacity-50 pointer-events-none blur-[1px]' : ''">
                        <div class="flex items-center justify-between">
                            <h2 class="text-h4 text-tasty-blue-black">Included Recipes *</h2>
                            <button
                                type="button"
                                @click="addChildRecipe()"
                                class="text-xs text-gray-500 hover:text-tasty-blue-black transition-colors flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Recipe
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 -mt-2">A composite meal includes multiple recipes that go together (e.g., Mas Huni with Roshi).</p>

                        <template x-for="(childRecipe, index) in form.child_recipes" :key="index">
                            <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3 bg-gray-50/50">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-tasty-blue-black">Recipe <span x-text="index + 1"></span></span>
                                    <button
                                        type="button"
                                        @click="removeChildRecipe(index)"
                                        x-show="form.child_recipes.length > 2"
                                        class="text-red-400 hover:text-red-600 p-1"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <input
                                    type="text"
                                    :name="`child_recipes[${index}][recipe_name]`"
                                    x-model="childRecipe.recipe_name"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-tasty-blue-black transition-colors"
                                    placeholder="Recipe name *"
                                    required
                                />

                                <textarea
                                    :name="`child_recipes[${index}][description]`"
                                    x-model="childRecipe.description"
                                    rows="2"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-tasty-blue-black transition-colors resize-none"
                                    placeholder="Brief description..."
                                    required
                                ></textarea>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Declaration Checkbox -->
                <div class="bg-white rounded-2xl p-6" :class="!authUser ? 'opacity-50 pointer-events-none blur-[1px]' : ''">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            x-model="form.declaration"
                            class="w-5 h-5 mt-0.5 rounded border-gray-300 text-tasty-yellow focus:ring-tasty-yellow"
                        />
                        <span class="text-sm text-tasty-blue-black">
                            I hereby confirm that this recipe is my own original creation or I have permission to share it. I agree to the Terms of Service and Privacy Policy.
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center pt-2" :class="!authUser ? 'opacity-50 pointer-events-none' : ''">
                    <button
                        type="submit"
                        :disabled="submitting || !authUser || !form.declaration"
                        class="btn btn-yellow px-8 py-3 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <template x-if="!submitting">
                            <span>Submit Recipe</span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Submitting...
                            </span>
                        </template>
                    </button>
                </div>
            </form>

            <!-- Guidelines -->
            <div class="bg-tasty-yellow/20 rounded-2xl p-5 text-center">
                <p class="text-sm text-tasty-blue-black">
                    All submissions are reviewed by our editorial team. Approved recipes will be published with credit to you.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function recipeSubmission() {
    return {
        mode: 'single',
        submitting: false,
        savingProfile: false,
        dragover: false,
        imagePreview: null,
        imageFile: null,
        avatarFile: null,
        showLoginModal: false,
        authUser: @json($authUser),
        originalName: @json($authUser ? $authUser['name'] : ''),

        ingredients: @json($ingredients),
        units: @json($units),
        mealTimes: [
            { id: 'breakfast', name: 'Breakfast' },
            { id: 'brunch', name: 'Brunch' },
            { id: 'lunch', name: 'Lunch' },
            { id: 'snack', name: 'Snack' },
            { id: 'dinner', name: 'Dinner' },
            { id: 'evening-tea', name: 'Evening Tea' },
            { id: 'late-night', name: 'Late Night' },
            { id: 'anytime', name: 'Anytime' }
        ],

        form: {
            submitter_name: '',
            submitter_email: '',
            submitter_phone: '',
            avatar_preview: null,
            is_chef: true,
            chef_name: '',
            recipe_name: '',
            headline: '',
            slug: '',
            description: '',
            prep_time: '',
            cook_time: '',
            total_time: '',
            servings: '',
            categories: [],
            meal_times: [],
            declaration: false,
            ingredients: [
                {
                    group_name: '',
                    items: [{ ingredient: '', quantity: '', unit: '', prep_note: '' }]
                }
            ],
            instructions: [
                {
                    group_name: '',
                    steps: ['']
                }
            ],
            child_recipes: [
                { recipe_name: '', description: '', ingredients: [], instructions: [] },
                { recipe_name: '', description: '', ingredients: [], instructions: [] }
            ]
        },

        errors: {},
        rawErrors: {},

        init() {
            // Pre-fill name from auth user if logged in
            if (this.authUser) {
                this.form.submitter_name = this.authUser.name || '';
                this.form.submitter_email = this.authUser.email || '';
            }
        },

        requireAuth() {
            if (!this.authUser) {
                this.showLoginModal = true;
                return false;
            }
            return true;
        },

        // Avatar handling
        handleAvatarSelect(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                this.avatarFile = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.form.avatar_preview = e.target.result;
                };
                reader.readAsDataURL(file);

                // Auto-save avatar to profile
                this.saveProfile();
            }
        },

        hasProfileChanges() {
            if (!this.authUser) return false;
            // Check if name changed or new avatar selected
            return (this.form.submitter_name !== this.originalName) || this.avatarFile !== null;
        },

        async removeAvatar() {
            if (!this.authUser) return;

            // Clear local preview
            this.form.avatar_preview = null;
            this.avatarFile = null;

            try {
                const response = await fetch('/api/auth/profile/remove-avatar', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.user) {
                        this.authUser = data.user;
                        this.originalName = data.user.name;
                    }
                }
            } catch (e) {
                console.error('Failed to remove avatar:', e);
            }
        },

        // Save profile (name/avatar) to user account
        async saveProfile() {
            if (!this.authUser) return;
            if (!this.hasProfileChanges()) return;

            this.savingProfile = true;

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');

            if (this.form.submitter_name && this.form.submitter_name !== this.originalName) {
                formData.append('name', this.form.submitter_name);
            }

            if (this.avatarFile) {
                formData.append('avatar', this.avatarFile);
            }

            try {
                const response = await fetch('/api/auth/profile', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.user) {
                        // Update local authUser with new data
                        this.authUser = data.user;
                        this.originalName = data.user.name;
                        this.avatarFile = null; // Clear file after successful upload
                        this.form.avatar_preview = null;
                    }
                }
            } catch (e) {
                console.error('Failed to save profile:', e);
            } finally {
                this.savingProfile = false;
            }
        },

        focusNextField(event) {
            // Don't prevent enter in textareas (allow new lines)
            if (event.target.tagName === 'TEXTAREA') {
                return;
            }

            const form = event.target.closest('form');
            const focusableElements = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="file"]), textarea, select'));
            const currentIndex = focusableElements.indexOf(event.target);

            if (currentIndex > -1 && currentIndex < focusableElements.length - 1) {
                focusableElements[currentIndex + 1].focus();
            }
        },

        generateSlug() {
            this.form.slug = this.form.recipe_name
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
        },

        calculateTotalTime() {
            const prep = parseInt(this.form.prep_time) || 0;
            const cook = parseInt(this.form.cook_time) || 0;
            this.form.total_time = prep + cook || '';
        },

        filteredIngredients(search) {
            if (!search) return [];
            const term = search.toLowerCase();
            return this.ingredients.filter(ing =>
                ing.name.toLowerCase().includes(term) ||
                (ing.name_dv && ing.name_dv.includes(search))
            ).slice(0, 8);
        },

        filteredUnits(search) {
            if (!search) return this.units.slice(0, 10);
            const term = search.toLowerCase();
            return this.units.filter(unit =>
                unit.name.toLowerCase().includes(term) ||
                (unit.abbreviation && unit.abbreviation.toLowerCase().includes(term)) ||
                (unit.name_dv && unit.name_dv.includes(search))
            ).slice(0, 10);
        },

        // Ingredient management
        addIngredientGroup() {
            this.form.ingredients.push({
                group_name: '',
                items: [{ ingredient: '', quantity: '', unit: '', prep_note: '' }]
            });
        },

        removeIngredientGroup(index) {
            if (this.form.ingredients.length > 1) {
                this.form.ingredients.splice(index, 1);
            }
        },

        addIngredient(groupIndex) {
            this.form.ingredients[groupIndex].items.push({
                ingredient: '', quantity: '', unit: '', prep_note: ''
            });
        },

        removeIngredient(groupIndex, itemIndex) {
            if (this.form.ingredients[groupIndex].items.length > 1) {
                this.form.ingredients[groupIndex].items.splice(itemIndex, 1);
            }
        },

        // Instruction management
        addInstructionGroup() {
            this.form.instructions.push({
                group_name: '',
                steps: ['']
            });
        },

        removeInstructionGroup(index) {
            if (this.form.instructions.length > 1) {
                this.form.instructions.splice(index, 1);
            }
        },

        addInstruction(groupIndex) {
            this.form.instructions[groupIndex].steps.push('');
        },

        removeInstruction(groupIndex, stepIndex) {
            if (this.form.instructions[groupIndex].steps.length > 1) {
                this.form.instructions[groupIndex].steps.splice(stepIndex, 1);
            }
        },

        // Child recipes for composite meals
        addChildRecipe() {
            this.form.child_recipes.push({
                recipe_name: '',
                description: '',
                ingredients: [],
                instructions: []
            });
        },

        removeChildRecipe(index) {
            if (this.form.child_recipes.length > 2) {
                this.form.child_recipes.splice(index, 1);
            }
        },

        // Image handling
        handleImageSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.processImage(file);
            }
        },

        handleImageDrop(event) {
            this.dragover = false;
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                this.processImage(file);
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.imageInput.files = dt.files;
            }
        },

        processImage(file) {
            this.imageFile = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        removeImage() {
            this.imagePreview = null;
            this.imageFile = null;
            this.$refs.imageInput.value = '';
        },

        // Helper to get clean error key for display
        getCleanErrorKey(key) {
            // Map field names to user-friendly labels
            const fieldMap = {
                'submitter_name': 'Name',
                'submitter_email': 'Email',
                'submitter_phone': 'Phone',
                'chef_name': 'Chef Name',
                'recipe_name': 'Recipe Name',
                'description': 'Description',
                'prep_time': 'Prep Time',
                'cook_time': 'Cook Time',
                'servings': 'Servings',
                'categories': 'Categories',
                'ingredients': 'Ingredients',
                'instructions': 'Instructions',
                'image': 'Image',
                'child_recipes': 'Included Recipes'
            };

            // Check for direct match
            if (fieldMap[key]) return fieldMap[key];

            // Handle nested keys like "ingredients.0.items.0.ingredient"
            const parts = key.split('.');
            const baseField = parts[0];

            if (baseField === 'ingredients') return 'Ingredients';
            if (baseField === 'instructions') return 'Instructions';
            if (baseField === 'child_recipes') return 'Included Recipes';

            return key;
        },

        // Check if a field has an error
        hasError(field) {
            if (this.rawErrors[field]) return true;
            // Check for nested errors
            return Object.keys(this.rawErrors).some(key => key.startsWith(field));
        },

        // Form submission
        async submitForm() {
            // Check authentication first
            if (!this.requireAuth()) {
                return;
            }

            this.submitting = true;
            this.errors = {};

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('submission_type', this.mode);
            // Use form data for name (user can edit), authUser email (fixed)
            formData.append('submitter_name', this.form.submitter_name);
            formData.append('submitter_email', this.authUser ? this.authUser.email : this.form.submitter_email);

            // Include avatar if user uploaded a new one
            if (this.avatarFile) {
                formData.append('submitter_avatar', this.avatarFile);
            }
            formData.append('submitter_phone', this.form.submitter_phone || '');
            formData.append('is_chef', this.form.is_chef ? '1' : '0');
            formData.append('chef_name', this.form.chef_name || '');
            formData.append('recipe_name', this.form.recipe_name);
            formData.append('headline', this.form.headline);
            formData.append('description', this.form.description);
            formData.append('prep_time', this.form.prep_time || '');
            formData.append('cook_time', this.form.cook_time || '');
            formData.append('servings', this.form.servings || '');

            // Categories
            this.form.categories.forEach((cat, i) => {
                formData.append(`categories[${i}]`, cat);
            });

            // Meal times
            this.form.meal_times.forEach((time, i) => {
                formData.append(`meal_times[${i}]`, time);
            });

            // Ingredients
            this.form.ingredients.forEach((group, gi) => {
                formData.append(`ingredients[${gi}][group_name]`, group.group_name || '');
                group.items.forEach((item, ii) => {
                    formData.append(`ingredients[${gi}][items][${ii}][ingredient]`, item.ingredient);
                    formData.append(`ingredients[${gi}][items][${ii}][quantity]`, item.quantity || '');
                    formData.append(`ingredients[${gi}][items][${ii}][unit]`, item.unit || '');
                    formData.append(`ingredients[${gi}][items][${ii}][prep_note]`, item.prep_note || '');
                });
            });

            // Instructions
            this.form.instructions.forEach((group, gi) => {
                formData.append(`instructions[${gi}][group_name]`, group.group_name || '');
                group.steps.forEach((step, si) => {
                    formData.append(`instructions[${gi}][steps][${si}]`, step);
                });
            });

            // Child recipes for composite
            if (this.mode === 'composite') {
                this.form.child_recipes.forEach((recipe, i) => {
                    formData.append(`child_recipes[${i}][recipe_name]`, recipe.recipe_name);
                    formData.append(`child_recipes[${i}][description]`, recipe.description);
                });
            }

            // Image
            if (this.imageFile) {
                formData.append('image', this.imageFile);
            }

            try {
                const response = await fetch('{{ route('recipes.submit.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.location.href = '{{ route('recipes.submit.success') }}';
                } else if (response.status === 422) {
                    const data = await response.json();
                    this.rawErrors = data.errors || {};

                    // Flatten and simplify error messages for display
                    this.errors = {};
                    Object.keys(this.rawErrors).forEach(key => {
                        const message = Array.isArray(this.rawErrors[key]) ? this.rawErrors[key][0] : this.rawErrors[key];
                        // Use a cleaner key for display (e.g., "ingredients.0.items.0.ingredient" -> "Ingredient")
                        const cleanKey = this.getCleanErrorKey(key);
                        if (!this.errors[cleanKey]) {
                            this.errors[cleanKey] = message;
                        }
                    });

                    // Scroll to error summary
                    this.$nextTick(() => {
                        const errorSummary = document.querySelector('.bg-red-50');
                        if (errorSummary) {
                            errorSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                } else {
                    alert('An error occurred. Please try again.');
                }
            } catch (error) {
                console.error('Submission error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>
@endpush
@endsection
