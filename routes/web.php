<?php

use App\Livewire\CreateUser;
use App\Livewire\EditRole;
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

Route::view('user-settings', 'user-settings')
    ->middleware(['auth', 'verified'])
    ->name('user-settings');

Route::get('/roles/{role}/edit', EditRole::class)->name('roles.edit');

Route::get('/users/create', CreateUser::class)->name('users.create');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
