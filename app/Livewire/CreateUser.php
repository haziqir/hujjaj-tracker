<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class CreateUser extends Component
{
    public $name = '';
    public $email = '';
    public $phone = '';
    public $password = '';
    public $selectedRole = '';

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|min:10',
            'password' => 'required|min:3',
            'selectedRole' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
        ]);

        $user->roles()->attach($this->selectedRole);

        session()->flash('swal', [
            'title' => 'Success!',
            'text' => 'User created successfully!',
            'icon' => 'success',
        ]);

        return redirect()->route('users.index');
    }

    public function render()
    {
        return view('livewire.create-user', ['roles' => Role::all()])->layout('layouts.app');
    }
}
