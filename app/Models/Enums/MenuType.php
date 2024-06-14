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

    public static function toArray(array $except = []): array
    {
        $options = [
            self::Group->value => self::Group->getLabel(),
            self::Resources->value => self::Resources->getLabel(),
            self::Custom->value => self::Custom->getLabel(),
        ];

        return collect($options)
            ->except($except)
            ->toArray();
    }

    public static function toIconArray(array $except = []): array
    {
        $icons = [
            self::Group->value => 'heroicon-s-queue-list',
            self::Resources->value => 'heroicon-o-globe-alt',
            self::Custom->value => 'heroicon-o-link',
        ];

        return collect($icons)
            ->except($except)
            ->toArray(); 
    }
}
