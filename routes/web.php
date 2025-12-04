<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/about', function () {
    return view('pages.about');
})->name('about');

Route::get('/contact', function () {
    return view('pages.contact');
})->name('contact');

Route::get('/editorial-policy', function () {
    return view('pages.editorial-policy');
})->name('editorial-policy');

Route::get('/work-with-us', function () {
    return view('pages.work-with-us');
})->name('work-with-us');

Route::get('/submit-story', function () {
    return view('pages.submit-story');
})->name('submit-story');
