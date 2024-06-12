<?php

namespace App\Filament\Pages;

use Filament\Actions;
use Filament\Forms;
use Saade\FilamentAdjacencyList\Forms as AdjacencyListForms;

class MenuPage extends \Filament\Pages\Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.menu-page';

    protected static ?string $title = 'Menu';

    public ?array $menuListData = [];

    public function mount()
    {
        $menus = \App\Models\Menu::query()->with(['children' => function ($query) {
            $query->orderBy('order');
        }])
        ->whereParentId(null)
        ->orderBy('order')->get();

        $this->menuListData = ['menus' => static::buildMenuArray($menus)];
    }

    private static function buildMenuArray($menus)
    {
        return array_map(function ($menu) {
            return [
                ...$menu->toArray(),
                'label' => $menu->name.($menu->is_show ? "" : " <span class='text-gray-400 text-sm'>(Hidden)</span>"),
                'children' => static::buildMenuArray($menu->children) ?? [],
            ];
        }, $menus->all());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Menu')
                ->modalHeading('Create Menu')
                ->form(static::getMenuForm())
                ->successNotificationTitle('Menu Created')
                ->failureNotificationTitle('There was a problem creating the menu.')
                ->using(function (array $data) {
                    static::createMenu($data);
                }),
        ];
    }

    public static function createMenu(array $data)
    {
        $newMenu = \App\Models\Menu::create($data);

        return $newMenu;
    }

    public static function getMenuForm(): array
    {
        return [
            Forms\Components\ToggleButtons::make('type')
                ->columnSpan(2)
                ->hiddenLabel()
                ->options(\App\Models\Enums\MenuType::toArray())
                ->icons(\App\Models\Enums\MenuType::toIconArray())
                ->default(\App\Models\Enums\MenuType::Group->value)
                ->live()
                ->inline()
                ->grouped(),
            Forms\Components\Section::make()
                ->columnSpanFull()
                ->columns(6)
                ->schema([
                    Forms\Components\Select::make('instance')
                        ->columnSpan(3)
                        ->label('Resource/Page')
                        ->searchable()
                        ->options(function () {
                            $resources = \Filament\Facades\Filament::getResources();
                            $pages = \Filament\Facades\Filament::getPages();

                            $listMenu = array_merge($resources, $pages);

                            return array_reduce($listMenu, function($menus, $menu) {
                                if ($menu::canAccess()) {
                                    $menus[$menu] = basename(str_replace('\\', '/', $menu));
                                }
                                return $menus;
                            }, []);
                        })
                        ->disableOptionWhen(function (string $value) {
                            return \App\Models\Menu::where('instance', $value)->exists();
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) return;

                            $set('name', $state::getNavigationLabel());
                            $set('route', method_exists($state, 'getRouteBaseName') ? $state::getRouteBaseName() : $state::getNavigationItemActiveRoutePattern());
                            $set('icon', $state::getNavigationIcon() ?? 'heroicon-o-rectangle-stack');
                        })
                        ->visible(fn (Forms\Get $get) => $get('type') == \App\Models\Enums\MenuType::Resources->value)
                        ->required(),
                    Forms\Components\Select::make('parent_id')
                        ->columnSpan(3)
                        ->label('Parent Menu')
                        ->placeholder('Don\'t have parent menu')
                        ->options(
                            fn () => \App\Models\Menu::query()->whereParentId(null)->type(\App\Models\Enums\MenuType::Group)->pluck('name', 'id')->toArray()
                        )
                        ->visible(fn (Forms\Get $get) => $get('type') != \App\Models\Enums\MenuType::Group->value)
                        ->nullable(),
                    Forms\Components\TextInput::make('name')
                        ->columnSpan(3)
                        ->label('Name')
                        ->placeholder('Enter the name of the menu item')
                        ->required(),
                    Forms\Components\TextInput::make('icon')
                        ->columnSpan(3)
                        ->label('Icon')
                        ->required()
                        ->placeholder('Enter the icon of the menu item')
                        ->visible(fn (Forms\Get $get) => $get('type') != \App\Models\Enums\MenuType::Group->value),
                    Forms\Components\TextInput::make('route')
                        ->columnSpan(3)
                        ->label(fn (Forms\Get $get) => $get('is_custom') ? 'URL' : 'Route')
                        ->placeholder('Enter the route of the menu item')
                        ->visible(fn (Forms\Get $get) => $get('type') != \App\Models\Enums\MenuType::Group->value)
                        ->required(),
                    Forms\Components\ToggleButtons::make('is_show')
                        ->columnSpanFull()
                        ->label('Show in Menu')
                        ->boolean()
                        ->colors([
                            true => 'primary',
                            false => 'danger',
                        ])
                        ->default(true)
                        ->inline()
                        ->required(),
                ])
        ];
    }

    protected function getForms(): array
    {
        return [
            'menuListForm',
        ];
    }

    public function menuListForm(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                AdjacencyListForms\Components\AdjacencyList::make('menus')
                    ->hiddenLabel()
                    ->maxDepth(1)
                    ->collapsible()
                    ->labelKey('label')
                    ->form([
                        ...static::getMenuForm(),
                    ])
                    ->addAction(function (AdjacencyListForms\Components\Actions\AddAction $action) {
                        $action
                            ->size('md')
                            ->icon('heroicon-o-plus')
                            ->label('Add Menu')
                            ->outlined()
                            ->color('primary')
                            ->extraAttributes([
                                'class' => 'mx-auto mt-1'
                            ]);
                    })
                    ->deleteAction(function (AdjacencyListForms\Components\Actions\DeleteAction $action) {
                        $action->requiresConfirmation();
                    }),
            ])
            ->model(\App\Models\Menu::class)
            ->statePath('menuListData');
    }
}
