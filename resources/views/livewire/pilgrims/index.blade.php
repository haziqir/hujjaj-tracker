<?php

use App\Models\Group as HajjGroup;
use App\Models\Hotel;
use App\Models\Pilgrim;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $passport_no = '';
    public string $gender = '';
    public string $age = '';
    public string $hotel_id = '';
    public string $group_id = '';
    public string $emergency_contact = '';
    public string $search = '';

    public ?Pilgrim $editingPilgrim = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $pilgrimId = $this->editingPilgrim?->id;
        $userId = $this->editingPilgrim?->user_id;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$this->editingPilgrim ? 'nullable' : 'required', 'string', 'min:6'],
            'passport_no' => ['required', 'string', 'max:100', Rule::unique('pilgrims', 'passport_no')->ignore($pilgrimId)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'hotel_id' => ['nullable', 'exists:hotels,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'emergency_contact' => ['required', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($validated) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?: null,
            ];

            if ($validated['password'] !== '') {
                $userData['password'] = $validated['password'];
            }

            if ($this->editingPilgrim) {
                $user = $this->editingPilgrim->user;
                $user->update($userData);
            } else {
                $user = User::create($userData);
            }

            $pilgrimRole = Role::firstOrCreate(['name' => 'pilgrim']);
            $user->roles()->syncWithoutDetaching([$pilgrimRole->id]);

            $profileData = [
                'user_id' => $user->id,
                'name' => $validated['name'],
                'passport_no' => $validated['passport_no'],
                'gender' => $validated['gender'],
                'age' => $validated['age'],
                'hotel_id' => $validated['hotel_id'] ?: null,
                'group_id' => $validated['group_id'] ?: null,
                'emergency_contact' => $validated['emergency_contact'],
            ];

            if ($this->editingPilgrim) {
                $this->editingPilgrim->update($profileData);
            } else {
                Pilgrim::create($profileData);
            }
        });

        $message = $this->editingPilgrim ? 'Pilgrim profile updated successfully!' : 'Pilgrim account and profile created successfully!';

        $this->resetForm();
        $this->resetPage();

        $this->dispatch('swal', [
            'title' => 'Success!',
            'text' => $message,
            'icon' => 'success',
        ]);
    }

    public function edit(Pilgrim $pilgrim): void
    {
        $pilgrim->load('user');

        $this->editingPilgrim = $pilgrim;
        $this->name = $pilgrim->name;
        $this->email = $pilgrim->user->email;
        $this->phone = $pilgrim->user->phone ?? '';
        $this->password = '';
        $this->passport_no = $pilgrim->passport_no;
        $this->gender = $pilgrim->gender;
        $this->age = (string) $pilgrim->age;
        $this->hotel_id = (string) ($pilgrim->hotel_id ?? '');
        $this->group_id = (string) ($pilgrim->group_id ?? '');
        $this->emergency_contact = $pilgrim->emergency_contact;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(Pilgrim $pilgrim): void
    {
        DB::transaction(function () use ($pilgrim) {
            $user = $pilgrim->user;
            $pilgrim->delete();
            $user?->delete();
        });

        $this->resetForm();
        $this->resetPage();

        $this->dispatch('swal', [
            'title' => 'Deleted!',
            'text' => 'Pilgrim account and profile deleted successfully!',
            'icon' => 'success',
        ]);
    }

    private function resetForm(): void
    {
        $this->editingPilgrim = null;
        $this->reset(
            'name',
            'email',
            'phone',
            'password',
            'passport_no',
            'gender',
            'age',
            'hotel_id',
            'group_id',
            'emergency_contact'
        );
        $this->resetValidation();
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'pilgrims' => Pilgrim::with(['user', 'hotel', 'group'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('passport_no', 'like', "%{$search}%")
                            ->orWhere('emergency_contact', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($query) use ($search) {
                                $query->where('email', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            })
                            ->orWhereHas('hotel', function ($query) use ($search) {
                                $query->where('hotel_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('group', function ($query) use ($search) {
                                $query->where('group_name', 'like', "%{$search}%")
                                    ->orWhere('group_code', 'like', "%{$search}%");
                            });
                    });
                })
                ->orderBy('id')
                ->paginate(10),
            'hotels' => Hotel::orderBy('hotel_name')->get(),
            'groups' => HajjGroup::orderBy('group_name')->get(),
        ];
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Pilgrim Management</h2>
    </header>

    <form wire:submit="save" class="mt-6 rounded border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ $editingPilgrim ? 'Update Pilgrim' : 'Create Pilgrim' }}</h3>
            @if ($editingPilgrim)
                <span class="text-sm text-gray-500">Editing {{ $editingPilgrim->name }}</span>
            @endif
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <div>
                <x-input-label for="name" value="Full Name" />
                <x-text-input wire:model="name" id="name" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input wire:model="email" id="email" type="email" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="phone" value="Phone Number" />
                <x-text-input wire:model="phone" id="phone" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="$editingPilgrim ? 'Password (leave blank to keep current)' : 'Password'" />
                <x-text-input wire:model="password" id="password" type="password" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="passport_no" value="Passport Number" />
                <x-text-input wire:model="passport_no" id="passport_no" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('passport_no')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="gender" value="Gender" />
                <select wire:model="gender" id="gender" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="age" value="Age" />
                <x-text-input wire:model="age" id="age" type="number" min="1" max="120" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('age')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="hotel_id" value="Hotel" />
                <select wire:model="hotel_id" id="hotel_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">No hotel assigned</option>
                    @foreach ($hotels as $hotel)
                        <option value="{{ $hotel->id }}">{{ $hotel->hotel_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('hotel_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="group_id" value="Hajj Group" />
                <select wire:model="group_id" id="group_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">No group assigned</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->group_name }} ({{ $group->group_code }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('group_id')" class="mt-2" />
            </div>

            <div class="lg:col-span-3">
                <x-input-label for="emergency_contact" value="Emergency Contact" />
                <x-text-input wire:model="emergency_contact" id="emergency_contact" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('emergency_contact')" class="mt-2" />
            </div>
        </div>

        <div class="mt-5 flex justify-end gap-3">
            @if ($editingPilgrim)
                <x-secondary-button type="button" wire:click="cancelEdit">Cancel</x-secondary-button>
            @endif

            <x-primary-button>{{ $editingPilgrim ? 'Update' : 'Save' }}</x-primary-button>
        </div>
    </form>

    <div class="mt-8">
        <x-text-input
            wire:model.live.debounce.300ms="search"
            type="search"
            class="w-full md:max-w-md"
            placeholder="Search name, passport, email, phone, hotel or group..."
        />
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2 border border-gray-300">No.</th>
                    <th class="p-2 border border-gray-300">Name</th>
                    <th class="p-2 border border-gray-300">Email</th>
                    <th class="p-2 border border-gray-300">Phone</th>
                    <th class="p-2 border border-gray-300">Passport</th>
                    <th class="p-2 border border-gray-300">Gender</th>
                    <th class="p-2 border border-gray-300">Age</th>
                    <th class="p-2 border border-gray-300">Hotel</th>
                    <th class="p-2 border border-gray-300">Group</th>
                    <th class="p-2 border border-gray-300">Emergency Contact</th>
                    <th class="p-2 border border-gray-300">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pilgrims as $pilgrim)
                    <tr>
                        <td class="p-2 border border-gray-300">{{ $pilgrims->firstItem() + $loop->index }}</td>
                        <td class="p-2 border border-gray-300">{{ $pilgrim->name }}</td>
                        <td class="p-2 border border-gray-300">{{ $pilgrim->user->email }}</td>
                        <td class="p-2 border border-gray-300">{{ $pilgrim->user->phone ?? '-' }}</td>
                        <td class="p-2 border border-gray-300">{{ $pilgrim->passport_no }}</td>
                        <td class="p-2 border border-gray-300">{{ ucfirst($pilgrim->gender) }}</td>
                        <td class="p-2 border border-gray-300">{{ $pilgrim->age }}</td>
                        <td class="p-2 border border-gray-300">{{ $pilgrim->hotel?->hotel_name ?? '-' }}</td>
                        <td class="p-2 border border-gray-300">{{ $pilgrim->group?->group_name ?? '-' }}</td>
                        <td class="p-2 border border-gray-300">{{ $pilgrim->emergency_contact }}</td>
                        <td class="p-2 border border-gray-300">
                            <div class="flex gap-2">
                                <a wire:click="edit({{ $pilgrim->id }})" class="text-blue-600 hover:text-blue-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </a>
                                <a wire:click="delete({{ $pilgrim->id }})" wire:confirm="Delete this pilgrim profile and login account?" class="text-red-600 hover:text-red-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="p-4 text-center text-gray-500 border border-gray-300">
                            No pilgrims found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        {{ $pilgrims->links('vendor.pagination.custom') }}
    </div>
</section>
