<?php

namespace Database\Seeders;

use App\Models\Pilgrim;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super-admin', 'staff', 'group-leader', 'pilgrim'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $createUsers = function ($count, $roleName, $prefix) {
            $role = Role::where('name', $roleName)->first();

            for ($i = 1; $i <= $count; $i++) {
                $user = User::updateOrCreate(
                    ['email' => strtolower("$prefix$i@hajj-system.com")],
                    [
                        'name' => "$prefix $i",
                        'password' => '123456',
                        'phone' => '01' . rand(10000000, 99999999),
                    ]
                );

                $user->roles()->syncWithoutDetaching([$role->id]);

                if ($roleName === 'pilgrim') {
                    Pilgrim::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'name' => $user->name,
                            'passport_no' => 'P' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                            'gender' => $i % 2 === 0 ? 'female' : 'male',
                            'age' => 40 + ($i % 35),
                            'hotel_id' => null,
                            'group_id' => null,
                            'emergency_contact' => '01' . rand(10000000, 99999999),
                        ]
                    );
                }
            }
        };

        $createUsers(1, 'super-admin', 'Admin');
        $createUsers(5, 'staff', 'Staff');
        $createUsers(5, 'group-leader', 'Leader');
        $createUsers(30, 'pilgrim', 'Pilgrim');
    }
}
