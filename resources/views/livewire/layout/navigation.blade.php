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
                {{ __('Hajj Groups') }}
            </x-nav-link>
        </div>

        <div x-data="{ open: {{ request()->routeIs(['permission-settings', 'role-settings', 'roles.edit', 'user-settings', 'users.create']) ? 'true' : 'false' }} }" class="px-3">
            <button @click="open = !open" class="w-full flex justify-between items-center px-4 py-3 text-gray-600 hover:text-gray-900 transition">
                <span>{{ __('Settings') }}</span>
                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2" class="mt-1 space-y-1">
                <x-nav-link :href="route('user-settings')" :active="request()->routeIs('user-settings', 'users.create')" class="w-full flex justify-start px-8 py-3" wire:navigate>
                    {{ __('Users') }}
                </x-nav-link>

                <x-nav-link :href="route('role-settings')" :active="request()->routeIs('role-settings', 'roles.edit')" class="w-full flex justify-start px-8 py-3" wire:navigate>
                    {{ __('Roles') }}
                </x-nav-link>

                <x-nav-link :href="route('permission-settings')" :active="request()->routeIs('permission-settings')" class="w-full flex justify-start px-8 py-3" wire:navigate>
                    {{ __('Permissions') }}
                </x-nav-link>
            </div>
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
