<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// Static template page (old home design)
Route::get('/template', function () {
    return view('template');
})->name('template');

// CMS Routes
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

// Product routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/go/{product:slug}', [ProductController::class, 'redirect'])->name('products.redirect');
Route::get('/products/category/{category:slug}', [ProductController::class, 'byCategory'])->name('products.category');
Route::get('/products/{store:slug}', [ProductController::class, 'byStore'])->name('products.store');
Route::get('/products/{store:slug}/load-more', [ProductController::class, 'loadMore'])->name('products.store.loadMore');

// Post routes (category/post pattern for SEO)
Route::get('/{category}/{post}', [PostController::class, 'show'])
    ->name('post.show')
    ->where('category', '^(?!cms|template|storage|category|tag|api).*$');

// Catch-all page route (must be last)
Route::get('/{slug}', [PageController::class, 'show'])
    ->name('page.show')
    ->where('slug', '^(?!cms|template|storage|category|tag).*$');
