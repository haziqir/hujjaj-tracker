<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();
        $dashboardRoute = $user->roles()->whereIn('name', ['super-admin', 'group-leader', 'staff'])->exists()
            ? 'dashboard'
            : 'pilgrims.dashboard';

        $this->redirect(route($dashboardRoute, absolute: false), navigate: true);
    }
}; ?>

<div class="overflow-hidden rounded-lg border border-white/20 bg-white/95 shadow-2xl shadow-black/30 backdrop-blur">
    <div class="border-b border-gray-100 px-7 py-6">
        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Sign in</div>
        <h2 class="mt-2 text-2xl font-bold text-gray-950">Access Hujjaj Tracker</h2>
        <p class="mt-2 text-sm leading-6 text-gray-500">
            Use your assigned account to continue to your role dashboard.
        </p>
    </div>

    <div class="px-7 py-6">
        <x-auth-session-status class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" :status="session('status')" />

        <form wire:submit="login" class="space-y-5">
            <div>
                <x-input-label for="email" :value="__('Email Address')" class="text-gray-700" />
                <x-text-input
                    wire:model="form.email"
                    id="email"
                    class="mt-2 block w-full rounded-md border-gray-300 px-4 py-3 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="name@example.com"
                />
                <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <x-input-label for="password" :value="__('Password')" class="text-gray-700" />

                    @if (Route::has('password.request'))
                        <a class="text-sm font-medium text-gray-500 transition hover:text-gray-950" href="{{ route('password.request') }}" wire:navigate>
                            Forgot password?
                        </a>
                    @endif
                </div>

                <x-text-input
                    wire:model="form.password"
                    id="password"
                    class="mt-2 block w-full rounded-md border-gray-300 px-4 py-3 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                />
                <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
            </div>

            <label for="remember" class="flex items-center gap-2">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900" name="remember">
                <span class="text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>

            <button
                type="submit"
                class="flex w-full items-center justify-center rounded-md bg-gray-950 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-lg shadow-gray-950/20 transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-950 focus:ring-offset-2"
            >
                Login
            </button>
        </form>

        @if (Route::has('register'))
            <div class="mt-6 rounded-md bg-gray-50 px-4 py-3 text-center text-sm text-gray-600">
                Need an account?
                <a href="{{ route('register') }}" wire:navigate class="font-semibold text-gray-950 hover:underline">Register here</a>
            </div>
        @endif
    </div>
</div>
