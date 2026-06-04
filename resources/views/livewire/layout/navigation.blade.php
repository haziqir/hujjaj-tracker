<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="h-screen w-64 bg-white border-r border-gray-200 flex flex-col">
    <div class="h-16 flex items-center px-6 border-b border-gray-100">
        <a href="{{ route('dashboard') }}" wire:navigate>
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 space-y-1">
        <div class="px-3">
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                {{ __('Dashboard') }}
            </x-nav-link>
        </div>

        <div class="px-3">
            <x-nav-link :href="route('groups')" :active="request()->routeIs('groups')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                {{ __('Kumpulan Haji') }}
            </x-nav-link>
        </div>

        <div class="px-3">
            <x-nav-link :href="route('settings')" :active="request()->routeIs('settings')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                {{ __('Tetapan') }}
            </x-nav-link>
        </div>
    </nav>

    <div class="p-4 border-t border-gray-200 space-y-2">
        <div class="px-2 pb-2">
            <div class="font-medium text-base text-gray-800">{{ auth()->user()->name }}</div>
            <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
        </div>

        <x-dropdown-link :href="route('profile')" wire:navigate>
            {{ __('Profile') }}
        </x-dropdown-link>

        <button wire:click="logout" class="w-full text-start">
            <x-dropdown-link>
                {{ __('Log Out') }}
            </x-dropdown-link>
        </button>
    </div>
</div>
