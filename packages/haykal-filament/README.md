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

### Middlewares

| Alias | Class | Purpose |
|---|---|---|
| `haykal.filament.tenancy` | `Http\Middlewares\FilamentTenancyMiddleware` | Propagates Filament's resolved tenant into Haykal's `Tenancy` context. |
| `haykal.filament.access` | `Http\Middlewares\AccessCheckingMiddleware` | Enforces the `<panel-id>.access` Gate — aborts 403 if denied. |
| `haykal.filament.locale` | `Http\Middlewares\SetPanelLocale` | Applies a session-persisted locale (`current_lang`) for every panel request. |

### Theming and assets

| Resource | Tag / Command |
|---|---|
| `resources/css/base-theme.css` | Published via `php artisan haykal:publish-theme <panel>` or the `haykal-filament-theme` tag. |
| `config/haykal-filament-icons.php` | Merged into `config.filament.icons` on register; publishable under `haykal-filament-icons`. |
| `stubs/panel-theme.css.stub` | Publishable under `haykal-filament-stubs`; consumed by the publish-theme command. |

### Console commands

| Command | Purpose |
|---|---|
| `haykal:publish-theme {panel} {--force}` | Copies the base theme to `resources/css/haykal/base-theme.css` and scaffolds a per-panel theme entry file at `resources/css/filament/<panel>/theme.css` with the correct Tailwind 4 `@source` directives. |

---

## Installation

With `hitaqnia/haykal-core` already wired (see its README), require this package alongside:

```bash
composer require hitaqnia/haykal-filament:@dev
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

### 2. Scaffold the base theme

Run the publish-theme command once per panel:

```bash
php artisan haykal:publish-theme admin
```

The command copies the shared base theme to `resources/css/haykal/base-theme.css` and scaffolds `resources/css/filament/admin/theme.css` with the correct Tailwind `@source` directives. Register the panel theme in `vite.config.js`:

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

Every panel has its own theme entry file that `@import`s the shared base theme and adds panel-specific styles. The `haykal:publish-theme` command scaffolds both; subsequent runs skip existing files unless `--force` is passed.

Tailwind 4 CSS-first configuration lives entirely in the panel theme file — the package does not publish any Tailwind config. The scaffolded panel theme includes `@source` directives for `app/Panels/<Name>`, `app/Filament/<Name>`, and `resources/views/panels/<name>` out of the box; extend the list for application-specific source paths.

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

## Customization

### Overriding hooks

Every override point is method-based — subclass `BasePanel`, override the relevant hooks, and the rest inherits from the base. See the [Hooks exposed by `BasePanel`](#hooks-exposed-by-basepanel) table above.

### Plugin composition

`HaykalPlugin::make()->withTranslatableTabs()->withAccessChecking()` can be attached per-panel to opt into optional behaviors on top of the defaults that `BasePanel` installs.

### Publishable resources

| Tag | Contents |
|---|---|
| `haykal-filament-theme` | `resources/css/base-theme.css` → `resources/css/haykal/base-theme.css` |
| `haykal-filament-icons` | `config/haykal-filament-icons.php` → `config/haykal-filament-icons.php` |
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
