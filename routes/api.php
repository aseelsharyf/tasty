<?php

use App\Http\Controllers\Api\PostsController;
use Illuminate\Support\Facades\Route;

Route::post('/posts/load-more', [PostsController::class, 'loadMore'])->name('api.posts.load-more');
