<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Notifications\Notification;
use Saade\FilamentAdjacencyList\Forms as AdjacencyListForms;

class MenuPage extends \Filament\Pages\Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.menu-page';

    protected static ?string $title = 'Menu';

    public bool $isUpdated = false;

    public ?array $deletedMenus = [];

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
        $listMenu = [];

        foreach ($menus as $menu) {
            $listMenu[] = [
                ...$menu->toArray(),
                'hidden' => ! $menu->is_show,
                'children' => static::buildMenuArray($menu->children) ?? [],
            ];
        }

        return $listMenu;
    }

    public function submit()
    {
        try {
            $data = $this->menuListForm->getState();

            $this->createMenus(array_values($data['menus']));

            Notification::make()
                ->title('Menu has been updated successfully.')
                ->success()
                ->send();
        } catch (\Throwable $th) {
            Notification::make()
                ->title('Failed to update menu.')
                ->danger()
                ->body($th->getMessage())
                ->send();
        }
    }

    private function createMenus($menus, $parent = null)
    {
        foreach ($menus as $index => $menu) {
            $menu['order'] = $index + 1;
            $menu['parent_id'] = $parent;

            unset($menu['created_at'], $menu['updated_at'], $menu['children'], $menu['hidden']);

            if (isset($menu['id'])) {
                $newMenu = \App\Models\Menu::updateOrCreate(['id' => $menu['id']], $menu);
            } else {
                $newMenu = \App\Models\Menu::create($menu);
            }

            if (isset($menu['children'])) {
                $this->createMenus($menu['children'], $newMenu->id);
            }

            if (isset($this->deletedMenus)) {
                \App\Models\Menu::destroy($this->deletedMenus);
            }
        }
    }

    public static function getMenuForm(): array
    {
        return [
            Forms\Components\ToggleButtons::make('type')
                ->columnSpan(2)
                ->hiddenLabel()
                ->options(function (Forms\Components\ToggleButtons $component, $context) {
                    $except = [];

                    $formState = ! in_array($context, ['add', 'addChild']) ? $component->getContainer()->getRawState() : [];

                    if (! empty($formState['children'])) {
                        $except = [\App\Models\Enums\MenuType::Resources->value, \App\Models\Enums\MenuType::Custom->value];
                    }
                    
                    if ($context == 'edit' && isset($formState['parent_id']) && $formState['parent_id'] != null) {
                        $except = [\App\Models\Enums\MenuType::Group->value];
                    }

                    if ($context == 'addChild') {
                        $except = [\App\Models\Enums\MenuType::Group->value];
                    }

                    return \App\Models\Enums\MenuType::toArray($except);
                })
                ->icons(\App\Models\Enums\MenuType::toIconArray())
                ->default(function ($context) {
                    if ($context == 'addChild') {
                        return \App\Models\Enums\MenuType::Resources->value;
                    }

                    return \App\Models\Enums\MenuType::Group->value;
                })
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
                \App\Forms\Components\AdjacencyList::make('menus')
                    ->hiddenLabel()
                    ->maxDepth(1)
                    ->collapsible()
                    ->labelKey('name')
                    ->moveable(false)
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
                    ->addChildAction(function (AdjacencyListForms\Components\Actions\AddChildAction $action) {
                        $action
                            ->visible(function (AdjacencyListForms\Components\Component $component, array $arguments) {
                                $statePath = $component->getRelativeStatePath($arguments['statePath']);
                                $state = $component->getState();

                                $item = data_get($state, $statePath);
                                
                                return $item['type'] == \App\Models\Enums\MenuType::Group->value;
                            });
                    })
                    ->deleteAction(function (AdjacencyListForms\Components\Actions\DeleteAction $action) {
                        $action
                            ->action(function (AdjacencyListForms\Components\Component $component, array $arguments) use ($action) {
                                $record = $component->getRelatedModel() ? $component->getCachedExistingRecords()->get($arguments['cachedRecordKey']) : null;

                                $action->process(function (AdjacencyListForms\Components\Component $component, array $arguments): void {
                                    $statePath = $component->getRelativeStatePath($arguments['statePath']);
                                    $items = $component->getState();

                                    $itemState = data_get($items, $statePath);

                                    if (isset($itemState['id'])) {
                                        $this->deletedMenus[] = $itemState['id'];
                                    }

                                    data_forget($items, $statePath);

                                    $component->state($items);
                                }, ['record' => $record]);
                            })
                            ->requiresConfirmation();
                    })
                    ->afterStateUpdated(function ($state) {
                        $this->isUpdated = true;
                    })
            ])
            ->model(\App\Models\Menu::class)
            ->statePath('menuListData');
    }
}
