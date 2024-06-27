<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Models;
use Filament\Http;
use Filament\Navigation;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support;
use Filament\Widgets;
use Illuminate\Cookie;
use Illuminate\Foundation;
use Illuminate\Routing;
use Illuminate\Session;
use Illuminate\Support\Facades\Blade;
use Illuminate\View;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->login()
            ->topNavigation(static::getTopNavigation())
            ->colors(static::getColors())
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages(static::getPages())
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets(static::getWidgets())
            ->plugins(static::getPlugins())
            ->navigation(static::getNavigations())          
            ->middleware(static::getMiddleware())
            ->authMiddleware(static::getAuthMiddleware());
    }

    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views/forms/components'), 'forms');
    }

    protected static function getTopNavigation()
    {
        return function_exists('setting') ? setting('top_navbar') : true;
    }

    protected static function getNavigations()
    {
        return function (Navigation\NavigationBuilder $builder): Navigation\NavigationBuilder {

            $menuItems = Models\Menu::query()
                ->with(['children' => function ($query) {
                    $query->orderBy('order')
                        ->whereIsShow(true);
                }])
                ->whereParentId(null)
                ->whereIsShow(true)
                ->orderBy('order')
                ->get();

            $listItems = [];

            foreach ($menuItems as $menu) {
                if ($menu->type === Models\Enums\MenuType::Group) {
                    $listItems[] = Navigation\NavigationGroup::make()
                        ->label($menu->name)
                        ->items(static::getNavigationChildItems($menu->children))
                        ->when($menu->icon, fn ($group) => $group->icon($menu->icon));
                } else {
                    $item = static::getNavigationItems($menu) ?? [];

                    $listItems[] = Navigation\NavigationGroup::make()->items($item);
                }
            }

            return $builder
                ->groups($listItems);
        };
    }

    protected static function getNavigationChildItems($menu): array
    {
        $listItem = [];

        foreach ($menu as $child) {
            $listItem = static::getNavigationItems($child, $listItem);
        }

        return $listItem;
    }

    protected static function getNavigationItems(Models\Menu $menu, array $listItem = []) {
        $instance = $menu->instance;

        if ($menu->type == Models\Enums\MenuType::Resources) {
            if ($instance::canViewAny()) {
                $listItem = array_merge($listItem, $instance::getNavigationItems());
            }
        } else {
            $listItem[] = Navigation\NavigationItem::make()
                ->label($menu->name)
                ->icon($menu->icon)
                ->sort($menu->order)
                ->isActiveWhen(fn () => request()->routeIs($menu->route))
                ->url(fn () => $menu->is_custom ? $menu->route : route($menu->route));
        }

        return $listItem;
    }

    public static function getColors()
    {
        return [
            'primary' => Support\Colors\Color::Amber,
        ];
    }

    public static function getPages()
    {
        return [
            Dashboard::class,
        ];
    }

    public static function getWidgets()
    {
        return [
            Widgets\AccountWidget::class,
            Widgets\FilamentInfoWidget::class,
        ];
    }

    protected static function getPlugins()
    {
        return [
            \Awcodes\LightSwitch\LightSwitchPlugin::make(),
            \Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin::make()->color('rgb('.Support\Colors\Color::Amber[500].')'),
        ];
    }

    protected static function getMiddleware()
    {
        return [
            Cookie\Middleware\EncryptCookies::class,
            Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Session\Middleware\StartSession::class,
            Session\Middleware\AuthenticateSession::class,
            View\Middleware\ShareErrorsFromSession::class,
            Foundation\Http\Middleware\VerifyCsrfToken::class,
            Routing\Middleware\SubstituteBindings::class,
            Http\Middleware\DisableBladeIconComponents::class,
            Http\Middleware\DispatchServingFilamentEvent::class,
        ];
    }

    protected static function getAuthMiddleware()
    {
        return [
            Http\Middleware\Authenticate::class,
        ];
    }
}