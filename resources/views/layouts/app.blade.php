<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @php
            $currentUser = auth()->user();
            $currentRole = $currentUser?->roles->first()?->name;
            $currentRoleLabel = $currentRole ? \Illuminate\Support\Str::headline($currentRole) : 'User';
        @endphp

        <div class="min-h-screen bg-gray-100 flex">
            <aside class="w-64 bg-white border-r border-gray-200 hidden md:block">
                <livewire:layout.navigation />
            </aside>

            <div class="flex-1 flex flex-col">
                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-4 px-4">
                            <div class="flex items-center justify-between gap-6">
                                <div>
                                    {{ $header }}
                                </div>

                                <div class="relative text-end" x-data="{ open: false }" @click.outside="open = false">
                                    <button type="button" @click="open = ! open" class="flex items-center gap-3 rounded-lg px-3 py-2 text-start transition hover:bg-gray-50">
                                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5Z" />
                                                <path d="M4 21c0-4.418 3.582-8 8-8s8 3.582 8 8H4Z" />
                                            </svg>
                                        </span>

                                        <span class="hidden sm:block">
                                            <span class="block text-base font-semibold leading-5 text-gray-900">{{ $currentUser?->name ?? '-' }}</span>
                                            <span class="mt-1 block text-sm font-medium text-gray-500">{{ $currentRoleLabel }}</span>
                                        </span>
                                    </button>

                                    <div
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                        class="absolute right-0 z-50 mt-3 w-80 overflow-hidden rounded-lg bg-white text-left shadow-xl ring-1 ring-gray-200"
                                        style="display: none;"
                                    >
                                        <div class="flex items-center gap-4 border-b border-gray-100 px-6 py-5">
                                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5Z" />
                                                    <path d="M4 21c0-4.418 3.582-8 8-8s8 3.582 8 8H4Z" />
                                                </svg>
                                            </span>

                                            <div>
                                                <div class="text-lg font-semibold text-gray-900">{{ $currentUser?->name ?? '-' }}</div>
                                                <div class="mt-1 text-base font-medium text-gray-400">{{ $currentRoleLabel }}</div>
                                            </div>
                                        </div>

                                        <div class="px-6 py-3">
                                            <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-4 rounded-md px-3 py-3 text-lg font-medium text-gray-700 hover:bg-gray-50" @click="open = false">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5Z" />
                                                    <path d="M4 21c0-4.418 3.582-8 8-8s8 3.582 8 8H4Z" />
                                                </svg>
                                                <span>{{ __('Profile') }}</span>
                                            </a>

                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="flex w-full items-center gap-4 rounded-md px-3 py-3 text-lg font-medium text-gray-700 hover:bg-gray-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v8.25a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 12 2.25ZM6.166 5.106a.75.75 0 0 1 0 1.06 8.25 8.25 0 1 0 11.668 0 .75.75 0 1 1 1.06-1.06 9.75 9.75 0 1 1-13.788 0 .75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span>{{ __('Log Out') }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>
                @endif

                <main class="p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('swal', (event) => {
                    const options = Array.isArray(event) ? event[0] : event;

                    Swal.fire({
                        title: options.title,
                        text: options.text,
                        icon: options.icon ?? 'success',
                        timer: options.timer ?? 2000,
                        showConfirmButton: options.showConfirmButton ?? false,
                    });
                });
            });

            @if (session('swal'))
                Swal.fire({
                    title: @js(session('swal.title')),
                    text: @js(session('swal.text')),
                    icon: @js(session('swal.icon', 'success')),
                    timer: @js(session('swal.timer', 2000)),
                    showConfirmButton: @js(session('swal.showConfirmButton', false)),
                });
            @endif
        </script>
    </body>
</html>
