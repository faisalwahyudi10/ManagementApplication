<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['permissions'] = collect(data_get($data, 'permissions', []))
            ->filter(fn (bool $value) => $value)
            ->keys()
            ->toArray();

            $role = static::getResource()::getEloquentQuery()->create($data);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
    }
}
