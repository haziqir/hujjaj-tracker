<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

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
            'notifications' => auth()->user()
                ?->notifications()
                ->latest()
                ->paginate(10),
            'unreadCount' => auth()->user()?->unreadNotifications()->count() ?? 0,
        ];
    }
}; ?>

<section>
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Notifications</h2>
            <p class="mt-1 text-sm text-gray-500">Review SOS and group separation notifications.</p>
        </div>

        @if ($unreadCount > 0)
            <x-primary-button type="button" wire:click="markAllAsRead">
                Mark All Read
            </x-primary-button>
        @endif
    </header>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2 border border-gray-300">No.</th>
                    <th class="p-2 border border-gray-300">Title</th>
                    <th class="p-2 border border-gray-300">Message</th>
                    <th class="p-2 border border-gray-300">Reason</th>
                    <th class="p-2 border border-gray-300">Status</th>
                    <th class="p-2 border border-gray-300">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($notifications as $notification)
                    @php($data = $notification->data)
                    <tr class="{{ $notification->read_at ? 'bg-white' : 'bg-blue-50' }}">
                        <td class="p-2 border border-gray-300">{{ $notifications->firstItem() + $loop->index }}</td>
                        <td class="p-2 border border-gray-300 font-medium text-gray-900">{{ $data['title'] ?? 'Notification' }}</td>
                        <td class="p-2 border border-gray-300">{{ $data['message'] ?? '-' }}</td>
                        <td class="p-2 border border-gray-300 text-sm text-gray-600">{{ $data['reason'] ?? '-' }}</td>
                        <td class="p-2 border border-gray-300">
                            @if ($notification->read_at)
                                <span class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">Read</span>
                            @else
                                <span class="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">Unread</span>
                            @endif
                        </td>
                        <td class="p-2 border border-gray-300">
                            <div class="flex gap-3">
                                <a href="{{ $data['url'] ?? route('dashboard') }}" wire:navigate class="text-blue-600 hover:text-blue-800">Open</a>
                                @if (! $notification->read_at)
                                    <button type="button" wire:click="markAsRead('{{ $notification->id }}')" class="text-emerald-600 hover:text-emerald-800">Mark read</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500 border border-gray-300">
                            No notifications yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        {{ $notifications->links('vendor.pagination.custom') }}
    </div>
</section>
