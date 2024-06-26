<?php

namespace Database\Seeders;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\MenuPage;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ExceptionResource;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Models\Enums\MenuType;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dashboard
        $this->createMenu(Dashboard::class, MenuType::Pages, 1);

        // Group Management Users
        $groupManagementUsers = $this->createMenu('Management Users', MenuType::Group, 2);
        foreach ([
            UserResource::class,
            RoleResource::class,
            PermissionResource::class,
        ] as $index => $resource) {
            $this->createMenu($resource, MenuType::Resources, $index + 1, $groupManagementUsers->id);
        }

        // Developer Tools
        $developerTools = $this->createMenu('Developer Tools', MenuType::Group, 3);
        foreach ([
            ActivityResource::class,
            ExceptionResource::class,
        ] as $index => $resource) {
            $this->createMenu($resource, MenuType::Resources, $index + 1, $developerTools->id);
        }

        // Menu
        $this->createMenu(MenuPage::class, MenuType::Pages, 4);
    }

    public function createMenu($instance, MenuType $type, int $order, $parent = null)
    {
        $newMenu = Menu::firstOrCreate([
            'parent_id' => $parent,
            'name' => $type != MenuType::Group ? $instance::getNavigationLabel() : $instance,
            'type' => $type,
            'icon' => $type != MenuType::Group ? $instance::getNavigationIcon(): null,
            'route' => $type != MenuType::Group ? (method_exists($instance, 'getRouteBaseName') ? $instance::getRouteBaseName() : $instance::getNavigationItemActiveRoutePattern()) : null,
            'is_show' => true,
            'order' => $order,
            'is_custom' => $type == MenuType::Custom,
            'instance' => $type != MenuType::Group ? $instance : null,
        ]);

        return $newMenu;
    }
}
