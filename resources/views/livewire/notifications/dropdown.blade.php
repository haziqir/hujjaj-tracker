<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function markAsRead(string $id): void
    {
        auth()->user()
            ?->unreadNotifications()
            ->where('id', $id)
            ->first()
            ?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
    }

    public function with(): array
    {
        return [
            'unreadCount' => auth()->user()?->unreadNotifications()->count() ?? 0,
            'notifications' => auth()->user()
                ?->notifications()
                ->latest()
                ->limit(8)
                ->get() ?? collect(),
        ];
    }
}; ?>

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" @click="open = ! open" class="relative flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
            <path d="M5.85 17.1h12.3l-1.2-1.2V11a4.95 4.95 0 0 0-3.75-4.8V5a1.2 1.2 0 1 0-2.4 0v1.2A4.95 4.95 0 0 0 7.05 11v4.9l-1.2 1.2ZM12 21a2.4 2.4 0 0 0 2.28-1.65H9.72A2.4 2.4 0 0 0 12 21Z" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-xs font-semibold text-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition
        class="absolute right-0 z-50 mt-3 w-96 overflow-hidden rounded-lg bg-white text-left shadow-xl ring-1 ring-gray-200"
        style="display: none;"
    >
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h3 class="text-base font-semibold text-gray-900">Notifications</h3>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                    Mark all read
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                @endphp
                <a
                    href="{{ $data['url'] ?? route('notifications.index') }}"
                    wire:navigate
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="block border-b border-gray-100 px-4 py-3 hover:bg-gray-50 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50' }}"
                    @click="open = false"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">{{ $data['title'] ?? 'Notification' }}</div>
                            <div class="mt-1 text-sm text-gray-600">{{ $data['message'] ?? '-' }}</div>
                            @if (! empty($data['reason']))
                                <div class="mt-1 text-xs text-gray-500">{{ $data['reason'] }}</div>
                            @endif
                        </div>
                        @if (! $notification->read_at)
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-600"></span>
                        @endif
                    </div>
                    <div class="mt-2 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">
                    No notifications yet.
                </div>
            @endforelse
        </div>
    </div>
</div>
