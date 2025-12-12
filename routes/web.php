<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

// Static template page (old home design)
Route::get('/template', function () {
    return view('home');
})->name('template');

// CMS Routes
Route::prefix('cms')->group(base_path('routes/cms.php'));

// Dynamic Page Routes from database
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/{slug}', [PageController::class, 'show'])->name('page.show')
    ->where('slug', '^(?!cms|template|storage).*$');
