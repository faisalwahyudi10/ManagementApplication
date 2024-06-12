<?php

namespace App\Filament\Resources;

use App\Models\Menu;

class ExceptionResource extends \BezhanSalleh\FilamentExceptions\Resources\ExceptionResource
{
    public static function getNavigationIcon(): string
    {
        return Menu::whereInstance(static::class)->first()?->icon ?? config('filament-exceptions.icons.navigation');
    }
}
