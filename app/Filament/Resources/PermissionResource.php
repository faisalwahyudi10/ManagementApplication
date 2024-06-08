<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Management Users';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('context')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('action')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                Tables\Columns\TextColumn::make('roles_count')
                    ->label('Assigned Roles')
                    ->counts('roles')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'primary' : 'gray'),
            ])
            ->groups([
                Tables\Grouping\Group::make('context'),
                Tables\Grouping\Group::make('action')
                    ->getTitleFromRecordUsing(fn (Permission $permission): string => Str::headline($permission->action)),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->using(function (Permission $record, Tables\Actions\DeleteAction $action) {
                        try {
                            return $record->delete();
                        } catch (\Throwable $th) {
                            $action->failureNotificationTitle($th->getMessage());
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePermissions::route('/'),
        ];
    }
}
