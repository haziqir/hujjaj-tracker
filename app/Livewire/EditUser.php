<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditUser extends Component
{
    public User $user;
    public string $name = '';
    public string $email = '';
    public ?string $phone = null;
    public string $selectedRole = '';

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->selectedRole = $user->roles->first()?->id ?? '-';
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user->id)],
            'selectedRole' => 'required|exists:roles,id',
            'phone' => 'nullable',
        ]);

        $this->user->name = $this->name;
        $this->user->email = $this->email;
        $this->user->phone = $this->phone;
        $this->user->save();

        $this->user->roles()->sync([$this->selectedRole]);

        session()->flash('swal', [
            'title' => 'Success!',
            'text' => 'User updated successfully!',
            'icon' => 'success',
        ]);

        return redirect()->route('users.index');
    }

    public function render()
    {
        return view('livewire.edit-user', [
            'roles' => Role::all()
        ])->layout('layouts.app');
    }
}
