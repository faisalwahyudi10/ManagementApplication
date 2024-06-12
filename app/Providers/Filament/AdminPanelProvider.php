<?php

namespace App\Providers\Filament;

use App\Models\Enums\MenuType;
use App\Models\Menu;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->login()
            ->topNavigation()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin::make()
            ])
            ->resources([
                config('filament-logger.activity_resource')
            ])
            ->navigation(static::getNavigations())          
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected static function getNavigations()
    {
        return function (NavigationBuilder $builder): NavigationBuilder {

            $user = Auth::user();
            $menuGroups = Menu::query()
                ->with(['children' => function ($query) {
                    $query->orderBy('order');
                }])
                ->type(MenuType::Group)
                ->whereIsShow(true)
                ->whereParentId(null)
                ->orderBy('order');

            $menuItems = Menu::query()
                ->whereParentId(null)
                ->whereIsShow(true)
                ->type([MenuType::Custom, MenuType::Resources])
                ->orderBy('order');

            $listGroups = [];
            $listItems = [];

            foreach ($menuGroups->get() as $menu) {
                $listGroups[] = NavigationGroup::make()
                    ->label($menu->name)
                    ->items(static::getNavigationGroupItems($menu->children))
                    ->when($menu->icon, fn ($group) => $group->icon($menu->icon));
            }

            foreach ($menuItems->get() as $item) {
                $listItems[] = NavigationItem::make()
                    ->label($item->name)
                    ->icon($item->icon)
                    ->url(route($item->route));
            }

            return $builder
                ->groups($listGroups)
                ->items($listItems);
        };
    }

    protected static function getNavigationGroupItems($menu): array
    {
        $listItem = [];

        foreach ($menu as $child) {
            $instance = $child->instance;
            if ($child->type == MenuType::Resources)
            {
                $listItem = array_merge($listItem, $instance::getNavigationItems());
                
            } else {
                $listItem[] = NavigationItem::make()
                    ->label($child->name)
                    ->icon($child->icon)
                    ->sort($child->order)
                    ->url(route($child->route));
            }
        }

        return $listItem;
    }
}
