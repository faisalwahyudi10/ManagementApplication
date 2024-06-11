<?php

namespace App\Models\Enums;

use App\Models\Enums\Concern\UsefulEnums;
use Filament\Support\Contracts\HasLabel;

enum MenuType: string implements HasLabel
{
    use UsefulEnums;

    case Group = 'Group';
    case Resources = 'Resources';
    case Custom = 'Custom';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    //to array 
    public static function toArray(): array
    {
        return [
            self::Group->value => 'Group',
            self::Resources->value => 'Filament Resource',
            self::Custom->value => 'Custom Link',
        ];
    }

    // icon to array
    public static function toIconArray(): array
    {
        return [
            self::Group->value => 'heroicon-s-queue-list',
            self::Resources->value => 'heroicon-o-globe-alt',
            self::Custom->value => 'heroicon-o-link',
        ];
    }
    
}
