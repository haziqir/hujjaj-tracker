<div class="p-6">
    <h2 class="text-xl font-bold mb-6">Edit Role: {{ $role->name }}</h2>

    <form wire:submit="update" class="max-w-full space-y-6">

        <!-- Editable Role Name -->
        <div>
            <x-input-label for="name" value="Role Name" />
            <x-text-input wire:model="name" id="name" type="text" class="block w-full max-w-md mt-1" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Vertical Permission List -->
        <div>
            <x-input-label value="Permissions" class="mb-2" />
            <div class="flex flex-col space-y-2">
                @foreach($permissions as $permission)
                    <label class="flex items-center space-x-3">
                        <input type="checkbox"
                               value="{{ $permission->id }}"
                               wire:model="selectedPermissions"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-gray-700">{{ $permission->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-5 mt-3">
            <x-secondary-button :href="route('role-settings')" wire:navigate>Back</x-secondary-button>
            <x-primary-button>Save Permissions</x-primary-button>
        </div>
    </form>
</div>
