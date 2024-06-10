<?php

namespace App\Models\Enums;

use App\Models\Enums\Concern\UsefulEnums;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    use UsefulEnums;

    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
