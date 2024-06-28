<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Menu;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use Illuminate\Support\Str;
use Filament\Forms\Components;
use Illuminate\Contracts\Support\Htmlable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Management Users';

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return Menu::whereInstance(static::class)->first()?->icon ?? 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return Menu::whereInstance(static::class)->first()?->name ?? static::getTitleCasePluralModelLabel();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make()
                    ->schema([
                        Components\Grid::make()
                            ->schema([
                                Components\SpatieMediaLibraryFileUpload::make('avatar')
                                    ->collection('profile')
                                    ->hiddenLabel()
                                    ->avatar()
                                    ->alignCenter()
                                    ->columnSpanFull()
                                    ->preserveFilenames(),
                                Components\TextInput::make('name')
                                    ->required()
                                    ->placeholder('Enter name'),
                                Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Enter email'),
                            ])
                            ->columns(2),
                        Components\Grid::make()
                            ->visible(static fn (string $context): string => $context == 'create')
                            ->schema([
                                Components\TextInput::make('password')
                                    ->password()
                                    ->visible(static fn (string $context): string => $context == 'create')
                                    ->autocomplete('new-password')
                                    ->required()
                                    ->placeholder('Enter password'),
                                Components\TextInput::make('password_confirmation')
                                    ->password()
                                    ->required()
                                    ->autocomplete('new-password')
                                    ->visible(static fn (string $context): string => $context == 'create')
                                    ->placeholder('Enter password confirmation'),
                            ])
                            ->columns(2),
                        Components\Grid::make()
                            ->schema([
                                Components\Section::make('User Roles')
                                    ->id('order-cart')
                                    ->schema([
                                        Components\CheckboxList::make('roles')
                                            ->hiddenLabel()
                                            ->relationship('roles', 'name')
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ]),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => User::query())
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
                        ->grow(false)
                        ->collection('profile')
                        ->conversion('avatar')
                        ->width(50)
                        ->height(50)
                        ->defaultImageUrl(function (User $record): string {
                            $name = Str::of(Filament::getUserName($record))
                                ->trim()
                                ->explode(' ')
                                ->map(fn (string $segment): string => filled($segment) ? mb_substr($segment, 0, 1) : '')
                                ->join(' ');

                            return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=FFFFFF&background=111827&font-size=0.33';
                        })
                        ->extraCellAttributes([
                            'style' => 'width: 1px',
                        ])
                        ->circular(),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->weight(FontWeight::Medium)
                            ->searchable()
                            ->sortable(),
                        Tables\Columns\TextColumn::make('email')
                            ->wrap()
                            ->color('gray')
                            ->searchable()
                            ->size('sm')
                            ->sortable()
                            ->icon('heroicon-m-envelope'),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('roles.name')
                            ->badge()
                            ->limitList(3)
                            ->separator(',')
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('is_active')
                            ->badge()
                            ->color(fn (User $record) => $record->is_active ? 'success' : 'danger')
                            ->getStateUsing(fn (User $record) => $record->is_active ? 'Active' : 'Inactive')
                    ]),
                ])->from('md'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->using(fn (User $record, array $data) => $record->update($data)),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('enable')
                        ->visible(fn (User $record) => auth()->user()->can('enable', $record))
                        ->label('Enable User')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $record) => "Enable User : {$record->name}")
                        ->action(function (User $record) {
                            $record->update([
                                'is_active' => true,
                            ]);
                        }),
                    Tables\Actions\Action::make('disable')
                        ->visible(fn (User $record) => auth()->user()->can('disable', $record))
                        ->label('Disable')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $record) => "Disable User : {$record->name}")
                        ->action(function (array $data, User $record) {
                            $record->update([
                                'is_active' => false,
                            ]);
                        }),
                    Impersonate::make()
                        ->grouped()
                        ->label(fn (User $record) => "Login sebagai {$record->name}")
                        ->visible(fn () => auth()->user()->canImpersonate())
                        ->icon('heroicon-m-eye')
                        ->color('primary')
                ])
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
