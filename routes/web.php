<?php

use App\Livewire\CreateUser;
use App\Livewire\EditRole;
use App\Livewire\EditUser;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('groups', 'groups')
    ->middleware(['auth', 'verified'])
    ->name('groups');

Route::view('pilgrims', 'pilgrims.index')
    ->middleware(['auth', 'verified'])
    ->name('pilgrims.index');

Route::view('my-pilgrim-profile', 'pilgrims.profile')
    ->middleware(['auth', 'verified'])
    ->name('pilgrims.profile');

Route::view('hotels', 'hotels.index')
    ->middleware(['auth', 'verified'])
    ->name('hotels.index');

Route::view('my-hotel', 'hotels.my-hotel')
    ->middleware(['auth', 'verified'])
    ->name('hotels.my');

Route::view('permission-settings', 'permission-settings')
    ->middleware(['auth', 'verified'])
    ->name('permission-settings');

Route::view('role-settings', 'role-settings')
    ->middleware(['auth', 'verified'])
    ->name('role-settings');

Route::view('user-settings', 'user-settings')
    ->middleware(['auth', 'verified'])
    ->name('users.index');

Route::get('/roles/{role}/edit', EditRole::class)->name('roles.edit');

Route::get('/users/create', CreateUser::class)->name('users.create');

Route::get('/users/{user}/edit', EditUser::class)->name('users.edit');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
