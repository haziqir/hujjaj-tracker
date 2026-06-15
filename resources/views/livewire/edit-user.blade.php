<div class="p-6">
    <h2 class="text-xl font-bold mb-6">Edit User: {{ $user->name }}</h2>

    <form wire:submit="update" class="max-w-md space-y-4">
        <div>
            <x-input-label value="Name" />
            <x-text-input wire:model="name" class="w-full" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label value="Email" />
            <x-text-input wire:model="email" type="email" class="w-full" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label value="Phone" />
            <x-text-input wire:model="phone" class="w-full" />
            <x-input-error :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label value="Role" />
            <select wire:model="selectedRole" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select a Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('selectedRole')" />
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <x-secondary-button href="{{ route('users.index') }}" wire:navigate>Back</x-secondary-button>
            <x-primary-button>Update</x-primary-button>
        </div>
    </form>
</div>
