# hitaqnia/haykal-core

The shared kernel for HiTaqnia Laravel applications.

`haykal-core` provides the classes, migrations, and ecosystem wiring that every HiTaqnia project relies on: a typed Result pattern, multi-tenancy primitives, Spatie Media Library path generation, and the canonical `User`, `Role`, `Permission`, and `Tenant` models integrated with the Huwiya Identity Provider.

Installing this package replaces Laravel's default authentication scaffolding end-to-end. The sections below describe exactly what is provided, how to configure it, and which Laravel defaults to remove.

---

## Table of contents

- [Requirements](#requirements)
- [What this package provides](#what-this-package-provides)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Customization](#customization)
- [Testing](#testing)

---

## Requirements

- PHP 8.3 or later
- Laravel 13 or later
- A running instance of the Huwiya Identity Provider

---

## What this package provides

### Classes

| Namespace | Description |
|---|---|
| `HiTaqnia\Haykal\Core\ResultPattern\Result`, `Error` | Typed `Result<T>` outcome and companion `Error` value object. Use in place of exceptions for expected, recoverable failures. |
| `HiTaqnia\Haykal\Core\Tenancy\Tenancy` | Process-local tenant context resolver. Exposes `setTenantId()`, `getTenantId()`, `clear()`. |
| `HiTaqnia\Haykal\Core\Tenancy\TenantScope` | Global Eloquent scope that constrains queries to the active tenant. Rows with a `NULL` tenant remain visible as shared records. |
| `HiTaqnia\Haykal\Core\Tenancy\Models\Tenant` | Abstract base model for application tenants. Concrete tenants (`Complex`, `Organization`, `Workspace`, …) extend this class. |
| `HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant` | Trait that marks an Eloquent model as tenant-owned. Applies `TenantScope`, auto-populates `tenant_id`, and declares the `tenant()` relation. |
| `HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware` | Forwards the active tenant into Spatie's `setPermissionsTeamId()`. Registered under the alias `haykal.permissions.team`. |
| `HiTaqnia\Haykal\Core\Http\Middlewares\SetUserLocaleMiddleware` | Applies the authenticated user's stored `locale` to the current request. Registered under the alias `haykal.user.locale`. |
| `HiTaqnia\Haykal\Core\MediaLibrary\CustomPathGenerator` | Spatie Media Library path generator. User-owned media is stored under `identity/<uuid>/`; all other media is stored under `<tenantId>/<uuid>/`. |
| `HiTaqnia\Haykal\Core\Identity\Models\User` | Authenticatable model. Uses `Huwiya\InteractsWithHuwiya`, Spatie `HasRoles`, Media Library, and soft deletes. Profile fields are synced from Huwiya claims on every login. |
| `HiTaqnia\Haykal\Core\Identity\Models\Role`, `Permission` | Spatie Role and Permission extended with `HasUlids`. |
| `HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber` | Iraqi phone number in E.164 canonical form with readable and compact formatters. |
| `HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast` | Eloquent cast for phone columns. |
| `HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule` | Validation rule for the input shapes the value object normalizes. |
| `HiTaqnia\Haykal\Core\Identity\QueryBuilders\UserQueryBuilder` | Adds `wherePhoneNumber()` and `getByPhoneNumber()` to `User::query()`. |
| `HiTaqnia\Haykal\Core\Database\Factories\UserFactory` | Default factory so `User::factory()->create()` works out of the box. |

### Migrations

`haykal-core` ships its own migrations and loads them automatically from the package directory. They may also be published via the tag `haykal-core-migrations` for customization.

| Migration | Tables created |
|---|---|
| `create_users_table` | `users` (ULID id, `huwiya_id`, `name`, `phone`, `email`, `locale`, `zoneinfo`, `theme`, soft deletes), `sessions` |
| `create_permission_tables` | Spatie permission schema with ULID primary keys. The team columns are created automatically when the consuming app sets `permission.teams = true`; the same migration runs cleanly with teams disabled. |
| `create_media_table` | Spatie Media Library schema with ULID morphs |
| `create_notifications_table` | Standard Laravel notifications table with a ULID morph target |

The `users` table deliberately omits `password`, `is_admin`, MFA columns, Sanctum tokens, and device tracking — all are handled by the Huwiya Identity Provider.

### Ecosystem dependencies

Installing `haykal-core` pulls in the packages below. Each is configured through its own upstream documentation; `haykal-core` does not wrap their configuration files.

- [`hitaqnia/huwiya-laravel`](https://github.com/hitaqnia/huwiya-laravel) — sole authentication mechanism.
- [`spatie/laravel-data`](https://spatie.be/docs/laravel-data) — data transfer objects.
- [`spatie/laravel-permission`](https://spatie.be/docs/laravel-permission) — roles and permissions. Teams are off by default; enable them per application when roles must scope to the active tenant.
- [`spatie/laravel-medialibrary`](https://spatie.be/docs/laravel-medialibrary) — file attachments.
- [`spatie/laravel-translatable`](https://spatie.be/docs/laravel-translatable) — translatable model attributes.
- [`laravel/horizon`](https://laravel.com/docs/horizon) — queue monitoring.
- [`laravel-notification-channels/fcm`](https://github.com/laravel-notification-channels/fcm) — push notifications.
- [`league/flysystem-aws-s3-v3`](https://flysystem.thephpleague.com/docs/adapter/aws-s3-v3/) + [`predis/predis`](https://github.com/predis/predis).

---

## Installation

Until the suite is published, consume the package via a Composer path repository. In the consuming application's `composer.json`:

```json
{
    "repositories": [
        { "type": "path", "url": "/absolute/path/to/haykal/packages/haykal-core" },
        { "type": "path", "url": "/absolute/path/to/huwiya/packages/huwiya-laravel" }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then require the package:

```bash
composer require hitaqnia/haykal-core:@dev
```

---

## Configuration

Each step below is required. The order matters: remove conflicting defaults first, then wire up the classes shipped by this package.

### 1. Remove Laravel's conflicting default migration

Fresh Laravel installations ship a users migration that conflicts with the one provided by `haykal-core`. Delete it before running migrations:

```bash
rm database/migrations/0001_01_01_000000_create_users_table.php
```

Retain the remaining Laravel defaults — they do not conflict with anything this package ships:

- `0001_01_01_000001_create_cache_table.php`
- `0001_01_01_000002_create_jobs_table.php`

### 2. Configure the authentication user provider

In `config/auth.php`, point Laravel's user provider at the Haykal `User` model (or at your own subclass of it):

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => \HiTaqnia\Haykal\Core\Identity\Models\User::class,
    ],
],
```

### 3. Publish the Spatie Permission configuration

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag=permission-config
```

Edit `config/permission.php` to point Spatie at Haykal's ULID-keyed models (or at your own subclasses of them):

```php
'models' => [
    'permission' => \HiTaqnia\Haykal\Core\Identity\Models\Permission::class,
    'role'       => \HiTaqnia\Haykal\Core\Identity\Models\Role::class,
],

// 'teams' is OFF by default. Enable only when roles must scope per tenant —
// apps without multi-tenancy should leave it disabled. Flipping this flag
// after migrations have run requires `migrate:fresh` so the pivot tables
// pick up the team_id columns.
'teams' => false,
```

Applications that enable teams must also slot the `haykal.permissions.team` middleware after whichever middleware resolves the active tenant — it is a no-op otherwise.

Do **not** publish Spatie's permission migration — `haykal-core` ships a ULID-keyed variant that runs automatically and adapts to the `teams` flag.

### 4. Publish the Media Library configuration

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag=medialibrary-config
```

Edit `config/media-library.php`:

```php
'path_generator' => \HiTaqnia\Haykal\Core\MediaLibrary\CustomPathGenerator::class,
```

Do **not** publish Spatie Media Library's migration — `haykal-core` ships a ULID-morph variant that runs automatically.

### 5. Configure the Huwiya SDK

Follow the [`huwiya-laravel` documentation](https://github.com/hitaqnia/huwiya-laravel) to publish its configuration and set the required environment variables:

```
HUWIYA_URL=https://idp.example.com
HUWIYA_PROJECT_ID=your-project-id
HUWIYA_CLIENT_ID=...
HUWIYA_CLIENT_SECRET=...
HUWIYA_REDIRECT_URI=https://your-app.example.com/huwiya/callback
```

Register the Huwiya guard drivers in `config/auth.php` as documented by the SDK (`huwiya-web` for session-based panels, `huwiya-api` for stateless APIs).

### 6. Wire the Haykal middlewares

In `bootstrap/app.php`, append the middlewares shipped by this package to the appropriate groups. The permissions-team middleware must run **after** whichever middleware resolves the active tenant.

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', [
        'haykal.permissions.team',
        'haykal.user.locale',
    ]);

    $middleware->appendToGroup('api', [
        'haykal.permissions.team',
        'haykal.user.locale',
    ]);
})
```

### 7. Run migrations

```bash
php artisan migrate
```

---

## Usage

### Result pattern

```php
use HiTaqnia\Haykal\Core\ResultPattern\Error;
use HiTaqnia\Haykal\Core\ResultPattern\Result;

return $user !== null
    ? Result::success($user)
    : Result::failure(Error::make(code: 404, message: 'User not found.'));
```

### Tenancy

Set the active tenant early in the request lifecycle via a middleware that resolves it from a Filament panel, subdomain, header, or route parameter:

```php
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;

Tenancy::setTenantId($tenant->getKey());
```

Define the application's concrete tenant model by extending the Haykal base class:

```php
use HiTaqnia\Haykal\Core\Tenancy\Models\Tenant;

class Complex extends Tenant
{
    // Application-specific columns, relations, and accessors.
}
```

Mark tenant-owned models with the `HasTenant` trait:

```php
use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasTenant;

    protected string $tenantModel = Complex::class;
}
```

The trait applies `TenantScope` as a global scope, fills the tenant foreign key automatically on creation when a tenant is active, and exposes a `tenant()` relation targeting the class named in `$tenantModel`. Rows whose tenant FK is `NULL` remain visible across all tenants.

#### Multiple tenant types

Applications that expose several tenant types (for example, **Agency** and **Development Company**, each with its own table, relations, and foreign key column) extend `Tenant` once per type. Only one tenant type is active per panel or request — Haykal does not maintain a keyed map of active tenants — so every component treats "the active tenant" as a single value.

Each tenanted model declares both its concrete tenant class and its foreign key column:

```php
use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use HiTaqnia\Haykal\Core\Tenancy\Models\Tenant;

class Agency extends Tenant {}
class DevelopmentCompany extends Tenant {}

class Property extends Model
{
    use HasTenant;

    protected string $tenantModel = Agency::class;
    protected string $tenantForeignKey = 'agency_id';
}

class Project extends Model
{
    use HasTenant;

    protected string $tenantModel = DevelopmentCompany::class;
    protected string $tenantForeignKey = 'developer_id';
}
```

The scope reads `$tenantForeignKey` per model, so queries for `Property` filter on `agency_id` and queries for `Project` filter on `developer_id`, each against the single active tenant id set by the request's middleware. Models that omit the property fall back to the package-wide default (`tenant_id`).

The Filament counterpart of this pattern is one panel per tenant type — see `haykal-filament`'s README for how each panel wires its own tenant model.

### Huwiya claim synchronization

The default `User` model syncs the following columns from `TokenClaims` on both account creation and every re-login:

- `name`, `phone`, `email`
- `locale`, `zoneinfo`, `theme`

To customize the mapping, override `attributesFromClaims(TokenClaims $claims): array` on a `User` subclass. A single method governs both the create and update behavior so they cannot drift apart.

### Phone numbers

```php
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;

$phone = new PhoneNumber('07701234567');
$phone->getInternational();                  // +9647701234567
$phone->getInternational(readable: true);    // +964 770 123 4567
$phone->getNational();                       // 07701234567
```

Accepts `+964…`, `00964…`, `0…`, and bare `7XXXXXXXXX` inputs.

### User query helpers

```php
User::query()->wherePhoneNumber('07701234567')->first();
User::query()->getByPhoneNumber('+9647701234567');
```

### Factories

```php
use HiTaqnia\Haykal\Core\Identity\Models\User;

$user = User::factory()->create();
$user = User::factory()->withoutHuwiya()->create();
$user = User::factory()->create(['locale' => 'ar']);
```

---

## Customization

Every customization point exposed by `haykal-core` itself is listed below. Knobs belonging to upstream packages (Spatie, Huwiya, Horizon, …) are configured through their own published configuration files.

### Model substitution

Apps may substitute any of the shipped Eloquent models with a subclass:

| Model | Where to substitute |
|---|---|
| `User` | `config/auth.php` — `providers.users.model` |
| `Role` | `config/permission.php` — `models.role` |
| `Permission` | `config/permission.php` — `models.permission` |
| `Tenant` | Apps extend `...\Tenancy\Models\Tenant` directly and reference the concrete subclass from their `HasTenant`-using models |

### Override points on the `User` model

| Method | Purpose |
|---|---|
| `attributesFromClaims(TokenClaims): array` | Columns synced from Huwiya claims on create and update. |
| `shouldAutoRegister(?TokenClaims): bool` | Return `false` for invite-only applications. |
| `resolveHuwiyaConflict(TokenClaims, self, string)` | Policy applied on unique-constraint violations (for example, phone or email recycling). |
| `newHuwiyaQuery()` and `huwiyaQueryForClaims()` | Control how users are located from claims — override to apply tenant scopes, eager-load relations, or accept alternative lookup columns. |
| `getHuwiyaIdentifierColumn(): string` | Column that stores the Huwiya subject identifier. Defaults to `huwiya_id`. |

### Override points on `HasTenant`

| Method / property | Purpose |
|---|---|
| `protected string $tenantModel` | The concrete Tenant class the model belongs to. |
| `protected string $tenantForeignKey` | The foreign key column on this model. Defaults to `tenant_id`; override per-model in multi-tenant-type apps (for example `agency_id`, `developer_id`). |
| `tenantRelationModel(): string` | Overridable for dynamic resolution (for example, reading from configuration). |
| `getTenantForeignKey(): string` | Overridable for dynamic FK resolution. Default reads the `$tenantForeignKey` property. |

### Override points on `CustomPathGenerator`

Extend the generator and override the protected `getBasePath(Media): string` to add application-specific special cases (for example, filing content owned by a domain root model under its own path prefix).

### Middleware aliases

`haykal-core` registers two route-middleware aliases. Apps compose them into whichever middleware groups they need.

| Alias | Middleware |
|---|---|
| `haykal.permissions.team` | `PermissionsTeamMiddleware` |
| `haykal.user.locale` | `SetUserLocaleMiddleware` |

### Publishable resources

| Tag | Contents |
|---|---|
| `haykal-core-migrations` | The package's migration files. Publish only when the schema needs application-specific changes. |

### Fixed conventions

The values below are hardcoded and not exposed through configuration. They can be changed by forking or extending the relevant class when a concrete use case emerges.

| Convention | Current value | Location |
|---|---|---|
| Default tenant foreign key column | `tenant_id` | `TenantScope::FOREIGN_KEY` (per-model overridable via `HasTenant`'s `$tenantForeignKey`) |
| Phone number country scope | Iraq (E.164 `+964…`) | `PhoneNumber::INPUT_REGEX` |

---

## Testing

Run the monorepo test suite from the repository root:

```bash
composer test
```

Tests use Orchestra Testbench with an in-memory SQLite database. The `FakeHuwiyaIdP` fixture (in the monorepo's `tests/Fixtures/`) issues RS256-signed JWTs that the real Huwiya SDK accepts, enabling end-to-end authentication flows without a live Identity Provider.
