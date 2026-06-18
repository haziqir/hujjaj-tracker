<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Hujjaj Tracker</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans">
        @php
            $homeUrl = auth()->check() && auth()->user()->roles()->where('name', 'pilgrim')->exists()
                ? route('pilgrims.dashboard')
                : url('/dashboard');
        @endphp

        <main class="relative min-h-screen overflow-hidden bg-gray-950 text-white">
            <img
                class="absolute inset-0 h-full w-full object-cover"
                src="{{ asset('storage/images/kaabah.jpg') }}"
                alt="Kaabah in Makkah"
            />
            <div class="absolute inset-0 bg-gradient-to-r from-gray-950/95 via-gray-950/70 to-gray-950/25"></div>
            <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-gray-950/90 to-transparent"></div>

            <div class="relative flex min-h-screen flex-col">
                <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/20 backdrop-blur">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7">
                                <path fill-rule="evenodd" d="M11.622 1.602a.75.75 0 0 1 .756 0l2.25 1.313a.75.75 0 0 1-.756 1.295L12 3.118 10.128 4.21a.75.75 0 1 1-.756-1.295l2.25-1.313ZM5.898 5.81a.75.75 0 0 1-.27 1.025l-1.14.665 1.14.665a.75.75 0 1 1-.756 1.295L3.75 8.806v.944a.75.75 0 0 1-1.5 0V7.5a.75.75 0 0 1 .372-.648l2.25-1.312a.75.75 0 0 1 1.026.27Zm12.204 0a.75.75 0 0 1 1.026-.27l2.25 1.312a.75.75 0 0 1 .372.648v2.25a.75.75 0 0 1-1.5 0v-.944l-1.122.654a.75.75 0 1 1-.756-1.295l1.14-.665-1.14-.665a.75.75 0 0 1-.27-1.025Zm-9 5.25a.75.75 0 0 1 1.026-.27L12 11.882l1.872-1.092a.75.75 0 1 1 .756 1.295l-1.878 1.096V15a.75.75 0 0 1-1.5 0v-1.82l-1.878-1.095a.75.75 0 0 1-.27-1.025ZM3 13.5a.75.75 0 0 1 .75.75v1.82l1.878 1.095a.75.75 0 1 1-.756 1.295l-2.25-1.312a.75.75 0 0 1-.372-.648v-2.25A.75.75 0 0 1 3 13.5Zm18 0a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-.372.648l-2.25 1.312a.75.75 0 1 1-.756-1.295l1.878-1.096V14.25a.75.75 0 0 1 .75-.75Zm-9 5.25a.75.75 0 0 1 .75.75v.944l1.122-.654a.75.75 0 1 1 .756 1.295l-2.25 1.313a.75.75 0 0 1-.756 0l-2.25-1.313a.75.75 0 1 1 .756-1.295l1.122.654V19.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>
                            <span class="block text-lg font-bold leading-5 tracking-wide">Hujjaj Tracker</span>
                            <span class="mt-1 block text-xs font-medium uppercase tracking-[0.2em] text-white/60">Pilgrim Safety System</span>
                        </span>
                    </a>

                    @if (Route::has('login'))
                        <livewire:welcome.navigation />
                    @endif
                </header>

                <section class="mx-auto grid w-full max-w-7xl flex-1 items-center gap-10 px-6 pb-12 pt-8 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-white/80 backdrop-blur">
                            Real-time support for Hajj movement and assistance
                        </div>

                        <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-tight tracking-normal text-white sm:text-5xl lg:text-6xl">
                            Detect lost pilgrims and guide help faster.
                        </h1>

                        <p class="mt-5 max-w-2xl text-base leading-7 text-white/75 sm:text-lg">
                            A web-based prototype for monitoring pilgrim locations, SOS alerts, group movement, hotels, zones, and staff assistance from one operational dashboard.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ $homeUrl }}" class="inline-flex items-center justify-center rounded-md bg-white px-5 py-3 text-sm font-bold uppercase tracking-wide text-gray-950 shadow-lg shadow-black/20 transition hover:bg-gray-100">
                                    Open Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md bg-white px-5 py-3 text-sm font-bold uppercase tracking-wide text-gray-950 shadow-lg shadow-black/20 transition hover:bg-gray-100">
                                    Login to System
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md border border-white/25 bg-white/10 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white backdrop-blur transition hover:bg-white/20">
                                        Register Account
                                    </a>
                                @endif
                            @endauth
                        </div>

                        <div class="mt-10 grid max-w-2xl gap-3 sm:grid-cols-3">
                            <div class="rounded-lg border border-white/20 bg-white/10 p-4 backdrop-blur">
                                <div class="text-2xl font-bold text-white">SOS</div>
                                <div class="mt-1 text-sm leading-5 text-white/70">Emergency alert with GPS location</div>
                            </div>
                            <div class="rounded-lg border border-white/20 bg-white/10 p-4 backdrop-blur">
                                <div class="text-2xl font-bold text-white">Map</div>
                                <div class="mt-1 text-sm leading-5 text-white/70">Live dashboard with markers and zones</div>
                            </div>
                            <div class="rounded-lg border border-white/20 bg-white/10 p-4 backdrop-blur">
                                <div class="text-2xl font-bold text-white">Group</div>
                                <div class="mt-1 text-sm leading-5 text-white/70">Leader location for separated members</div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden lg:block">
                        <div class="ml-auto max-w-md rounded-lg border border-white/20 bg-white/10 p-5 shadow-2xl shadow-black/30 backdrop-blur-md">
                            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                                <div>
                                    <div class="text-sm font-medium text-white/60">System Status</div>
                                    <div class="mt-1 text-xl font-bold text-white">Demo Ready</div>
                                </div>
                                <span class="rounded-full bg-emerald-400/20 px-3 py-1 text-sm font-semibold text-emerald-200">Active</span>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div class="flex items-start gap-3">
                                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                    <div>
                                        <div class="font-semibold text-white">Lost pilgrim detection</div>
                                        <div class="text-sm text-white/60">Manual SOS and auto alert checks</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-blue-400"></span>
                                    <div>
                                        <div class="font-semibold text-white">Staff assignment workflow</div>
                                        <div class="text-sm text-white/60">Accept, assign, resolve assistance tasks</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                    <div>
                                        <div class="font-semibold text-white">Geofence monitoring</div>
                                        <div class="text-sm text-white/60">Makkah and Madinah zone awareness</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
