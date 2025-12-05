<?php

use App\Http\Controllers\Cms\AuthController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\RoleController;
use App\Http\Controllers\Cms\SettingsController;
use App\Http\Controllers\Cms\UserController;
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
        Route::get('settings', [SettingsController::class, 'index'])->name('cms.settings');
        Route::put('settings', [SettingsController::class, 'update'])->name('cms.settings.update');
    });
});
