<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Models\Enums\MenuType;
use App\Models\Menu;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
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
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->login()
            ->topNavigation(function_exists('setting') ? setting('top_navbar') : true)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->plugins([
                \Awcodes\LightSwitch\LightSwitchPlugin::make(),
                \Njxqlus\FilamentProgressbar\FilamentProgressbarPlugin::make()->color('rgb('.Color::Amber[500].')'),
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

    public function boot(): void
    {
        Blade::anonymousComponentPath(resource_path('views/forms/components'), 'forms');
    }

    protected static function getNavigations()
    {
        return function (NavigationBuilder $builder): NavigationBuilder {

            $menuItems = Menu::query()
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
                if ($menu->type === MenuType::Group) {
                    $listItems[] = NavigationGroup::make()
                        ->label($menu->name)
                        ->items(static::getNavigationChildItems($menu->children))
                        ->when($menu->icon, fn ($group) => $group->icon($menu->icon));
                } else {
                    $item = static::getNavigationItems($menu) ?? [];

                    $listItems[] = NavigationGroup::make()->items($item);
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

    protected static function getNavigationItems(Menu $menu, array $listItem = []) {
        $instance = $menu->instance;

        if ($menu->type == MenuType::Resources) {
            if ($instance::canViewAny()) {
                $listItem = array_merge($listItem, $instance::getNavigationItems());
            }
        } else {
            $listItem[] = NavigationItem::make()
                ->label($menu->name)
                ->icon($menu->icon)
                ->sort($menu->order)
                ->isActiveWhen(fn () => request()->routeIs($menu->route))
                ->url(fn () => $menu->is_custom ? $menu->route : route($menu->route));
        }

        return $listItem;
    }
}
