<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Validate('required|unique:roles,name|min:3')]
    public string $name = '';

    public string $search = '';

    public ?User $editingUser = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingUser) {
            $this->editingUser->update(['name' => $this->name]);
            $this->editingUser = null;
            $message = 'User updated successfully!';
        } else {
            User::create(['name' => $this->name]);
            $message = 'User created successfully!';
        }

        $this->reset('name');
        $this->resetPage();

        $this->dispatch('swal', [
            'title' => 'Success!',
            'text' => $message,
            'icon' => 'success',
        ]);
    }

    public function edit(User $user): void
    {
        $this->editingUser = $user;
        $this->name = $user->name;
    }

    public function cancelEdit(): void
    {
        $this->editingUser = null;
        $this->reset('name');
    }

    public function delete(User $user): void
    {
        $user->delete();
        $this->resetPage();
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'users' => User::with('roles')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhereHas('roles', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%");
                            });
                    });
                })
                ->orderBy('id')
                ->paginate(10),
        ];
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">User Management</h2>
    </header>

    <div class="mt-6 flex justify-end">
        <x-primary-button href="{{ route('users.create') }}" wire:navigate>
            Add
        </x-primary-button>
    </div>

    <div class="mt-6">
        <x-text-input
            wire:model.live.debounce.300ms="search"
            type="search"
            class="w-full md:max-w-md"
            placeholder="Search name, email, phone or role..."
        />
    </div>

    <table class="w-full mt-6 text-left border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-50">
                <th class="p-2 border border-gray-300">No.</th>
                <th class="p-2 border border-gray-300">Name</th>
                <th class="p-2 border border-gray-300">Email</th>
                <th class="p-2 border border-gray-300">Phone</th>
                <th class="p-2 border border-gray-300">Role</th>
                {{-- <th class="p-2 border border-gray-300">Action</th> --}}
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="p-2 border border-gray-300">{{ $users->firstItem() + $loop->index }}</td>
                    <td class="p-2 border border-gray-300">{{ $user->name }}</td>
                    <td class="p-2 border border-gray-300">{{ $user->email }}</td>
                    <td class="p-2 border border-gray-300">{{ $user->phone ?? '-' }}</td>
                    <td class="p-2 border border-gray-300">
                        @forelse($user->roles as $role)
                            @if ($role->id == 1)
                                <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">{{ $role->name }}</span>
                            @elseif ($role->id == 3)
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">{{ $role->name }}</span>
                            @elseif ($role->id == 4)
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">{{ $role->name }}</span>
                            @else
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ $role->name }}</span>
                            @endif

                        @empty
                            -
                        @endforelse
                    </td>
                    <td class="p-2 border border-gray-300 flex gap-2">
                        <a href="{{ route('users.edit', $user->id) }}" class="text-blue-600 hover:text-blue-800" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </a>

                        <button wire:click="delete({{ $user->id }})" class="text-red-600 hover:text-red-800" title="Delete"
                                wire:confirm="Are you sure you want to delete this?">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-500 border border-gray-300">
                        No users found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 flex justify-end">
        {{ $users->links('vendor.pagination.custom') }}
    </div>
</section>
