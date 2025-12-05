<?php

use App\Http\Controllers\Cms\AuthController;
use App\Http\Controllers\Cms\CategoryController;
use App\Http\Controllers\Cms\CommentBanController;
use App\Http\Controllers\Cms\CommentController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\LanguageController;
use App\Http\Controllers\Cms\MediaController;
use App\Http\Controllers\Cms\MediaFolderController;
use App\Http\Controllers\Cms\PostController;
use App\Http\Controllers\Cms\RoleController;
use App\Http\Controllers\Cms\SettingsController;
use App\Http\Controllers\Cms\TagController;
use App\Http\Controllers\Cms\UserController;
use App\Models\Language;
use Illuminate\Support\Facades\Route;

// CMS Authentication Routes (Guest only)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('cms.login');
    Route::post('login', [AuthController::class, 'login'])->name('cms.login.store');
});

// CMS Protected Routes (Authenticated + CMS Access)
Route::middleware(['auth', 'cms'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('cms.logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('cms.dashboard');

    // Users Management
    Route::middleware('permission:users.view')->group(function () {
        Route::resource('users', UserController::class)->names([
            'index' => 'cms.users.index',
            'create' => 'cms.users.create',
            'store' => 'cms.users.store',
            'show' => 'cms.users.show',
            'edit' => 'cms.users.edit',
            'update' => 'cms.users.update',
            'destroy' => 'cms.users.destroy',
        ]);
    });

    // Roles Management
    Route::middleware('permission:roles.view')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show'])->names([
            'index' => 'cms.roles.index',
            'create' => 'cms.roles.create',
            'store' => 'cms.roles.store',
            'edit' => 'cms.roles.edit',
            'update' => 'cms.roles.update',
            'destroy' => 'cms.roles.destroy',
        ]);
    });

    // Settings
    Route::middleware('permission:settings.view')->group(function () {
        Route::get('settings/{tab?}', [SettingsController::class, 'index'])
            ->name('cms.settings')
            ->where('tab', 'general|seo|opengraph|favicons|social');
        Route::post('settings', [SettingsController::class, 'update'])->name('cms.settings.update');
        Route::get('settings/post-types', [SettingsController::class, 'postTypes'])->name('cms.settings.post-types');
        Route::put('settings/post-types', [SettingsController::class, 'updatePostTypes'])->name('cms.settings.post-types.update');
        Route::get('settings/media', [SettingsController::class, 'media'])->name('cms.settings.media');
        Route::put('settings/media', [SettingsController::class, 'updateMedia'])->name('cms.settings.media.update');

        // Languages
        Route::get('settings/languages', [SettingsController::class, 'languages'])->name('cms.settings.languages');
        Route::post('settings/languages', [SettingsController::class, 'storeLanguage'])->name('cms.settings.languages.store');
        Route::put('settings/languages/{language}', [SettingsController::class, 'updateLanguage'])->name('cms.settings.languages.update');
        Route::delete('settings/languages/{language}', [SettingsController::class, 'destroyLanguage'])->name('cms.settings.languages.destroy');
        Route::post('settings/languages/reorder', [SettingsController::class, 'reorderLanguages'])->name('cms.settings.languages.reorder');
    });

    // Languages API (for fetching available languages)
    Route::get('languages', [LanguageController::class, 'index'])->name('cms.languages.index');

    // Posts Management
    Route::middleware('permission:posts.view')->group(function () {
        // Language-specific post listing
        Route::get('posts/{language}', [PostController::class, 'index'])
            ->name('cms.posts.index')
            ->where('language', '[a-z]{2,5}');

        // Create post (language required)
        Route::get('posts/{language}/create', [PostController::class, 'create'])
            ->name('cms.posts.create')
            ->where('language', '[a-z]{2,5}');
        Route::post('posts/{language}', [PostController::class, 'store'])
            ->name('cms.posts.store')
            ->where('language', '[a-z]{2,5}');

        // Edit/Update/Delete (language-agnostic since post already has language)
        Route::get('posts/{language}/{post}/edit', [PostController::class, 'edit'])
            ->name('cms.posts.edit')
            ->where('language', '[a-z]{2,5}');
        Route::put('posts/{language}/{post}', [PostController::class, 'update'])
            ->name('cms.posts.update')
            ->where('language', '[a-z]{2,5}');
        Route::delete('posts/{language}/{post}', [PostController::class, 'destroy'])
            ->name('cms.posts.destroy')
            ->where('language', '[a-z]{2,5}');

        // Additional post actions
        Route::post('posts/{language}/{post}/publish', [PostController::class, 'publish'])
            ->name('cms.posts.publish')
            ->where('language', '[a-z]{2,5}');
        Route::post('posts/{language}/{post}/unpublish', [PostController::class, 'unpublish'])
            ->name('cms.posts.unpublish')
            ->where('language', '[a-z]{2,5}');
        Route::post('posts/{language}/{post}/restore', [PostController::class, 'restore'])
            ->name('cms.posts.restore')
            ->where('language', '[a-z]{2,5}');
        Route::delete('posts/{language}/{post}/force', [PostController::class, 'forceDelete'])
            ->name('cms.posts.forceDelete')
            ->where('language', '[a-z]{2,5}');
    });

    // Categories Management
    Route::middleware('permission:categories.view')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show'])->names([
            'index' => 'cms.categories.index',
            'create' => 'cms.categories.create',
            'store' => 'cms.categories.store',
            'edit' => 'cms.categories.edit',
            'update' => 'cms.categories.update',
            'destroy' => 'cms.categories.destroy',
        ]);
        Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('cms.categories.reorder');
        Route::delete('categories/bulk', [CategoryController::class, 'bulkDestroy'])->name('cms.categories.bulk-destroy');
    });

    // Tags Management
    Route::middleware('permission:tags.view')->group(function () {
        Route::resource('tags', TagController::class)->except(['show'])->names([
            'index' => 'cms.tags.index',
            'create' => 'cms.tags.create',
            'store' => 'cms.tags.store',
            'edit' => 'cms.tags.edit',
            'update' => 'cms.tags.update',
            'destroy' => 'cms.tags.destroy',
        ]);
        Route::delete('tags/bulk', [TagController::class, 'bulkDestroy'])->name('cms.tags.bulk-destroy');
    });

    // Comments Management
    Route::middleware('permission:comments.view')->group(function () {
        Route::get('comments', [CommentController::class, 'index'])->name('cms.comments.index');
        Route::get('comments/queue', [CommentController::class, 'queue'])->name('cms.comments.queue');
        Route::get('comments/statistics', [CommentController::class, 'statistics'])->name('cms.comments.statistics');
        Route::get('comments/search-posts', [CommentController::class, 'searchPosts'])->name('cms.comments.search-posts');
        Route::post('comments/bulk', [CommentController::class, 'bulkAction'])->name('cms.comments.bulk');

        // Comment Bans (must be before {comment} wildcard)
        Route::get('comments/bans', [CommentBanController::class, 'index'])->name('cms.comments.bans');
        Route::post('comments/bans', [CommentBanController::class, 'store'])->name('cms.comments.bans.store');
        Route::delete('comments/bans/{ban}', [CommentBanController::class, 'destroy'])->name('cms.comments.bans.destroy');

        // Single comment actions
        Route::get('comments/{comment}', [CommentController::class, 'show'])->name('cms.comments.show');
        Route::put('comments/{comment}', [CommentController::class, 'update'])->name('cms.comments.update');
        Route::post('comments/{comment}/approve', [CommentController::class, 'approve'])->name('cms.comments.approve');
        Route::post('comments/{comment}/spam', [CommentController::class, 'spam'])->name('cms.comments.spam');
        Route::post('comments/{comment}/trash', [CommentController::class, 'trash'])->name('cms.comments.trash');
        Route::post('comments/{comment}/restore', [CommentController::class, 'restore'])->name('cms.comments.restore');
        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('cms.comments.destroy');
    });

    // Media Management
    Route::middleware('permission:media.view')->group(function () {
        Route::get('media', [MediaController::class, 'index'])->name('cms.media.index');
        Route::get('media/picker', [MediaController::class, 'picker'])->name('cms.media.picker');
        Route::get('media/trashed', [MediaController::class, 'trashed'])->name('cms.media.trashed');
        Route::get('media/{media}', [MediaController::class, 'show'])->name('cms.media.show');

        Route::middleware('permission:media.upload')->group(function () {
            Route::post('media', [MediaController::class, 'store'])->name('cms.media.store');
        });

        Route::middleware('permission:media.edit')->group(function () {
            Route::put('media/{media}', [MediaController::class, 'update'])->name('cms.media.update');
            Route::post('media/bulk', [MediaController::class, 'bulkAction'])->name('cms.media.bulk');
        });

        Route::middleware('permission:media.delete')->group(function () {
            Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('cms.media.destroy');
            Route::post('media/{uuid}/restore', [MediaController::class, 'restore'])->name('cms.media.restore');
            Route::delete('media/{uuid}/force', [MediaController::class, 'forceDelete'])->name('cms.media.force-delete');
        });

        // Media Folders
        Route::get('media-folders', [MediaFolderController::class, 'index'])->name('cms.media-folders.index');
        Route::get('media-folders/list', [MediaFolderController::class, 'list'])->name('cms.media-folders.list');
        Route::post('media-folders', [MediaFolderController::class, 'store'])->name('cms.media-folders.store');
        Route::put('media-folders/{folder}', [MediaFolderController::class, 'update'])->name('cms.media-folders.update');
        Route::delete('media-folders/{folder}', [MediaFolderController::class, 'destroy'])->name('cms.media-folders.destroy');
    });
});
