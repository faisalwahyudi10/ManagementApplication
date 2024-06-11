<?php

namespace App\Filament\Pages;

use Filament\Actions;
use Filament\Forms;
use Illuminate\Support\Str;
use Saade\FilamentAdjacencyList\Forms as AdjacencyListForms;

class MenuPage extends \Filament\Pages\Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.menu-page';

    protected static ?string $title = 'Menu';

    public ?array $menuListData = [];

    public function mount()
    {
        $menus = \App\Models\Menu::query()->with('children')->whereParentId(null)->get();

        $this->menuListData = ['menus' => static::buildMenuArray($menus)];
    }

    private static function buildMenuArray($menus)
    {
        return array_map(function ($menu) {
            return [
                'label' => $menu->name,
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
                    Forms\Components\Select::make('filament_resource')
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
                            $slug = $value::getSlug();
                            return \App\Models\Menu::where('slug', $slug)->exists();
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) return;

                            $set('name', $state::getNavigationLabel());
                            $set('slug', $state::getSlug());
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
                    Forms\Components\TextInput::make('slug')
                        ->columnSpan(3)
                        ->label('Slug')
                        ->placeholder('Enter the slug of the menu item')
                        ->visible(fn (Forms\Get $get) => $get('type') == \App\Models\Enums\MenuType::Resources->value)
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
                    ->addAction(function (AdjacencyListForms\Components\Actions\AddAction $action) {
                        $action
                            ->size('md')
                            ->icon('heroicon-o-plus')
                            ->color('primary')
                            ->extraAttributes([
                                'class' => 'mx-auto mt-1'
                            ])
                            ->action(function (AdjacencyListForms\Components\Component $component, array $data): void {
                                $items = $component->getState();
                
                                $items[(string) Str::uuid()] = [
                                    $component->getLabelKey() => __('filament-adjacency-list::adjacency-list.items.untitled'),
                                    $component->getChildrenKey() => [],
                                    ...$data,
                                ];
                
                                $component->state($items);
                            });
                    })
                    ->addChildAction(function (AdjacencyListForms\Components\Actions\AddChildAction $action) {
                        $action
                            ->action(function (AdjacencyListForms\Components\Component $component, array $arguments) use ($action) : void {
                                $parentRecord = $component->getRelatedModel() ? $component->getCachedExistingRecords()->get($arguments['cachedRecordKey']) : null;
                    
                                $action->process(function (AdjacencyListForms\Components\Component $component, array $arguments, array $data): void {
                                    $statePath = $component->getRelativeStatePath($arguments['statePath']);
                                    $uuid = (string) Str::uuid();
                    
                                    $items = $component->getState();
                    
                                    data_set($items, ("$statePath." . $component->getChildrenKey() . ".$uuid"), [
                                        $component->getLabelKey() => __('filament-adjacency-list::adjacency-list.items.untitled'),
                                        $component->getChildrenKey() => [],
                                        ...$data,
                                    ]);
                    
                                    $component->state($items);
                                }, ['parentRecord' => $parentRecord]);
                            });
                    })
                    ->editAction(function (AdjacencyListForms\Components\Actions\EditAction $action) {
                        $action
                            ->action(function (AdjacencyListForms\Components\Component $component, array $arguments): void {
                                $record = $component->getRelatedModel() ? $component->getCachedExistingRecords()->get($arguments['cachedRecordKey']) : null;
                    
                                $this->process(function (AdjacencyListForms\Components\Component $component, array $arguments, array $data): void {
                                    $statePath = $component->getRelativeStatePath($arguments['statePath']);
                                    $state = $component->getState();
                    
                                    $item = array_merge(data_get($state, $statePath), $data);
                    
                                    data_set($state, $statePath, $item);
                    
                                    $component->state($state);
                                }, ['record' => $record]);
                            });
                    })
            ])
            ->model(\App\Models\Menu::class)
            ->statePath('menuListData');
    }
}
