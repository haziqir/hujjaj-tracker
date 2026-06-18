<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Hujjaj Tracker') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <main class="relative min-h-screen overflow-hidden bg-gray-950">
            <img
                class="absolute inset-0 h-full w-full object-cover"
                src="{{ asset('storage/images/kaabah.jpg') }}"
                alt="Kaabah in Makkah"
            />
            <div class="absolute inset-0 bg-gradient-to-r from-gray-950/95 via-gray-950/75 to-gray-950/40"></div>
            <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-gray-950/95 to-transparent"></div>

            <div class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 lg:px-8">
                <header class="flex items-center justify-between">
                    <a href="/" wire:navigate class="flex items-center gap-3 text-white">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20 backdrop-blur">
                            <x-application-logo class="h-7 w-7 fill-current text-white" />
                        </span>
                        <span>
                            <span class="block text-lg font-bold leading-5">Hujjaj Tracker</span>
                            <span class="mt-1 block text-xs font-medium uppercase tracking-[0.2em] text-white/60">Pilgrim Safety System</span>
                        </span>
                    </a>

                    <a href="/" wire:navigate class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                        Home
                    </a>
                </header>

                <section class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[0.95fr_1.05fr]">
                    <div class="hidden max-w-xl text-white lg:block">
                        <div class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-white/80 backdrop-blur">
                            Secure access for Hajj operations
                        </div>
                        <h1 class="mt-6 text-5xl font-extrabold leading-tight tracking-normal">
                            Welcome back to your pilgrim safety dashboard.
                        </h1>
                        <p class="mt-5 text-lg leading-8 text-white/70">
                            Monitor SOS alerts, group locations, zones, hotels, and assistance tasks from one role-based workspace.
                        </p>
                    </div>

                    <div class="mx-auto w-full max-w-md">
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
