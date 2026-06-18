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

@php
    $user = auth()->user();
    $isAdmin = $user?->roles()->where('name', 'super-admin')->exists() ?? false;
    $isStaff = $user?->roles()->where('name', 'staff')->exists() ?? false;
    $isGroupLeader = $user?->roles()->where('name', 'group-leader')->exists() ?? false;
    $isPilgrim = $user?->roles()->where('name', 'pilgrim')->exists() ?? false;
    $homeRoute = $isPilgrim ? route('pilgrims.dashboard') : route('dashboard');
@endphp

<div class="h-screen w-64 bg-white border-r border-gray-200 flex flex-col">
    <div class="h-20 flex items-center px-5 border-b border-gray-100">
        <a href="{{ $homeRoute }}" wire:navigate class="flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-950 text-white [&_svg]:h-7 [&_svg]:w-7 [&_svg]:fill-white [&_svg]:text-white [&_path]:fill-white [&_path]:stroke-white">
                <x-application-logo class="block h-7 w-7 fill-white text-white" />
            </span>
            <span>
                <span class="block text-base font-bold leading-5 text-gray-950">Hujjaj Tracker</span>
                <span class="mt-1 block text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-500">Pilgrim Safety System</span>
            </span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 space-y-1">
        @if ($isAdmin)
            <div class="px-3">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Dashboard') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('pilgrims.index')" :active="request()->routeIs('pilgrims.index')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Pilgrims') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('groups')" :active="request()->routeIs('groups')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Groups') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('hotels.index')" :active="request()->routeIs('hotels.index')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Hotels') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('zones.index')" :active="request()->routeIs('zones.index')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Zones') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('sos-alerts.index')" :active="request()->routeIs('sos-alerts.index')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('SOS Alerts') }}
                </x-nav-link>
            </div>

            <div x-data="{ open: {{ request()->routeIs(['permission-settings', 'role-settings', 'roles.edit', 'users.index', 'users.create', 'users.edit']) ? 'true' : 'false' }} }" class="px-3">
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
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.index', 'users.create', 'users.edit')" class="w-full flex justify-start px-8 py-3" wire:navigate>
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
        @endif

        @if ($isStaff && ! $isAdmin)
            <div class="px-3">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Map') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('sos-alerts.index')" :active="request()->routeIs('sos-alerts.index') && request('status') !== 'assigned'" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Alerts') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('sos-alerts.index', ['status' => 'assigned'])" :active="request()->routeIs('sos-alerts.index') && request('status') === 'assigned'" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Assignments') }}
                </x-nav-link>
            </div>
        @endif

        @if ($isGroupLeader && ! $isAdmin && ! $isStaff)
            <div class="px-3">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Dashboard') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('groups')" :active="request()->routeIs('groups')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('My Group') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('groups.location')" :active="request()->routeIs('groups.location')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Group Map') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Notifications') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('sos-alerts.index')" :active="request()->routeIs('sos-alerts.index')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Group SOS') }}
                </x-nav-link>
            </div>
        @endif

        @if ($isPilgrim)
            <div class="px-3">
                <x-nav-link :href="route('pilgrims.dashboard')" :active="request()->routeIs('pilgrims.dashboard')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('Dashboard') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('locations.my')" :active="request()->routeIs('locations.my')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('My Location') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('hotels.my')" :active="request()->routeIs('hotels.my')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('My Hotel') }}
                </x-nav-link>
            </div>

            <div class="px-3">
                <x-nav-link :href="route('pilgrims.profile')" :active="request()->routeIs('pilgrims.profile')" class="w-full flex justify-start px-4 py-3" wire:navigate>
                    {{ __('My Pilgrim Profile') }}
                </x-nav-link>
            </div>
        @endif
    </nav>

</div>
