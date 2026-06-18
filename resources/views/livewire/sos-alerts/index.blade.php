<?php

use App\Models\Assignment;
use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'active';
    public string $search = '';
    public array $staffSelections = [];
    public ?int $viewingAlertId = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function isAdmin(): bool
    {
        return auth()->user()?->roles()->where('name', 'super-admin')->exists() ?? false;
    }

    private function isStaff(): bool
    {
        return auth()->user()?->roles()->where('name', 'staff')->exists() ?? false;
    }

    private function isGroupLeader(): bool
    {
        return auth()->user()?->roles()->where('name', 'group-leader')->exists() ?? false;
    }

    private function scopedAlertsQuery()
    {
        $query = SosAlert::query();

        if ($this->isAdmin()) {
            return $query;
        }

        if ($this->isStaff()) {
            return $query->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhereHas('assignments', function ($query) {
                        $query->where('staff_id', auth()->id())
                            ->whereNull('completed_at');
                    });
            });
        }

        if ($this->isGroupLeader()) {
            return $query->whereHas('pilgrim.group', function ($query) {
                $query->where('leader_id', auth()->id());
            });
        }

        return $query->whereRaw('1 = 0');
    }

    private function canViewAlert(SosAlert $alert): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isStaff()) {
            return $alert->status === 'pending'
                || $alert->assignments()
                    ->where('staff_id', auth()->id())
                    ->whereNull('completed_at')
                    ->exists();
        }

        if ($this->isGroupLeader()) {
            return $alert->pilgrim()
                ->whereHas('group', function ($query) {
                    $query->where('leader_id', auth()->id());
                })
                ->exists();
        }

        return false;
    }

    private function authorizeAlertAccess(?SosAlert $alert = null): void
    {
        abort_unless($this->isAdmin() || $this->isStaff() || $this->isGroupLeader(), 403);

        if ($alert) {
            abort_unless($this->canViewAlert($alert), 403);
        }
    }

    public function viewDetails(SosAlert $alert): void
    {
        $this->authorizeAlertAccess($alert);
        $this->viewingAlertId = $alert->id;
    }

    public function accept(SosAlert $alert): void
    {
        $this->authorizeAlertAccess($alert);
        abort_unless($this->isStaff(), 403);

        if ($alert->status !== 'pending') {
            $this->addError('alert-action', 'Only pending alerts can be accepted.');

            return;
        }

        DB::transaction(function () use ($alert) {
            Assignment::create([
                'alert_id' => $alert->id,
                'staff_id' => auth()->id(),
                'assigned_at' => now(),
            ]);

            $alert->update(['status' => 'assigned']);
        });

        $this->viewingAlertId = $alert->id;
        $this->dispatch('swal', [
            'title' => 'Accepted!',
            'text' => 'SOS alert has been assigned to you.',
            'icon' => 'success',
        ]);
    }

    public function assignToStaff(SosAlert $alert): void
    {
        $this->authorizeAlertAccess($alert);
        abort_unless($this->isAdmin(), 403);

        if (! in_array($alert->status, ['pending', 'assigned'], true)) {
            $this->addError('alert-action', 'Resolved alerts cannot be reassigned.');

            return;
        }

        $staffId = $this->staffSelections[$alert->id] ?? null;

        $this->validate([
            "staffSelections.{$alert->id}" => ['required', 'exists:users,id'],
        ], [
            "staffSelections.{$alert->id}.required" => 'Please select a staff member.',
        ]);

        DB::transaction(function () use ($alert, $staffId) {
            Assignment::where('alert_id', $alert->id)
                ->whereNull('completed_at')
                ->update(['completed_at' => now()]);

            Assignment::create([
                'alert_id' => $alert->id,
                'staff_id' => $staffId,
                'assigned_at' => now(),
            ]);

            $alert->update(['status' => 'assigned']);
        });

        $this->viewingAlertId = $alert->id;
        $this->dispatch('swal', [
            'title' => 'Assigned!',
            'text' => 'SOS alert has been assigned to staff.',
            'icon' => 'success',
        ]);
    }

    public function complete(SosAlert $alert): void
    {
        $this->authorizeAlertAccess($alert);

        if ($alert->status !== 'assigned') {
            $this->addError('alert-action', 'Only assigned alerts can be completed.');

            return;
        }

        $assignment = $alert->assignments()
            ->whereNull('completed_at')
            ->latest('assigned_at')
            ->first();

        if (! $assignment) {
            $this->addError('alert-action', 'No active assignment exists for this alert.');

            return;
        }

        if (! $this->isAdmin() && (int) $assignment->staff_id !== auth()->id()) {
            $this->addError('alert-action', 'Only the assigned staff can complete this alert.');

            return;
        }

        DB::transaction(function () use ($alert, $assignment) {
            $assignment->update(['completed_at' => now()]);
            $alert->update(['status' => 'resolved']);
        });

        $this->viewingAlertId = $alert->id;
        $this->dispatch('swal', [
            'title' => 'Completed!',
            'text' => 'SOS alert has been resolved.',
            'icon' => 'success',
        ]);
    }

    public function with(): array
    {
        $this->authorizeAlertAccess();

        $search = trim($this->search);
        $isAdmin = $this->isAdmin();
        $isStaff = $this->isStaff();
        $isGroupLeader = $this->isGroupLeader();
        $baseScope = $this->scopedAlertsQuery();

        $alerts = (clone $baseScope)
            ->with(['pilgrim.group', 'pilgrim.hotel', 'latestAssignment.staff'])
            ->when($this->status === 'active', fn ($query) => $query->whereIn('status', ['pending', 'assigned']))
            ->when(in_array($this->status, ['pending', 'assigned', 'resolved'], true), fn ($query) => $query->where('status', $this->status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('source', 'like', "%{$search}%")
                        ->orWhere('trigger_reason', 'like', "%{$search}%")
                        ->orWhereHas('pilgrim', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('passport_no', 'like', "%{$search}%")
                                ->orWhere('emergency_contact', 'like', "%{$search}%")
                                ->orWhereHas('group', fn ($query) => $query->where('group_name', 'like', "%{$search}%"))
                                ->orWhereHas('hotel', fn ($query) => $query->where('hotel_name', 'like', "%{$search}%"));
                        });
                });
            })
            ->latest()
            ->paginate(10);

        $viewingAlert = $this->viewingAlertId
            ? (clone $baseScope)
                ->with(['pilgrim.group', 'pilgrim.hotel', 'latestAssignment.staff', 'assignments.staff'])
                ->find($this->viewingAlertId)
            : null;

        return [
            'alerts' => $alerts,
            'viewingAlert' => $viewingAlert,
            'staffUsers' => User::whereHas('roles', fn ($query) => $query->where('name', 'staff'))->orderBy('name')->get(),
            'isAdmin' => $isAdmin,
            'isStaff' => $isStaff,
            'isGroupLeader' => $isGroupLeader,
            'pendingCount' => (clone $baseScope)->where('status', 'pending')->count(),
            'assignedCount' => (clone $baseScope)->where('status', 'assigned')->count(),
            'resolvedCount' => (clone $baseScope)->where('status', 'resolved')->count(),
        ];
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">SOS Alert Management</h2>
        <p class="mt-1 text-sm text-gray-500">
            @if ($isGroupLeader && ! $isAdmin && ! $isStaff)
                View SOS alerts from pilgrims in your own group.
            @else
                View active lost pilgrim alerts, assign staff, and resolve assistance tasks.
            @endif
        </p>
    </header>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <div class="rounded border border-red-100 bg-red-50 p-4">
            <div class="text-sm font-medium text-red-700">Pending</div>
            <div class="mt-2 text-2xl font-semibold text-red-900">{{ $pendingCount }}</div>
        </div>
        <div class="rounded border border-blue-100 bg-blue-50 p-4">
            <div class="text-sm font-medium text-blue-700">Assigned</div>
            <div class="mt-2 text-2xl font-semibold text-blue-900">{{ $assignedCount }}</div>
        </div>
        <div class="rounded border border-emerald-100 bg-emerald-50 p-4">
            <div class="text-sm font-medium text-emerald-700">Resolved</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-900">{{ $resolvedCount }}</div>
        </div>
    </div>

    <div class="mt-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <x-text-input
            wire:model.live.debounce.300ms="search"
            type="search"
            class="w-full lg:max-w-md"
            placeholder="Search pilgrim, passport, group, hotel..."
        />

        <select wire:model.live="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-56">
            <option value="active">Active Alerts</option>
            <option value="pending">Pending</option>
            <option value="assigned">Assigned</option>
            <option value="resolved">Resolved</option>
            <option value="all">All Alerts</option>
        </select>
    </div>

    <x-input-error :messages="$errors->get('alert-action')" class="mt-4" />

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2 border border-gray-300">No.</th>
                    <th class="p-2 border border-gray-300">Pilgrim</th>
                    <th class="p-2 border border-gray-300">Age</th>
                    <th class="p-2 border border-gray-300">Group</th>
                    <th class="p-2 border border-gray-300">Hotel</th>
                    <th class="p-2 border border-gray-300">Emergency Contact</th>
                    <th class="p-2 border border-gray-300">Location</th>
                    <th class="p-2 border border-gray-300">Source</th>
                    <th class="p-2 border border-gray-300">Status</th>
                    <th class="p-2 border border-gray-300">Assigned Staff</th>
                    <th class="p-2 border border-gray-300">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alerts as $alert)
                    <tr>
                        <td class="p-2 border border-gray-300">{{ $alerts->firstItem() + $loop->index }}</td>
                        <td class="p-2 border border-gray-300">
                            <div class="font-medium text-gray-900">{{ $alert->pilgrim->name }}</div>
                            <div class="text-sm text-gray-500">{{ $alert->pilgrim->passport_no }}</div>
                        </td>
                        <td class="p-2 border border-gray-300">{{ $alert->pilgrim->age }}</td>
                        <td class="p-2 border border-gray-300">{{ $alert->pilgrim->group?->group_name ?? '-' }}</td>
                        <td class="p-2 border border-gray-300">{{ $alert->pilgrim->hotel?->hotel_name ?? '-' }}</td>
                        <td class="p-2 border border-gray-300">{{ $alert->pilgrim->emergency_contact }}</td>
                        <td class="p-2 border border-gray-300">
                            <a class="text-blue-600 hover:text-blue-800" href="https://www.google.com/maps?q={{ $alert->latitude }},{{ $alert->longitude }}" target="_blank">
                                {{ $alert->latitude }}, {{ $alert->longitude }}
                            </a>
                        </td>
                        <td class="p-2 border border-gray-300">
                            <span class="rounded px-2 py-1 text-xs font-medium {{ $alert->source === 'auto' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($alert->source ?? 'manual') }}
                            </span>
                            <div class="mt-1 max-w-52 text-xs text-gray-500">{{ $alert->trigger_reason ?? '-' }}</div>
                        </td>
                        <td class="p-2 border border-gray-300">
                            @if ($alert->status === 'pending')
                                <span class="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-800">Pending</span>
                            @elseif ($alert->status === 'assigned')
                                <span class="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">Assigned</span>
                            @else
                                <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Resolved</span>
                            @endif
                        </td>
                        <td class="p-2 border border-gray-300">{{ $alert->latestAssignment?->staff?->name ?? '-' }}</td>
                        <td class="p-2 border border-gray-300">
                            <div class="flex min-w-48 flex-col gap-2">
                                <button type="button" wire:click="viewDetails({{ $alert->id }})" class="text-left text-emerald-600 hover:text-emerald-800">
                                    Details
                                </button>

                                @if ($isStaff && $alert->status === 'pending')
                                    <button type="button" wire:click="accept({{ $alert->id }})" class="text-left text-blue-600 hover:text-blue-800">
                                        Accept
                                    </button>
                                @endif

                                @if ($isAdmin && in_array($alert->status, ['pending', 'assigned'], true))
                                    <div class="flex gap-2">
                                        <select wire:model="staffSelections.{{ $alert->id }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Assign staff</option>
                                            @foreach ($staffUsers as $staff)
                                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" wire:click="assignToStaff({{ $alert->id }})" class="text-sm text-blue-600 hover:text-blue-800">
                                            Assign
                                        </button>
                                    </div>
                                    <x-input-error :messages="$errors->get('staffSelections.' . $alert->id)" />
                                @endif

                                @if (($isAdmin || $isStaff) && $alert->status === 'assigned')
                                    <button type="button" wire:click="complete({{ $alert->id }})" class="text-left text-gray-700 hover:text-gray-900">
                                        Mark Completed
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="p-4 text-center text-gray-500 border border-gray-300">
                            No SOS alerts found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        {{ $alerts->links('vendor.pagination.custom') }}
    </div>

    @if ($viewingAlert)
        <div class="mt-8 rounded border border-gray-200 bg-white p-4">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Alert Details: {{ $viewingAlert->pilgrim->name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">Created at {{ $viewingAlert->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <a class="text-sm text-blue-600 hover:text-blue-800" href="https://www.google.com/maps?q={{ $viewingAlert->latitude }},{{ $viewingAlert->longitude }}" target="_blank">
                    Open Location in Google Maps
                </a>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Pilgrim</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $viewingAlert->pilgrim->name }}</div>
                    <div class="text-sm text-gray-500">{{ $viewingAlert->pilgrim->passport_no }}</div>
                </div>
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Age / Gender</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $viewingAlert->pilgrim->age }} / {{ ucfirst($viewingAlert->pilgrim->gender) }}</div>
                </div>
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Group</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $viewingAlert->pilgrim->group?->group_name ?? '-' }}</div>
                </div>
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Hotel</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $viewingAlert->pilgrim->hotel?->hotel_name ?? '-' }}</div>
                </div>
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Emergency Contact</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $viewingAlert->pilgrim->emergency_contact }}</div>
                </div>
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Location</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $viewingAlert->latitude }}, {{ $viewingAlert->longitude }}</div>
                </div>
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Status</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ ucfirst($viewingAlert->status) }}</div>
                </div>
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Source</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ ucfirst($viewingAlert->source ?? 'manual') }}</div>
                    <div class="mt-1 text-sm text-gray-500">{{ $viewingAlert->trigger_reason ?? '-' }}</div>
                </div>
                <div class="rounded border border-gray-200 p-3">
                    <div class="text-sm font-medium text-gray-500">Current Staff</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $viewingAlert->latestAssignment?->staff?->name ?? '-' }}</div>
                </div>
            </div>

            <div class="mt-6">
                <h4 class="text-sm font-semibold text-gray-900">Assignment History</h4>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="p-2 border border-gray-300">No.</th>
                                <th class="p-2 border border-gray-300">Staff</th>
                                <th class="p-2 border border-gray-300">Assigned At</th>
                                <th class="p-2 border border-gray-300">Completed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($viewingAlert->assignments as $assignment)
                                <tr>
                                    <td class="p-2 border border-gray-300">{{ $loop->iteration }}</td>
                                    <td class="p-2 border border-gray-300">{{ $assignment->staff->name }}</td>
                                    <td class="p-2 border border-gray-300">{{ $assignment->assigned_at?->format('Y-m-d H:i:s') }}</td>
                                    <td class="p-2 border border-gray-300">{{ $assignment->completed_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-4 text-center text-gray-500 border border-gray-300">
                                        No assignment history yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</section>
