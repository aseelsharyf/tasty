<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// Static template page (old home design)
Route::get('/template', function () {
    return view('home');
})->name('template');

// CMS Routes
Route::prefix('cms')->group(base_path('routes/cms.php'));

// Dynamic Page Routes from database
Route::get('/', [PageController::class, 'home'])->name('home');

// Category routes
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');

// Tag routes
Route::get('/tag/{tag:slug}', [TagController::class, 'show'])->name('tag.show');

// Post routes (category/post pattern for SEO)
Route::get('/{category}/{post}', [PostController::class, 'show'])
    ->name('post.show')
    ->where('category', '^(?!cms|template|storage|category|tag).*$');

// Catch-all page route (must be last)
Route::get('/{slug}', [PageController::class, 'show'])->name('page.show')
    ->where('slug', '^(?!cms|template|storage|category|tag).*$');
