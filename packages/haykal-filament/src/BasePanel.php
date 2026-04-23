<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament;

use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\SimplePage;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware;
use HiTaqnia\Haykal\Core\Tenancy\Models\Tenant;
use HiTaqnia\Haykal\Filament\Auth\HuwiyaRedirectLogin;
use HiTaqnia\Haykal\Filament\Http\Middlewares\AccessCheckingMiddleware;
use HiTaqnia\Haykal\Filament\Http\Middlewares\FilamentTenancyMiddleware;
use HiTaqnia\Haykal\Filament\Http\Middlewares\SetPanelLocale;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

/**
 * Base class for every Haykal Filament panel.
 *
 * Wires the Filament middleware stack, the Huwiya-backed login page,
 * sensible defaults (SPA mode, full-width layout, light theme, no global
 * search), convention-driven resource/page/widget/cluster discovery under
 * `app/Panels/<Name>`, and tenant-aware middleware that bridges Filament's
 * tenant resolution into Haykal's `Tenancy` context.
 *
 * Subclasses declare the panel identity and may override the customization
 * hooks to tweak branding, default theme, or tenancy wiring:
 *
 *     final class ManagementPanelProvider extends BasePanel
 *     {
 *         protected function getId(): string { return 'management'; }
 *
 *         protected function customizePanel(Panel $panel): Panel
 *         {
 *             return $panel->brandName('Management');
 *         }
 *
 *         protected function tenantModel(): ?string { return Complex::class; }
 *
 *         protected function tenantSlugAttribute(): ?string { return 'slug'; }
 *     }
 *
 * Return `null` from `tenantModel()` for single-tenant (or tenant-less)
 * panels such as super-admin dashboards.
 */
abstract class BasePanel extends PanelProvider
{
    /**
     * Unique panel identifier used by Filament for routing, URL prefixes,
     * resource discovery (`app/Panels/<Name>/Resources`), and session
     * scoping.
     */
    abstract protected function getId(): string;

    /**
     * Apply panel-specific customizations (brand name, extra plugins,
     * additional pages, navigation groups, etc.) on top of the shared
     * Haykal defaults. Must return the same `$panel` instance.
     */
    abstract protected function customizePanel(Panel $panel): Panel;

    public function panel(Panel $panel): Panel
    {
        $name = $this->getName();

        $panel
            ->id($this->getId())
            ->login($this->loginPage())
            ->pages([
                Dashboard::class,
            ])
            ->discoverResources(in: app_path("Panels/{$name}/Resources"), for: "App\\Panels\\{$name}\\Resources")
            ->discoverPages(in: app_path("Panels/{$name}/Pages"), for: "App\\Panels\\{$name}\\Pages")
            ->discoverWidgets(in: app_path("Panels/{$name}/Widgets"), for: "App\\Panels\\{$name}\\Widgets")
            ->discoverClusters(in: app_path("Panels/{$name}/Clusters"), for: "App\\Panels\\{$name}\\Clusters")
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
                SetPanelLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins($this->defaultPlugins())
            ->defaultThemeMode(ThemeMode::Light)
            ->databaseTransactions()
            ->spa()
            ->maxContentWidth(Width::Full)
            ->globalSearch(false)
            ->darkMode(false);

        $tenantModel = $this->tenantModel();

        if ($tenantModel !== null) {
            $panel
                ->tenant($tenantModel, $this->tenantSlugAttribute())
                ->tenantMenu(false)
                ->tenantMiddleware([
                    FilamentTenancyMiddleware::class,
                    PermissionsTeamMiddleware::class,
                    AccessCheckingMiddleware::class,
                ], isPersistent: true);
        }

        return $this->customizePanel($panel);
    }

    /**
     * Login page class. Override to replace the Huwiya OAuth redirect
     * with a custom auth page (for example, a pre-redirect consent page).
     *
     * @return class-string<SimplePage>
     */
    protected function loginPage(): string
    {
        return HuwiyaRedirectLogin::class;
    }

    /**
     * Concrete tenant model for this panel, or `null` for tenant-less
     * panels (e.g., super-admin). Typically a subclass of
     * {@see Tenant}.
     *
     * @return class-string<Model>|null
     */
    protected function tenantModel(): ?string
    {
        return null;
    }

    /**
     * Column on the tenant model used as the URL slug. `null` falls back
     * to the tenant's primary key.
     */
    protected function tenantSlugAttribute(): ?string
    {
        return null;
    }

    /**
     * Panel plugins to install by default. Override to add or remove.
     *
     * @return array<int, Plugin>
     */
    protected function defaultPlugins(): array
    {
        return [
            SpatieTranslatablePlugin::make()
                ->defaultLocales(config('app.supported_locales', [config('app.locale')]))
                ->persist(),
        ];
    }

    /**
     * Derive the panel name used for directory discovery from the panel id.
     * `management` → `Management`, `super-admin` → `SuperAdmin`.
     */
    protected function getName(): string
    {
        return (string) Str::of($this->getId())->studly();
    }
}
