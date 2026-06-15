<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function with(): array
    {
        return [
            'pilgrim' => auth()->user()?->pilgrim()->with(['user', 'hotel', 'group'])->first(),
        ];
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Pilgrim Profile</h2>
        <p class="mt-1 text-sm text-gray-500">View your linked pilgrim information.</p>
    </header>

    @if ($pilgrim)
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded border border-gray-200 bg-white p-5">
                <h3 class="text-base font-semibold text-gray-900">Personal Information</h3>

                <dl class="mt-4 divide-y divide-gray-100 text-sm">
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Name</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->name }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Email</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->user->email }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Phone</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->user->phone ?? '-' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Passport</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->passport_no }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Gender</dt>
                        <dd class="col-span-2 text-gray-900">{{ ucfirst($pilgrim->gender) }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Age</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->age }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded border border-gray-200 bg-white p-5">
                <h3 class="text-base font-semibold text-gray-900">Travel Information</h3>

                <dl class="mt-4 divide-y divide-gray-100 text-sm">
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Hotel</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->hotel?->hotel_name ?? 'Not assigned' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Hotel Address</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->hotel?->address ?? '-' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Group</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->group?->group_name ?? 'Not assigned' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Group Code</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->group?->group_code ?? '-' }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-4 py-3">
                        <dt class="font-medium text-gray-700">Emergency Contact</dt>
                        <dd class="col-span-2 text-gray-900">{{ $pilgrim->emergency_contact }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    @else
        <div class="mt-6 rounded border border-gray-200 bg-gray-50 p-6 text-gray-600">
            No pilgrim profile is linked to your account yet.
        </div>
    @endif
</section>
