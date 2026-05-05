# hitaqnia/haykal-filament

Filament foundation for HiTaqnia Laravel applications.

`haykal-filament` ships the base classes, middlewares, theme, and Huwiya integration that every HiTaqnia Filament panel uses. Installing this package gives an application a complete panel baseline — authentication via Huwiya, tenant-aware middleware stack, opinionated UI defaults, a shared theme, and a convention-driven resource discovery layout — so panel definitions stay focused on the concrete resources, pages, and navigation of each panel.

---

## Table of contents

- [Requirements](#requirements)
- [What this package provides](#what-this-package-provides)
- [Installation](#installation)
- [Configuration](#configuration)
- [Defining panels](#defining-panels)
- [Global Filament defaults](#global-filament-defaults)
- [Resources and pages](#resources-and-pages)
- [Theming](#theming)
- [Translatable forms](#translatable-forms)
- [Mapbox components](#mapbox-components)
- [Customization](#customization)
- [Testing](#testing)

---

## Requirements

- PHP 8.3 or later
- Laravel 13 or later
- `filament/filament` 5.5 or later
- `hitaqnia/haykal-core` (shared kernel)
- A running instance of the Huwiya Identity Provider

---

## What this package provides

### Panel foundation

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Filament\BasePanel` | Abstract `PanelProvider` that wires the shared middleware stack, Huwiya login, sensible defaults (SPA, full-width, light theme, no global search), convention-driven resource/page discovery (`app/Panels/<Name>`), and optional tenancy. |
| `HiTaqnia\Haykal\Filament\BaseFilamentServiceProvider` | Abstract application-side provider that applies HiTaqnia's global Filament UX defaults: locked-down modals, no "Create another" action, slide-over column manager + filters, and "Click to copy" tooltips with em-dash placeholders for copyable text columns/entries. |
| `HiTaqnia\Haykal\Filament\Auth\HuwiyaConsentLogin` | Consent-style login page — renders a single button that redirects the browser to the Huwiya OAuth authorization endpoint for the active panel's guard. Translated through `haykal-filament::auth.login.*` (en / ar / ku). |

### Resources and pages

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Filament\Resources\BaseResource` | Abstract Filament resource with translation-key-driven UI metadata (`panels/{panel}/resources/{resource}.*`). |
| `HiTaqnia\Haykal\Filament\Resources\Pages\BaseListPage` | List page that enables the `custom` tabs style hook used by the base theme. |
| `HiTaqnia\Haykal\Filament\Resources\Pages\BaseCreatePage` | Create page with Filament's "Create another" action suppressed by default. |
| `HiTaqnia\Haykal\Filament\Resources\Pages\BaseEditPage` | Edit page with a read-only fallback for view-only users. |

### Forms

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Filament\Forms\TranslatableTabs` | Tabbed multi-language editor for `spatie/laravel-translatable` attributes, with per-locale icon state and configurable required-language policy. |

### Mapbox (form fields + infolist entries)

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationPicker` | Form field that captures a `{lng, lat}` pair via a draggable marker. |
| `HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationViewer` | Read-only infolist entry that displays a stored `{lng, lat}` location. |
| `HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsDrawer` | Form field that captures a GeoJSON `FeatureCollection` of polygons drawn on the map. Supports `maxPolygons()` cap. |
| `HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsViewer` | Read-only infolist entry that renders a stored GeoJSON `FeatureCollection`. |
| `HiTaqnia\Haykal\Filament\Mapbox\Concerns\InteractsWithMapbox` | Fluent configuration trait shared by all four components (container, height, style, center, zoom, navigation control). |

### Middlewares

| Alias | Class | Purpose |
|---|---|---|
| `haykal.filament.tenancy` | `Http\Middlewares\FilamentTenancyMiddleware` | Propagates Filament's resolved tenant into Haykal's `Tenancy` context. |

### Theming and assets

| Resource | Notes |
|---|---|
| `resources/css/base-theme.css` | Loaded directly from the vendor path by every scaffolded panel theme. Updates propagate through `composer update` — no republishing required. Publishable under `haykal-filament-theme` only when an application needs to fork the file for per-application overrides. |
| `config/haykal-filament-icons.php` | Merged into `config.filament.icons` on register; publishable under `haykal-filament-icons`. |
| `stubs/panel-theme.css.stub` | Publishable under `haykal-filament-stubs`; consumed by the publish-theme command. |

### Console commands

| Command | Purpose |
|---|---|
| `haykal:publish-theme {panel} {--force}` | Scaffolds a per-panel theme entry file at `resources/css/filament/<panel>/theme.css` that `@import`s Filament's default theme and the Haykal base theme directly from the vendor directory and sets up the Tailwind 4 `@source` directives for the panel's PHP and Blade paths. |

---

## Installation

With `hitaqnia/haykal-core` already wired (see its README), require this package alongside:

```bash
composer require hitaqnia/haykal-filament
```

That single require is enough — Phosphor icons (`codeat3/blade-phosphor-icons` via [`tonegabes/filament-phosphor-icons`](https://github.com/tonegabes/filament-phosphor-icons)) are pulled transitively, so every alias declared in `config/haykal-filament-icons.php` resolves out of the box. The `ToneGabes\Filament\Icons\Enums\Phosphor` enum is also available for typed icon references in resources, navigation items, and form components.

---

## Configuration

### 1. Register the Huwiya web guard

In `config/auth.php`, register a guard for every panel that uses Huwiya authentication. The guard driver must be `huwiya-web`:

```php
'guards' => [
    // ... existing guards ...

    'web' => [
        'driver' => 'huwiya-web',
        'provider' => 'users',
    ],
],
```

Panels may each use their own named guard (`admin`, `residents`, …). Filament resolves the guard for the active panel via `Filament::getAuthGuard()`.

### 2. Scaffold the panel theme

Run the publish-theme command once per panel:

```bash
php artisan haykal:publish-theme admin
```

The command scaffolds `resources/css/filament/admin/theme.css`. The scaffold `@import`s Filament's default theme and the Haykal base theme **directly from the vendor directory**, plus the correct Tailwind 4 `@source` directives for the panel's paths. Because both imports reference vendor paths, updates to either flow through `composer update` without republishing. Register the panel theme in `vite.config.js`:

```js
laravel({
    input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/filament/admin/theme.css',
    ],
});
```

Wire the panel theme through `BasePanel::customizePanel()`:

```php
protected function customizePanel(Panel $panel): Panel
{
    return $panel->viteTheme('resources/css/filament/admin/theme.css');
}
```

### 3. Publish the icon overrides (optional)

Icon overrides are merged into `config.filament.icons` automatically. Publish the config file only if per-application tweaks are needed:

```bash
php artisan vendor:publish --tag=haykal-filament-icons
```

---

## Defining panels

Subclass `BasePanel` for every panel. The minimum viable panel declares only an id and a customization hook; everything else — login page, middleware stack, plugins, defaults — is inherited.

```php
namespace App\Providers\Panels;

use App\Models\Complex;
use Filament\Panel;
use HiTaqnia\Haykal\Filament\BasePanel;

final class ManagementPanelProvider extends BasePanel
{
    protected function getId(): string
    {
        return 'management';
    }

    protected function customizePanel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Management')
            ->viteTheme('resources/css/filament/management/theme.css');
    }

    protected function tenantModel(): ?string
    {
        return Complex::class;
    }

    protected function tenantSlugAttribute(): ?string
    {
        return 'slug';
    }
}
```

Register the provider in `bootstrap/providers.php`. Panels without tenancy (super-admin dashboards, system settings) return `null` from `tenantModel()` and skip the tenant middleware stack.

### Multiple tenant types: one panel per type

Filament binds exactly one tenant model per panel, so applications with multiple tenant types (for example, **Agency** and **Development Company**) implement **one panel per type**. Each panel declares its own tenant model, middleware stack, login route, and URL prefix; users working with Agencies sign in at `/agency/...` and never see Developer resources, and vice versa.

```php
final class AgencyPanelProvider extends BasePanel
{
    protected function getId(): string { return 'agency'; }

    protected function tenantModel(): ?string { return Agency::class; }
    protected function tenantSlugAttribute(): ?string { return 'slug'; }

    protected function customizePanel(Panel $panel): Panel { /* ... */ }
}

final class DevelopmentCompanyPanelProvider extends BasePanel
{
    protected function getId(): string { return 'developers'; }

    protected function tenantModel(): ?string { return DevelopmentCompany::class; }
    protected function tenantSlugAttribute(): ?string { return 'slug'; }

    protected function customizePanel(Panel $panel): Panel { /* ... */ }
}
```

`FilamentTenancyMiddleware` stores whichever tenant the active panel resolves into `Tenancy::setTenantId(...)` as a single value — only one tenant type is active per request. Resources on each panel declare their own `$tenantForeignKey` (see `haykal-core`'s "Multiple tenant types" section) so queries filter on the correct column (`agency_id` for `Property`, `developer_id` for `Project`) against that single active id.

### Hooks exposed by `BasePanel`

| Hook | Default | Purpose |
|---|---|---|
| `getId()` | *(abstract)* | Unique panel identifier. Drives routing, session scoping, and resource discovery. |
| `customizePanel(Panel)` | *(abstract)* | Apply panel-specific branding, plugins, navigation, and theme. |
| `loginPage()` | `HuwiyaConsentLogin::class` | Override to replace the Huwiya consent page with a custom page (auto-redirect, custom layout, …). |
| `tenantModel()` | `null` | Concrete Tenant model for this panel, or `null` for tenant-less panels. |
| `tenantSlugAttribute()` | `null` | Column on the tenant model used as the URL slug. `null` falls back to the primary key. |
| `defaultPlugins()` | SpatieTranslatable | Override to install a different base plugin set. |
| `fontFamiliesByLocale()` | `en→Outfit`, `ar→Tajawal`, `ku→Noto Sans Arabic` (default `Outfit`) | Override the locale → font family map applied through `Panel::font(...)`. |
| `fontFamilyForLocale($locale)` | reads the map | Resolves the family for a single locale; rarely needs an override on its own. |
| `getName()` | `Str::studly(id)` | Derives the directory name for resource discovery. Override for custom layouts. |

### Locale-aware fonts

`BasePanel` calls Filament's `Panel::font(...)` with a closure that resolves the font family from the active locale on every render — switching the user's language switches the panel's typography on the next request. Defaults:

| Locale | Font |
|---|---|
| `en` (and any unknown locale) | Outfit |
| `ar` | Tajawal |
| `ku` | Noto Sans Arabic |

Fonts load through Filament's [`BunnyFontProvider`](https://fonts.bunny.net) (the Google Fonts mirror Filament ships with), so no app-side font hosting or build-step is required.

To swap fonts per locale, override `fontFamiliesByLocale()`:

```php
final class ManagementPanelProvider extends BasePanel
{
    // ...

    protected function fontFamiliesByLocale(): array
    {
        return [
            'default' => 'Inter',     // every locale not listed below falls back here
            'ar'      => 'Cairo',
            'ku'      => 'Vazirmatn',
        ];
    }
}
```

The `'default'` key is the safety net — any locale not in the map falls through to it.

---

## Global Filament defaults

`BaseFilamentServiceProvider` is an application-side provider that applies HiTaqnia's shared Filament UX defaults across every panel. Subclass it in your application, register the subclass in `bootstrap/providers.php`, and the defaults boot once per request.

```php
namespace App\Providers;

use HiTaqnia\Haykal\Filament\BaseFilamentServiceProvider;

final class FilamentServiceProvider extends BaseFilamentServiceProvider
{
    // Inherit every default. Override individual configure*() hooks
    // when an app needs to relax or extend a single concern.
}
```

### What the base applies

| Hook | Default behavior |
|---|---|
| `configureModalDefaults()` | `ModalComponent::closedByClickingAway(false)` — modals never auto-close on outside click. |
| `configureActionDefaults()` | `CreateAction` defaults to `createAnother(false)`. |
| `configureTableDefaults()` | Column manager and filters render in a slide-over with no cancel button; filters use `FiltersLayout::Modal`. |
| `configureTextDefaults()` | `TextColumn` and `TextEntry` get an em-dash (`—`) placeholder, plus a "Click to copy" tooltip on copyable instances. The tooltip resolves through the package translation namespace `haykal-filament::copyable.tooltip` (English / Arabic / Kurdish ship in the box). |

Per-call configuration always wins — calling `->placeholder('...')` or `->tooltip(...)` on a column or entry overrides the default for that instance.

### Extending

Each concern is its own `protected` method, so an app subclass replaces only what it needs:

```php
final class FilamentServiceProvider extends BaseFilamentServiceProvider
{
    // Allow modals to close on outside click in this app.
    protected function configureModalDefaults(): void
    {
        ModalComponent::closedByClickingAway(true);
    }

    // Add an extra default on top of the base's text defaults.
    protected function configureTextDefaults(): void
    {
        parent::configureTextDefaults();

        TextColumn::configureUsing(static function (TextColumn $column): void {
            $column->size(TextColumn\TextColumnSize::Small);
        });
    }
}
```

To skip a hook entirely, override it with an empty body. To replace a tooltip translation, either change the active locale or publish the package translations and edit them locally:

```bash
php artisan vendor:publish --tag=haykal-filament-translations
```

Published files land at `lang/vendor/haykal-filament/{en,ar,ku}/copyable.php`.

---

## Resources and pages

Every Haykal resource extends `BaseResource` and leverages translation-key conventions for its labels:

```php
namespace App\Panels\Management\Resources;

use App\Models\Unit;
use HiTaqnia\Haykal\Filament\Resources\BaseResource;
use HiTaqnia\Haykal\Filament\Resources\Pages\BaseCreatePage;
use HiTaqnia\Haykal\Filament\Resources\Pages\BaseEditPage;
use HiTaqnia\Haykal\Filament\Resources\Pages\BaseListPage;

final class UnitResource extends BaseResource
{
    protected static ?string $model = Unit::class;

    public static function getPages(): array
    {
        return [
            'index' => BaseListPage::route('/'),
            'create' => BaseCreatePage::route('/create'),
            'edit' => BaseEditPage::route('/{record}/edit'),
        ];
    }
}
```

Labels resolve from `lang/en/panels/management/resources/units.php`:

```php
return [
    'model' => [
        'singular' => 'Unit',
        'plural' => 'Units',
    ],
    'navigation' => [
        'label' => 'Units',
        'group' => 'Property',       // optional
        'parent' => 'Complex',       // optional
    ],
];
```

---

## Theming

`resources/css/base-theme.css` is the single shared theme — an opinionated, fully-skinned Filament look inspired by polished SaaS dashboards: dark near-black sidebar with orange accents, light airy content area, flat surfaces (no shadows except on overlays), rounded corners, and gentle motion. Every panel imports it directly from the vendor directory, so updates flow through `composer update`.

The `haykal:publish-theme` command scaffolds a per-panel theme file; subsequent runs skip existing files unless `--force` is passed. A scaffolded panel theme looks like:

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';
@import '../../../../vendor/hitaqnia/haykal-filament/resources/css/base-theme.css';

@source '../../../../app/Panels/Management/**/*.php';
@source '../../../../resources/views/filament/management/**/*.blade.php';

/* Management panel overrides go below. */
```

Tailwind 4 CSS-first configuration lives entirely in the panel theme file — the package does not publish any Tailwind config. Extend the `@source` list for application-specific content paths.

### Re-skinning via tokens

The base theme exposes its color/radius/elevation system as CSS tokens declared in a `@theme {}` block. Override any of them in a panel's own `@theme {}` block placed *after* the `@import` to re-skin without forking the file:

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';
@import '../../../../vendor/hitaqnia/haykal-filament/resources/css/base-theme.css';

@source '../../../../app/Panels/Management/**/*.php';

@theme {
    --filament-primary-rgb: 16 129 105;   /* swap accent */
    --filament-shell:       #0c1117;      /* darker sidebar */
    --filament-radius:      1rem;         /* rounder cards */
}
```

### Forking

When token overrides aren't enough, publish the file:

```bash
php artisan vendor:publish --tag=haykal-filament-theme
```

After publishing, update each panel theme's `@import` to point at the local copy (`resources/css/haykal/base-theme.css`). **This opts out of upstream updates** — prefer the `@theme {}` token-override approach whenever the change can be expressed that way.

### Token reference

Every variable below is declared in the base theme's `@theme {}` block.

#### Accent

| Token | Default | Purpose |
|---|---|---|
| `--filament-primary-rgb` | `243 107 29` | Brand accent as space-separated RGB channels (mirrors `--color-primary-500`). Used to compose translucent values via `rgb(var(--filament-primary-rgb) / <alpha>)` for selection highlights, focus rings, the sidebar dot glow, and the active tab background. Override together with the Filament primary palette when re-skinning. |

#### Shell — the dark sidebar / app frame

The "shell" tokens describe the dark surface on the left edge: the sidebar background, its borders, and the text colors that sit on top of it.

| Token | Default | Purpose |
|---|---|---|
| `--filament-shell` | `#0e0f12` | Sidebar background. |
| `--filament-shell-soft` | `#17191e` | Slightly lighter shell tone (reserved). |
| `--filament-shell-elevated` | `#1c1e24` | Active sidebar item card background. |
| `--filament-shell-line` | `#23262d` | Hairline borders on the shell. |
| `--filament-shell-line-strong` | `#2a2d35` | Stronger border (active item). |
| `--filament-shell-text` | `#f3f4f6` | Primary text color on the shell. |
| `--filament-shell-muted` | `#9ca3af` | Secondary text / inactive icons. |
| `--filament-shell-dim` | `#6b7280` | Tertiary text (group labels, chevrons). |
| `--filament-shell-on-active` | `#ffffff` | Text and icon color on the active item / logo. |
| `--filament-shell-hover-bg` | `rgb(255 255 255 / 0.025)` | Hover overlay painted on top of the shell. Final color (not alpha) so panels can swap schemes without recomputing math. |
| `--filament-shell-active-bg` | `rgb(255 255 255 / 0.04)` | Active overlay for the user menu / icon-button hover. |
| `--filament-shell-badge-bg` | `rgb(255 255 255 / 0.06)` | Background for sidebar counters (badges). |
| `--filament-shell-active-shadow` | inset stack | Inset shadow applied to the active sidebar item to give it a subtle "pressed card" feel. |

#### Canvas — the light content area

The "canvas"/"surface" tokens describe everything to the right of the sidebar: the page background, cards, table chrome, dividers, and text colors on top of them.

| Token | Default | Purpose |
|---|---|---|
| `--filament-canvas` | `#ffffff` | Page background. |
| `--filament-surface` | `#ffffff` | Card / section / table body background. |
| `--filament-surface-soft` | `#fafbfc` | Subtle alternate surface (table head, hover row). |
| `--filament-line` | `#eef0f3` | Default hairline divider. |
| `--filament-line-strong` | `#e2e6ec` | Stronger border (inputs, gray buttons). |
| `--filament-ink` | `#0f1620` | Primary text color on canvas. |
| `--filament-ink-soft` | `#4a5568` | Body text, table cells. |
| `--filament-ink-muted` | `#8a96a8` | Secondary text, placeholders, hints. |
| `--filament-input-border-hover` | `#c8ced7` | Border color of an input on hover (between idle and focused). |

#### Status

| Token | Default | Purpose |
|---|---|---|
| `--filament-danger` | `rgb(220 38 38)` | Default destructive action color. |
| `--filament-danger-strong` | `rgb(185 28 28)` | Hover / active state for destructive actions. |

#### Radius

| Token | Default | Purpose |
|---|---|---|
| `--filament-radius-sm` | `0.625rem` | Buttons, inputs, pills. |
| `--filament-radius` | `0.875rem` | Cards, sections, tables. |
| `--filament-radius-lg` | `1.125rem` | Large surfaces (reserved). |

#### Elevation

The theme is intentionally flat — no ambient shadows on resting surfaces. Only floating layers (modals, dropdowns, notifications) get a single soft lift.

| Token | Default | Purpose |
|---|---|---|
| `--filament-shadow-none` | `0 0 #0000` | Explicit zero shadow (use to neutralize Filament defaults). |
| `--filament-shadow-overlay` | soft lift | Single shadow applied to floating layers (dropdowns, notifications). |
| `--filament-focus-ring` | translucent halo | Focus ring placed around inputs / buttons on focus, derived from the brand accent. |

#### Motion

| Token | Default | Purpose |
|---|---|---|
| `--filament-ease` | `cubic-bezier(0.32, 0.72, 0.32, 1)` | Shared easing curve for hover transitions across buttons, sidebar items, sort headers, dropdowns, etc. |

---

## Translatable forms

Multi-language form fields belong inside `TranslatableTabs`:

```php
use HiTaqnia\Haykal\Filament\Forms\TranslatableTabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

TranslatableTabs::make('translations')
    ->languages(config('app.supported_locales'))
    ->primaryLanguage('ar')
    ->requirePrimaryLanguageOnly()
    ->fields([
        TextInput::make('name')->required(),
        Textarea::make('description'),
    ]);
```

The component clones each declared field per locale, binding state paths to `<field>.<locale>`. Tab icons reflect state at a glance: filled (check), required-but-empty (warning), or optional-and-empty (dashed circle).

---

## Mapbox components

Four components cover every Mapbox use case encountered across HiTaqnia projects. All four share the same fluent configuration API through the `InteractsWithMapbox` trait.

### Configuration

Add a Mapbox access token to `.env`:

```dotenv
MAPBOX_TOKEN=pk.your-mapbox-access-token
```

Publish the config only when overrides are needed — the token is already read from `MAPBOX_TOKEN` through the package default:

```bash
php artisan vendor:publish --tag=haykal-filament-mapbox-config
```

### Location picker (form field)

Captures a `{lng, lat}` coordinate pair from a draggable marker. Binds to a JSON column or any cast that accepts an associative array.

```php
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationPicker;

MapboxLocationPicker::make('coordinates')
    ->mapStyle('mapbox://styles/mapbox/streets-v12')
    ->mapCenter([44.3661, 33.3152])  // Baghdad
    ->mapZoom(12)
    ->mapHeight(500)
    ->navigationControl();
```

Stored shape:

```json
{ "lng": 44.3712, "lat": 33.3203 }
```

### Location viewer (infolist entry)

Renders a stored `{lng, lat}` as a read-only map marker.

```php
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationViewer;

MapboxLocationViewer::make('coordinates')
    ->mapStyle('mapbox://styles/mapbox/satellite-v9')
    ->mapHeight(400);
```

### Polygons drawer (form field)

Captures a GeoJSON `FeatureCollection` of user-drawn polygons. `maxPolygons()` caps how many the user can create; `-1` (the default) means unlimited.

```php
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsDrawer;

MapboxPolygonsDrawer::make('boundaries')
    ->maxPolygons(3)
    ->mapCenter([44.3661, 33.3152])
    ->mapZoom(13);
```

Stored shape:

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": { "type": "Polygon", "coordinates": [[[...]]] },
      "properties": {}
    }
  ]
}
```

### Polygons viewer (infolist entry)

Renders a stored `FeatureCollection` read-only and auto-fits the map to the polygons.

```php
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsViewer;

MapboxPolygonsViewer::make('boundaries')
    ->mapHeight(500);
```

### Shared fluent API

Every Mapbox component accepts these setters through `InteractsWithMapbox`:

| Setter | Default | Purpose |
|---|---|---|
| `mapContainer(string)` | Field name | DOM id of the map container. |
| `mapHeight(int\|Closure)` | `400` | Pixel height of the map. |
| `mapStyle(string)` | Mapbox default | Any `mapbox://styles/...` URL. |
| `mapCenter(array\|Closure)` | `[-74.5, 40]` | `[lng, lat]` for the initial viewport. Ignored when state is loaded and polygons auto-fit. |
| `mapZoom(int\|Closure)` | `9` | Initial zoom level. |
| `navigationControl(bool\|Closure)` | `false` | Show Mapbox's zoom/compass controls. |

### Reactive configuration via closures

`mapHeight`, `mapCenter`, `mapZoom`, and `navigationControl` accept either a static value or a Filament-style closure. Closures resolve the same named/typed injections as any other Filament closure (`$state`, `$get`, `$set`, `$record`, `$livewire`, …) and are re-evaluated on every render — pair them with a `live()` source field to drive the map from another component:

```php
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationPicker;

Select::make('city')
    ->options([
        'baghdad' => 'Baghdad',
        'erbil'   => 'Erbil',
    ])
    ->live();

MapboxLocationPicker::make('coordinates')
    ->mapCenter(fn (Get $get): array => match ($get('city')) {
        'baghdad' => [44.3661, 33.3152],
        'erbil'   => [44.0090, 36.1900],
        default   => [-74.5, 40.0],
    })
    ->mapZoom(fn (Get $get): int => $get('city') ? 12 : 9);
```

Switching the `city` Select re-renders the picker with a fresh center and zoom — no manual `afterStateUpdated()` plumbing required.

### Assets

Both the Mapbox GL CSS (`mapbox-gl.css`, `mapbox-gl-draw.css`) and the compiled Alpine bundles for each component are registered with Filament automatically via `HaykalFilamentServiceProvider`. The bundles are loaded on request — pages that do not reference a Mapbox component pay no runtime cost.

### Right-to-left text

Every component initializes [`mapbox-gl-rtl-text`](https://github.com/mapbox/mapbox-gl-rtl-text) on the first map render, so Arabic, Hebrew, and Persian labels join correctly instead of rendering as disconnected glyphs. The plugin is registered with lazy-loading (the third argument to `setRTLTextPlugin`), so its script is only fetched the first time a tile actually contains RTL text — non-RTL maps pay nothing.

The registration is idempotent across all four components (Mapbox throws if `setRTLTextPlugin` runs twice), and the plugin URL is pinned to the version embedded in the package bundles. No application configuration is required.

---

## Customization

### Overriding hooks

Every override point is method-based — subclass `BasePanel`, override the relevant hooks, and the rest inherits from the base. See the [Hooks exposed by `BasePanel`](#hooks-exposed-by-basepanel) table above.

### Publishable resources

| Tag | Contents |
|---|---|
| `haykal-filament-theme` | `resources/css/base-theme.css` → `resources/css/haykal/base-theme.css` (fork only when the base needs app-specific overrides; prefer overriding individual tokens via `@theme {}`). |
| `haykal-filament-icons` | `config/haykal-filament-icons.php` → `config/haykal-filament-icons.php` |
| `haykal-filament-mapbox-config` | `config/mapbox.php` → `config/mapbox.php` (only needed when overriding the default `MAPBOX_TOKEN` env binding). |
| `haykal-filament-stubs` | `stubs/panel-theme.css.stub` → `resources/stubs/haykal/panel-theme.css.stub` |
| `haykal-filament-translations` | `lang/{en,ar,ku}/{auth,copyable}.php` → `lang/vendor/haykal-filament/{en,ar,ku}/...` (override the consent-login texts and the "Click to copy" tooltip strings). |

### Fixed conventions

| Convention | Current value | Location |
|---|---|---|
| Resource directory layout | `app/Panels/<Name>/{Resources,Pages,Widgets,Clusters}` | `BasePanel::panel()` |
| Translation key prefix | `panels/{panel}/resources/{resource-kebab-plural}` | `BaseResource::getTranslationKeyPrefix()` |

---

## Testing

Run the monorepo suite from the repository root:

```bash
composer test
```

Tests use Orchestra Testbench with an in-memory SQLite database. Filament and Livewire service providers must be registered on the test case's `getPackageProviders()` list; see `tests/Filament/FilamentTestCase.php` for the canonical setup.
