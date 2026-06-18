<nav class="flex items-center justify-end gap-2">
    @auth
        @php
            $homeUrl = auth()->user()->roles()->where('name', 'pilgrim')->exists()
                ? route('pilgrims.dashboard')
                : url('/dashboard');
        @endphp

        <a
            href="{{ $homeUrl }}"
            class="rounded-md border border-white/20 bg-white px-4 py-2 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
        >
            Dashboard
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="rounded-md px-4 py-2 text-sm font-semibold text-white/80 transition hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
        >
            Login
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="rounded-md border border-white/20 bg-white px-4 py-2 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
            >
                Register
            </a>
        @endif
    @endauth
</nav>
