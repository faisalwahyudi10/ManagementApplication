<?php

namespace Database\Seeders;

use App\Actions\Permissions\PermissionPopulateAction;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Symfony\Component\Yaml\Yaml;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        try {
            $file = base_path('data/permissions.yaml');
            if (! file_exists($file)) {
                throw new \Exception('File storage/app/permissions.yaml does not exist');
            }

            $permissions = Yaml::parseFile($file);

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

    }
}
