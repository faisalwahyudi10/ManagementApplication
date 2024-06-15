<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use Filament\Pages\Dashboard as Page;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends Page
{
    public static function getNavigationIcon(): string | Htmlable | null
    {
        return Menu::whereInstance(static::class)->first()?->icon ?? parent::getNavigationIcon();
    }
}
