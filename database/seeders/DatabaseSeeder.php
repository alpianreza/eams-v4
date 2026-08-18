<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Built-in legacy roles (instruction 9) + registry for custom roles.
        foreach (['admin', 'compliance', 'security', 'staff', 'auditor', 'office'] as $role) {
            UserRole::firstOrCreate(['name' => $role]);
        }

        // Default admin for local development only — real users come from the legacy import.
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@eams.local',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'permission' => 'write',
                'status' => 'active',
            ]
        );
    }
}
