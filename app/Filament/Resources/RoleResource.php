<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Spatie\Permission;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Management Users';

    protected static ?int $navigationSort = 2;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Components\Section::make()
                    ->schema([
                        Components\TextInput::make('name'),
                        Components\Select::make('copy_permissions_from')
                            ->dehydrated(false)
                            ->label('Copy Permissions from')
                            ->hidden(fn(string $operation) => $operation === 'view')
                            ->options(fn() => static::getEloquentQuery()->pluck('name', 'id')->toArray())
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                if (! $state) {
                                    return;
                                }

                                /** @var Role */
                                $newParent = Role::find($state);

                                app(Permission\PermissionRegistrar::class)
                                    ->getPermissions()
                                    ->each(function (Permission\Models\Permission $permission) use ($set, $newParent) {

                                        $condition = $newParent->hasPermissionTo($permission);

                                        $set('permissions.'.$permission->name, $condition);
                                    });
                            })
                            ->preload(),
                    ])
                    ->columns([
                        'sm' => 2,
                    ]),
                Components\Section::make('Advanced Permissions')
                    ->schema(static::getPermissionEntitySchema())
                    ->collapsible(false)
                    ->columns([
                        'sm' => 4,
                    ]),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->rowIndex()
                    ->extraCellAttributes([
                        'style' => 'width: 1px',
                    ])
                    ->label('No.')
                    ->grow(false),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('persistRolePermissions')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-shield-check')
                    ->label('Persist Permissions')
                    // ->hidden(fn(Role $record) => !in_array($record->name, UserRole::values()) || app()->isProduction())
                    ->action(fn(Role $record) => throw new \Exception('Not implemented.'))
                    ->successNotificationTitle('Permissions persisted successfully.')
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getPermissionEntitySchema(): array
    {
        $permissions = app(Permission\PermissionRegistrar::class)->getPermissions();
        $permissionsGroupedByEntity = $permissions->groupBy(function ($permission) {
            [$context, $action] = explode(':', $permission->name);

            return $context;
        })->map(function ($permissions, $key) {
            return Components\Fieldset::make($key)
                ->label(Str::headline($key))
                ->columns(1)
                ->extraAttributes([
                    'class' => 'h-full',
                ])
                ->columnSpan([
                    'lg' => 2,
                    'xl' => 1,
                ])
                ->schema($permissions->map(function ($permission) {
                    [$context, $action] = explode(':', $permission->name);
                    return Components\Checkbox::make('permissions.'.$permission->name)
                        ->label(Str::headline($action));
                })->toArray());
        })->toArray();

        return $permissionsGroupedByEntity;
    }
}
