<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Actions\Roles\RoleAssignDefaultPermissions;
use App\Models\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Symfony\Component\Yaml\Yaml;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (UserRole::array() as $roleName) {
            $role = Role::updateOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            $file = base_path('data/roleAssignedPermissions.yaml');

            if (! file_exists($file)) {
                throw new \Exception("File $file  does not exist");
            }

            $roleAssignedPermissions = Yaml::parseFile($file);
        
            if(!array_key_exists($role->name, $roleAssignedPermissions)) return;
            
            $role?->syncPermissions([...$roleAssignedPermissions[$role->name], ...$role->permissions->pluck('name')->toArray()]);
        }
    }
}
