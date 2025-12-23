<?php

use App\Http\Controllers\Api\PostsController;
use App\Http\Controllers\SubscriberController;
use Illuminate\Support\Facades\Route;

Route::post('/posts/load-more', [PostsController::class, 'loadMore'])->name('api.posts.load-more');

Route::post('/subscribe', [SubscriberController::class, 'subscribe'])->name('api.subscribe');
