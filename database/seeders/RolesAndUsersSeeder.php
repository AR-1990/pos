<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        $roles = [
            [
                'name' => 'Super Admin',
                'description' => 'Full access to all system features.',
                'status' => 'Active',
            ],
            [
                'name' => 'Admin',
                'description' => 'Administrator with limited access compared to Super Admin.',
                'status' => 'Active',
            ],
            [
                'name' => 'Accountant',
                'description' => 'Manages accounts, expenses, and financial reports.',
                'status' => 'Active',
            ],
            [
                'name' => 'Sales',
                'description' => 'Handles sales operations and customer interactions.',
                'status' => 'Active',
            ],
        ];

        foreach ($roles as $roleData) {
            // Update or create based on name to avoid duplicates
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        // 2. Create Users for each Role
        $users = [
            [
                'name' => 'Super Admin User',
                'email' => 'superadmin@example.com',
                'role_name' => 'Super Admin',
                'designation' => 'System Owner',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role_name' => 'Admin',
                'designation' => 'Manager',
            ],
            [
                'name' => 'Accountant User',
                'email' => 'accountant@example.com',
                'role_name' => 'Accountant',
                'designation' => 'Senior Accountant',
            ],
            [
                'name' => 'Sales User',
                'email' => 'sales@example.com',
                'role_name' => 'Sales',
                'designation' => 'Sales Representative',
            ],
        ];

        foreach ($users as $userData) {
            $role = Role::where('name', $userData['role_name'])->first();

            if ($role) {
                User::updateOrCreate(
                    ['email' => $userData['email']], // Check by email
                    [
                        'name' => $userData['name'],
                        'password' => Hash::make('password'), // Password is always 'password'
                        'role_id' => $role->id,
                        'designation' => $userData['designation'],
                        'status' => 'Active',
                        'joining_date' => now(),
                        'address' => 'Office Address',
                        'phone' => '0300-0000000',
                    ]
                );
            }
        }
    }
}
