<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OgImageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecipeSubmissionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// Static template page (old home design)
Route::get('/template', function () {
    return view('template');
})->name('template');

// CMS Routes - domain-based for live.tastymaldives.com (no /cms prefix)
Route::domain(config('app.cms_domain'))->group(function () {
    Route::group([], base_path('routes/cms.php'));
});

// CMS Routes - path-based for other hosts (/cms prefix)
Route::prefix('cms')->group(base_path('routes/cms.php'));

// Homepage - uses configurable layout
Route::get('/', HomeController::class)->name('home');

// Category routes
Route::get('/category/{category:slug}', [
    CategoryController::class,
    'show',
])->name('category.show');

// Tag routes
Route::get('/tag/{tag:slug}', [TagController::class, 'show'])->name('tag.show');

// Author routes
Route::get('/author/{author:username}', [
    AuthorController::class,
    'show',
])->name('author.show');

// Search routes
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/api/search', [SearchController::class, 'suggestions'])->name(
    'search.suggestions',
);

// Comment routes
Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');

// Social Authentication routes
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/auth/logout', [SocialAuthController::class, 'logout'])->name('auth.logout');
Route::get('/api/auth/user', [SocialAuthController::class, 'user'])->name('api.auth.user');
Route::post('/api/auth/profile', [SocialAuthController::class, 'updateProfile'])->name('api.auth.profile.update');
Route::post('/api/auth/profile/remove-avatar', [SocialAuthController::class, 'removeAvatar'])->name('api.auth.profile.remove-avatar');

// Debug route - remove in production
Route::get('/auth/debug', function () {
    $user = auth()->user();

    return response()->json([
        'authenticated' => auth()->check(),
        'user' => $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'google_id' => $user->google_id,
        ] : null,
        'session_id' => session()->getId(),
    ]);
});

// OG Image preview (for debugging)
Route::get('/og-preview/{post:slug}', [OgImageController::class, 'preview'])->name('og.preview');
Route::get('/og-html/{post:slug}', [OgImageController::class, 'renderHtml'])->name('og.html');
Route::get('/og-test', [OgImageController::class, 'testPage'])->name('og.test');

// Product routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/go/{product:slug}', [ProductController::class, 'redirect'])->name('products.redirect');
Route::get('/products/category/{category:slug}', [ProductController::class, 'byCategory'])->name('products.category');
Route::get('/products/tag/{tag:slug}', [ProductController::class, 'byTag'])->name('products.tag');
Route::get('/products/{store:slug}', [ProductController::class, 'byStore'])->name('products.store');
Route::get('/products/{store:slug}/load-more', [ProductController::class, 'loadMore'])->name('products.store.loadMore');

// Recipe submission routes
Route::get('/submit-recipe', [RecipeSubmissionController::class, 'create'])->name('recipes.submit');
Route::post('/submit-recipe', [RecipeSubmissionController::class, 'store'])->name('recipes.submit.store');
Route::get('/submit-recipe/success', [RecipeSubmissionController::class, 'success'])->name('recipes.submit.success');
Route::get('/api/ingredients', [RecipeSubmissionController::class, 'apiIngredients'])->name('api.ingredients');
Route::get('/api/units', [RecipeSubmissionController::class, 'apiUnits'])->name('api.units');

// Post routes (category/post pattern for SEO)
Route::get('/{category}/{post}', [PostController::class, 'show'])
    ->name('post.show')
    ->where('category', '^(?!cms|template|storage|category|tag|api).*$');

// Catch-all page route (must be last)
Route::get('/{slug}', [PageController::class, 'show'])
    ->name('page.show')
    ->where('slug', '^(?!cms|template|storage|category|tag).*$');
