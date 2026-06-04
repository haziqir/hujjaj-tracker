<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('groups', 'groups')
    ->middleware(['auth', 'verified'])
    ->name('groups');

Route::view('settings', 'settings')
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
