<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('groups', 'groups')
    ->middleware(['auth', 'verified'])
    ->name('groups');

Route::view('permission-settings', 'permission-settings')
    ->middleware(['auth', 'verified'])
    ->name('permission-settings');

Route::view('role-settings', 'role-settings')
    ->middleware(['auth', 'verified'])
    ->name('role-settings');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
