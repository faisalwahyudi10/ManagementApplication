<?php

namespace App\Filament\Resources;

use App\Models\Menu;
use Z3d0X\FilamentLogger\Resources\ActivityResource as Resources;

class ActivityResource extends Resources
{

    public static function getNavigationIcon(): string
    {
        return  Menu::whereInstance(static::class)->first()?->icon ?? __('filament-logger::filament-logger.nav.log.icon');
    }
}
