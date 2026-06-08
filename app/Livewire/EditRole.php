<?php

namespace App\Livewire;

use App\Models\Permission;
use App\Models\Role;
use Livewire\Component;

class EditRole extends Component
{
    public Role $role;
    public string $name;
    public $selectedPermissions = [];

    public function mount(Role $role)
    {
        $this->role = $role;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
    }

    public function update()
    {
        $this->role->update(['name' => $this->name]);
        $this->role->permissions()->sync($this->selectedPermissions);

        session()->flash('message', 'Permissions updated successfully!');
        return redirect()->route('role-settings');
    }

    public function render()
    {
        return view('livewire.edit-role', ['permissions' => Permission::all()])->layout('layouts.app');
    }
}
