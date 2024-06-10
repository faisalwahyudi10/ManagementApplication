<?php

namespace Database\Seeders;

use App\Models\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin.super@admin.com',
            'is_active' => true,
            'password' => 'admin',
        ]);
        $superAdmin->syncRoles(UserRole::SuperAdmin);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'is_active' => true,
            'password' => 'admin',
        ]);
        $admin->syncRoles(UserRole::Admin);
    }
}
