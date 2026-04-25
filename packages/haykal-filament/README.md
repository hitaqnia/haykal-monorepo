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
- [Resources and pages](#resources-and-pages)
- [Theming](#theming)
- [Translatable forms](#translatable-forms)
- [Mapbox components](#mapbox-components)
- [ViewerJS image gallery](#viewerjs-image-gallery)
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
| `HiTaqnia\Haykal\Filament\HaykalPlugin` | Per-panel Filament plugin with fluent toggles for the Spatie translatable plugin and panel-access Gate check. |
| `HiTaqnia\Haykal\Filament\Auth\HuwiyaRedirectLogin` | Login page that immediately redirects to the Huwiya OAuth authorization endpoint for the active panel's guard. |

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

### ViewerJS

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Filament\ViewerJs\Components\ImageGallery` | Infolist entry that renders a grid of images with ViewerJS lightbox support. Accepts an array or closure. |

### Middlewares

| Alias | Class | Purpose |
|---|---|---|
| `haykal.filament.tenancy` | `Http\Middlewares\FilamentTenancyMiddleware` | Propagates Filament's resolved tenant into Haykal's `Tenancy` context. |
| `haykal.filament.access` | `Http\Middlewares\AccessCheckingMiddleware` | Enforces the `<panel-id>.access` Gate — aborts 403 if denied. |
| `haykal.filament.locale` | `Http\Middlewares\SetPanelLocale` | Applies a session-persisted locale (`current_lang`) for every panel request. |

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

Install the Phosphor icon library (or Tabler) so the icon overrides resolve at runtime:

```bash
composer require codeat3/blade-phosphor-icons
```

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

### 4. Register panel-access Gates

Each panel that uses the `haykal.filament.access` middleware must register a corresponding Gate in the application's `AuthServiceProvider`:

```php
Gate::define('admin.access', fn (User $user) => $user->is_admin);
Gate::define('management.access', fn (User $user) => $user->employments()->active()->exists());
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
| `loginPage()` | `HuwiyaRedirectLogin::class` | Override to replace the Huwiya OAuth redirect with a custom page. |
| `tenantModel()` | `null` | Concrete Tenant model for this panel, or `null` for tenant-less panels. |
| `tenantSlugAttribute()` | `null` | Column on the tenant model used as the URL slug. `null` falls back to the primary key. |
| `defaultPlugins()` | SpatieTranslatable | Override to install a different base plugin set. |
| `getName()` | `Str::studly(id)` | Derives the directory name for resource discovery. Override for custom layouts. |

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

Every panel has its own theme entry file that `@import`s the shared base theme from the vendor directory and adds panel-specific styles below. The `haykal:publish-theme` command scaffolds the panel file; subsequent runs skip existing files unless `--force` is passed.

**The base theme is never copied into the application.** The scaffolded panel theme references `vendor/hitaqnia/haykal-filament/resources/css/base-theme.css` directly, so updates to the shared theme flow to every panel via `composer update` — no republishing, no drift between panels or applications.

A scaffolded panel theme looks like:

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';
@import '../../../../vendor/hitaqnia/haykal-filament/resources/css/base-theme.css';

@source '../../../../app/Panels/Management/**/*.php';
@source '../../../../resources/views/filament/management/**/*.blade.php';

/* Management panel overrides go below. */
```

Tailwind 4 CSS-first configuration lives entirely in the panel theme file — the package does not publish any Tailwind config. Extend the `@source` list for application-specific content paths.

### Forking the base theme

Applications that need to change the shared base CSS across every panel can publish the file explicitly:

```bash
php artisan vendor:publish --tag=haykal-filament-theme
```

After publishing, update each panel theme's `@import` to point at the local copy (`resources/css/haykal/base-theme.css`). **This opts out of upstream updates** — prefer extending the panel theme files instead whenever possible so the shared base continues to receive package updates.

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
| `mapHeight(int)` | `400` | Pixel height of the map. |
| `mapStyle(string)` | Mapbox default | Any `mapbox://styles/...` URL. |
| `mapCenter(array)` | `[-74.5, 40]` | `[lng, lat]` for the initial viewport. Ignored when state is loaded and polygons auto-fit. |
| `mapZoom(int)` | `9` | Initial zoom level. |
| `navigationControl(bool)` | `false` | Show Mapbox's zoom/compass controls. |

### Assets

Both the Mapbox GL CSS (`mapbox-gl.css`, `mapbox-gl-draw.css`) and the compiled Alpine bundles for each component are registered with Filament automatically via `HaykalFilamentServiceProvider`. The bundles are loaded on request — pages that do not reference a Mapbox component pay no runtime cost.

---

## ViewerJS image gallery

`ImageGallery` renders an infolist entry as a grid of image thumbnails. Clicking any thumbnail opens a ViewerJS lightbox over the full set.

```php
use HiTaqnia\Haykal\Filament\ViewerJs\Components\ImageGallery;

// Array form: label => URL
ImageGallery::make('photos')
    ->images([
        'Floor plan' => 'https://cdn.example.com/floor-plan.jpg',
        'Elevation'  => 'https://cdn.example.com/elevation.jpg',
        'Gallery'    => 'https://cdn.example.com/gallery.jpg',
    ]);

// Closure form: resolved on render, receives the Entry's container state
ImageGallery::make('photos')
    ->images(fn ($record) => $record->media()
        ->where('collection_name', 'gallery')
        ->get()
        ->mapWithKeys(fn ($m) => [$m->name => $m->getUrl()])
        ->all());
```

The component's CSS (`viewerjs` from unpkg) and Alpine bundle are registered by `HaykalFilamentServiceProvider` and loaded on request.

---

## Customization

### Overriding hooks

Every override point is method-based — subclass `BasePanel`, override the relevant hooks, and the rest inherits from the base. See the [Hooks exposed by `BasePanel`](#hooks-exposed-by-basepanel) table above.

### Plugin composition

`HaykalPlugin::make()->withTranslatableTabs()->withAccessChecking()` can be attached per-panel to opt into optional behaviors on top of the defaults that `BasePanel` installs.

### Publishable resources

| Tag | Contents |
|---|---|
| `haykal-filament-theme` | `resources/css/base-theme.css` → `resources/css/haykal/base-theme.css` (fork only when the base needs app-specific overrides). |
| `haykal-filament-icons` | `config/haykal-filament-icons.php` → `config/haykal-filament-icons.php` |
| `haykal-filament-mapbox-config` | `config/mapbox.php` → `config/mapbox.php` (only needed when overriding the default `MAPBOX_TOKEN` env binding). |
| `haykal-filament-stubs` | `stubs/panel-theme.css.stub` → `resources/stubs/haykal/panel-theme.css.stub` |

### Fixed conventions

| Convention | Current value | Location |
|---|---|---|
| Resource directory layout | `app/Panels/<Name>/{Resources,Pages,Widgets,Clusters}` | `BasePanel::panel()` |
| Translation key prefix | `panels/{panel}/resources/{resource-kebab-plural}` | `BaseResource::getTranslationKeyPrefix()` |
| Locale session key | `current_lang` | `SetPanelLocale` |

---

## Testing

Run the monorepo suite from the repository root:

```bash
composer test
```

Tests use Orchestra Testbench with an in-memory SQLite database. Filament and Livewire service providers must be registered on the test case's `getPackageProviders()` list; see `tests/Filament/FilamentTestCase.php` for the canonical setup.
