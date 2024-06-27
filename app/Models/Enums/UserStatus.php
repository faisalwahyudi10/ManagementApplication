<?php

namespace App\Models\Enums;

use App\Models\Enums\Concern\UsefulEnums;
use Filament\Support\Contracts\HasLabel;

enum UserStatus: int implements HasLabel
{
    use UsefulEnums;

    case Active = 1;
    case Inactive = 0;

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public static function getIcon(int $state): string
    {
        return match ($state) {
            self::Active->value => 'heroicon-o-check',
            self::Inactive->value => 'heroicon-o-x-mark',
        };
    }

    public static function getColor(int $state): string
    {
        return match ($state) {
            self::Active->value => 'success',
            self::Inactive->value => 'danger',
        };
    }

    public static function getName(int $state): string
    {
        return match ($state) {
            self::Active->value => 'Active',
            self::Inactive->value => 'Inactive',
        };
    }
}
