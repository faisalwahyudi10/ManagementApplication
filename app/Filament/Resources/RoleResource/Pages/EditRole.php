<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = app(PermissionRegistrar::class)
            ->getPermissions()
            ->mapWithKeys(fn ($permission) => [$permission->name => $this->getRecord()->hasPermissionTo($permission->name)])
            ->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['permissions'] = collect(data_get($data, 'permissions', []))
            ->filter(fn (bool $value) => $value)
            ->keys()
            ->toArray();

            $record->update($data);

            if (isset($data['permissions'])) {
                $record->syncPermissions($data['permissions']);
            }

            return $record;
    }
}
