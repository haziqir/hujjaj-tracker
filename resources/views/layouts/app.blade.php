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

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex">
            <aside class="w-64 bg-white border-r border-gray-200 hidden md:block">
                <livewire:layout.navigation />
            </aside>

            <div class="flex-1 flex flex-col">
                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4">
                            {{ $header }}
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
