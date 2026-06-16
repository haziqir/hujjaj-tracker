<?php

use App\Models\Group as HajjGroup;
use App\Models\Pilgrim;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $group_name = '';
    public string $group_code = '';
    public string $leader_id = '';
    public array $selectedPilgrims = [];
    public string $search = '';
    public ?HajjGroup $editingGroup = null;
    public ?int $viewingGroupId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'group_name' => ['required', 'string', 'max:255'],
            'group_code' => ['required', 'string', 'max:100', Rule::unique('groups', 'group_code')->ignore($this->editingGroup?->id)],
            'leader_id' => ['nullable', 'exists:users,id'],
            'selectedPilgrims' => ['array'],
            'selectedPilgrims.*' => ['integer', 'exists:pilgrims,id'],
        ]);

        $selectedIds = collect($this->selectedPilgrims)->map(fn ($id) => (int) $id)->values()->all();
        $hasInvalidMembers = Pilgrim::whereIn('id', $selectedIds)
            ->when($this->editingGroup, function ($query) {
                $query->whereNotNull('group_id')
                    ->where('group_id', '!=', $this->editingGroup->id);
            }, function ($query) {
                $query->whereNotNull('group_id');
            })
            ->exists();

        if ($hasInvalidMembers) {
            $this->addError('selectedPilgrims', 'Only unassigned pilgrims can be selected. When updating, only this group members and unassigned pilgrims are allowed.');

            return;
        }

        $group = $this->editingGroup;

        if ($group) {
            $group->update([
                'group_name' => $validated['group_name'],
                'group_code' => $validated['group_code'],
                'leader_id' => $validated['leader_id'] ?: null,
            ]);
            $message = 'Hajj group updated successfully!';
        } else {
            $group = HajjGroup::create([
                'group_name' => $validated['group_name'],
                'group_code' => $validated['group_code'],
                'leader_id' => $validated['leader_id'] ?: null,
            ]);
            $message = 'Hajj group created successfully!';
        }

        Pilgrim::where('group_id', $group->id)
            ->when($selectedIds !== [], fn ($query) => $query->whereNotIn('id', $selectedIds))
            ->update(['group_id' => null]);

        if ($selectedIds !== []) {
            Pilgrim::whereIn('id', $selectedIds)->update(['group_id' => $group->id]);
        }

        $this->viewingGroupId = $group->id;
        $this->resetForm();
        $this->resetPage();
        $this->dispatch('group-member-select-refresh', options: $this->memberSelectOptions(), selectedPilgrims: []);

        $this->dispatch('swal', [
            'title' => 'Success!',
            'text' => $message,
            'icon' => 'success',
        ]);
    }

    public function edit(HajjGroup $group): void
    {
        $this->editingGroup = $group;
        $this->group_name = $group->group_name;
        $this->group_code = $group->group_code;
        $this->leader_id = (string) ($group->leader_id ?? '');
        $this->selectedPilgrims = $group->pilgrims()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->viewingGroupId = $group->id;
        $this->dispatch('group-member-select-refresh', options: $this->memberSelectOptions(), selectedPilgrims: $this->selectedPilgrims);
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->dispatch('group-member-select-refresh', options: $this->memberSelectOptions(), selectedPilgrims: []);
    }

    public function delete(HajjGroup $group): void
    {
        $group->delete();

        if ($this->viewingGroupId === $group->id) {
            $this->viewingGroupId = null;
        }

        $this->resetForm();
        $this->resetPage();

        $this->dispatch('swal', [
            'title' => 'Deleted!',
            'text' => 'Hajj group deleted successfully!',
            'icon' => 'success',
        ]);
    }

    public function viewMembers(HajjGroup $group): void
    {
        $this->viewingGroupId = $group->id;
    }

    private function resetForm(): void
    {
        $this->editingGroup = null;
        $this->reset('group_name', 'group_code', 'leader_id', 'selectedPilgrims');
        $this->resetValidation();
    }

    private function selectablePilgrimsQuery()
    {
        return Pilgrim::with(['user', 'group'])
            ->when($this->editingGroup, fn ($query) => $query->where(function ($query) {
                $query->whereNull('group_id')
                    ->orWhere('group_id', $this->editingGroup->id);
            }), fn ($query) => $query->whereNull('group_id'))
            ->orderBy('name');
    }

    private function memberSelectOptions(): array
    {
        return $this->selectablePilgrimsQuery()
            ->get()
            ->map(fn (Pilgrim $pilgrim) => [
                'id' => (string) $pilgrim->id,
                'text' => $pilgrim->name . ' - ' . $pilgrim->passport_no,
            ])
            ->values()
            ->all();
    }

    private function groupLeaderOnly(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->roles()->where('name', 'group-leader')->exists()
            && ! $user->roles()->whereIn('name', ['super-admin', 'staff'])->exists();
    }

    public function with(): array
    {
        $search = trim($this->search);
        $restrictedToLeader = $this->groupLeaderOnly();
        $userId = auth()->id();

        $groups = HajjGroup::with(['leader', 'pilgrims'])
            ->withCount('pilgrims')
            ->when($restrictedToLeader, fn ($query) => $query->where('leader_id', $userId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('group_name', 'like', "%{$search}%")
                        ->orWhere('group_code', 'like', "%{$search}%")
                        ->orWhereHas('leader', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('id')
            ->paginate(10);

        $viewingGroup = $this->viewingGroupId
            ? HajjGroup::with(['leader', 'pilgrims.user', 'pilgrims.hotel'])
                ->when($restrictedToLeader, fn ($query) => $query->where('leader_id', $userId))
                ->find($this->viewingGroupId)
            : null;

        return [
            'groups' => $groups,
            'leaders' => User::whereHas('roles', fn ($query) => $query->where('name', 'group-leader'))->orderBy('name')->get(),
            'availablePilgrims' => $this->selectablePilgrimsQuery()->get(),
            'viewingGroup' => $viewingGroup,
            'restrictedToLeader' => $restrictedToLeader,
        ];
    }
}; ?>

<div>
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Hajj Group Management</h2>
        <p class="mt-1 text-sm text-gray-500">
            {{ $restrictedToLeader ? 'View your assigned group members.' : 'Create groups, assign leaders, and manage group members.' }}
        </p>
    </header>

    @unless ($restrictedToLeader)
        <form wire:submit="save" class="mt-6 rounded border border-gray-200 bg-white p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">{{ $editingGroup ? 'Update Hajj Group' : 'Create Hajj Group' }}</h3>
                @if ($editingGroup)
                    <span class="text-sm text-gray-500">Editing {{ $editingGroup->group_name }}</span>
                @endif
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <div>
                    <x-input-label for="group_name" value="Group Name" />
                    <x-text-input wire:model="group_name" id="group_name" class="mt-1 w-full" />
                    <x-input-error :messages="$errors->get('group_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="group_code" value="Group Code" />
                    <x-text-input wire:model="group_code" id="group_code" class="mt-1 w-full" placeholder="Example: HG001" />
                    <x-input-error :messages="$errors->get('group_code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="leader_id" value="Group Leader" />
                    <select wire:model="leader_id" id="leader_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">No leader assigned</option>
                        @foreach ($leaders as $leader)
                            <option value="{{ $leader->id }}">{{ $leader->name }} ({{ $leader->email }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('leader_id')" class="mt-2" />
                </div>
            </div>

        <div class="mt-4">
            <x-input-label for="selectedPilgrims" value="Group Members" />
            <div wire:ignore class="mt-1">
                <select id="selectedPilgrims" multiple class="w-full">
                    @foreach ($availablePilgrims as $pilgrim)
                        <option value="{{ $pilgrim->id }}">
                            {{ $pilgrim->name }} - {{ $pilgrim->passport_no }}
                            @if ($pilgrim->group && (! $editingGroup || $pilgrim->group_id !== $editingGroup->id))
                                ({{ $pilgrim->group->group_name }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <p class="mt-1 text-sm text-gray-500">Search and select multiple pilgrims from the dropdown.</p>
            <x-input-error :messages="$errors->get('selectedPilgrims')" class="mt-2" />
            <x-input-error :messages="$errors->get('selectedPilgrims.*')" class="mt-2" />
        </div>

            <div class="mt-5 flex justify-end gap-3">
                @if ($editingGroup)
                    <x-secondary-button type="button" wire:click="cancelEdit">Cancel</x-secondary-button>
                @endif

                <x-primary-button>{{ $editingGroup ? 'Update' : 'Save' }}</x-primary-button>
            </div>
        </form>
    @endunless

    <div class="mt-8">
        <x-text-input
            wire:model.live.debounce.300ms="search"
            type="search"
            class="w-full md:max-w-md"
            placeholder="Search group name, code or leader..."
        />
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2 border border-gray-300">No.</th>
                    <th class="p-2 border border-gray-300">Group Name</th>
                    <th class="p-2 border border-gray-300">Code</th>
                    <th class="p-2 border border-gray-300">Leader</th>
                    <th class="p-2 border border-gray-300">Members</th>
                    <th class="p-2 border border-gray-300">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($groups as $group)
                    <tr>
                        <td class="p-2 border border-gray-300">{{ $groups->firstItem() + $loop->index }}</td>
                        <td class="p-2 border border-gray-300">{{ $group->group_name }}</td>
                        <td class="p-2 border border-gray-300">{{ $group->group_code }}</td>
                        <td class="p-2 border border-gray-300">{{ $group->leader?->name ?? '-' }}</td>
                        <td class="p-2 border border-gray-300">{{ $group->pilgrims_count }}</td>
                        <td class="p-2 border border-gray-300">
                            <div class="flex flex-wrap gap-2">
                                <a wire:click="viewMembers({{ $group->id }})" class="text-emerald-600 hover:text-emerald-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                    </svg>
                                </a>
                                @unless ($restrictedToLeader)
                                    <a wire:click="edit({{ $group->id }})" class="text-blue-600 hover:text-blue-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </a>
                                    <a wire:click="delete({{ $group->id }})" wire:confirm="Delete this hajj group?" class="text-red-600 hover:text-red-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </a>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500 border border-gray-300">
                            No hajj groups found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        {{ $groups->links('vendor.pagination.custom') }}
    </div>

    @if ($viewingGroup)
        <div class="mt-8 rounded border border-gray-200 bg-white p-4">
            <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ $viewingGroup->group_name }} Members</h3>
                    <p class="text-sm text-gray-500">
                        Code: {{ $viewingGroup->group_code }} |
                        Leader: {{ $viewingGroup->leader?->name ?? 'Not assigned' }}
                    </p>
                </div>
                <span class="text-sm text-gray-500">{{ $viewingGroup->pilgrims->count() }} members</span>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="p-2 border border-gray-300">No.</th>
                            <th class="p-2 border border-gray-300">Name</th>
                            <th class="p-2 border border-gray-300">Passport</th>
                            <th class="p-2 border border-gray-300">Email</th>
                            <th class="p-2 border border-gray-300">Phone</th>
                            <th class="p-2 border border-gray-300">Hotel</th>
                            <th class="p-2 border border-gray-300">Emergency Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($viewingGroup->pilgrims as $member)
                            <tr>
                                <td class="p-2 border border-gray-300">{{ $loop->iteration }}</td>
                                <td class="p-2 border border-gray-300">{{ $member->name }}</td>
                                <td class="p-2 border border-gray-300">{{ $member->passport_no }}</td>
                                <td class="p-2 border border-gray-300">{{ $member->user->email }}</td>
                                <td class="p-2 border border-gray-300">{{ $member->user->phone ?? '-' }}</td>
                                <td class="p-2 border border-gray-300">{{ $member->hotel?->hotel_name ?? '-' }}</td>
                                <td class="p-2 border border-gray-300">{{ $member->emergency_contact }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-gray-500 border border-gray-300">
                                    No members assigned to this group yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

<script>
    (() => {
        function initGroupMemberSelect(selectedValues = null, options = null) {
            const select = $('#selectedPilgrims');

            if (!select.length || typeof $.fn.select2 === 'undefined') {
                return;
            }

            if (select.hasClass('select2-hidden-accessible')) {
                select.off('change.group-members');
                select.select2('destroy');
            }

            if (options !== null) {
                select.empty();

                options.forEach((option) => {
                    select.append(new Option(option.text, option.id, false, false));
                });
            }

            select.select2({
                width: '100%',
                placeholder: 'Search and select pilgrims',
                allowClear: true,
                closeOnSelect: false,
            });

            if (selectedValues !== null) {
                select.val(selectedValues).trigger('change.select2');
            } else {
                select.val(@js($selectedPilgrims)).trigger('change.select2');
            }

            select.on('change.group-members', function () {
                @this.set('selectedPilgrims', $(this).val() || []);
            });
        }

        document.addEventListener('DOMContentLoaded', () => initGroupMemberSelect());
        document.addEventListener('livewire:navigated', () => initGroupMemberSelect());

        document.addEventListener('livewire:init', () => {
            Livewire.on('group-member-select-refresh', (event) => {
                const payload = Array.isArray(event) ? event[0] : event;

                setTimeout(() => {
                    initGroupMemberSelect(payload.selectedPilgrims ?? [], payload.options ?? []);
                }, 50);
            });
        });
    })();
</script>

<style>
    .select2-container--default .select2-selection--multiple {
        min-height: 46px;
        border-color: rgb(209 213 219);
        border-radius: 0.375rem;
        padding: 0.25rem 0.375rem;
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: rgb(99 102 241);
        box-shadow: 0 0 0 1px rgb(99 102 241);
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: rgb(219 234 254);
        border-color: rgb(191 219 254);
        color: rgb(29 78 216);
        border-radius: 0.25rem;
        padding: 0.125rem 0.375rem;
    }

    .select2-dropdown {
        border-color: rgb(209 213 219);
    }
</style>
</div>
