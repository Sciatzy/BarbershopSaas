<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Platform Admin', 'Barbershop Admin', 'Branch Manager', 'Barber', 'Customer'];

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            
            $email = strtolower(str_replace(' ', '', $roleName)) . '@example.com';
            
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $roleName . ' User',
                    'password' => bcrypt('password'),
                ]
            );

            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        }
    }
}
