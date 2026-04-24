# hitaqnia/haykal-core

The shared kernel for HiTaqnia Laravel applications.

`haykal-core` centralizes the cross-project primitives that every hitaqnia Laravel app relies on:

- A typed `Result` pattern for recoverable failures.
- Multi-tenancy primitives (context resolver, global scope, model trait).
- A phone number value object with Eloquent cast + validation rule.
- ULID-keyed Spatie Role/Permission subclasses.
- Two HTTP middlewares (`haykal.permissions.team`, `haykal.user.locale`).
- An **abstract `BaseHuwiyaUser`** that pre-wires every override point the Huwiya SDK exposes with the team's default behavior — subclass this in your app, inherit the defaults, override what you need.
- The matching migrations (users, Spatie permissions, media, notifications).

The Huwiya SDK (`hitaqnia/huwiya-laravel`) ships the auth mechanism; `haykal-core` ships the team's filled-in defaults on top of that mechanism so every new project bootstraps with identical Huwiya behavior.

---

## Table of contents

- [Requirements](#requirements)
- [What this package provides](#what-this-package-provides)
- [Installation](#installation)
- [Configuration](#configuration)
- [Identity model — `BaseHuwiyaUser`](#identity-model--basehuwiyauser)
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
| `HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant` | Trait that marks an Eloquent model as tenant-owned. Applies `TenantScope`, auto-populates the tenant FK on creating, and declares the `tenant()` relation. |
| `HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware` | Forwards the active tenant into Spatie's `setPermissionsTeamId()`. Registered under the alias `haykal.permissions.team`. |
| `HiTaqnia\Haykal\Core\Http\Middlewares\SetUserLocaleMiddleware` | Applies the authenticated user's stored `locale` to the current request. Registered under the alias `haykal.user.locale`. |
| `HiTaqnia\Haykal\Core\Identity\Models\BaseHuwiyaUser` | **Abstract** base for the application's User model. Pre-wires Huwiya claim sync + Spatie `HasRoles` + Media Library + soft deletes + ULIDs. Intended to be subclassed. |
| `HiTaqnia\Haykal\Core\Identity\Models\Role`, `Permission` | Spatie Role and Permission extended with `HasUlids`. |
| `HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber` | Iraqi phone number in E.164 canonical form with readable and compact formatters. |
| `HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast` | Eloquent cast for phone columns. |
| `HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule` | Validation rule for the input shapes the value object accepts. |
| `HiTaqnia\Haykal\Core\Identity\QueryBuilders\UserQueryBuilder` | Adds `wherePhoneNumber()` and `getByPhoneNumber()` to `BaseHuwiyaUser::query()` — inherited by every subclass. |

### Migrations

`haykal-core` ships its migrations and loads them automatically from the package directory. Publish with the tag `haykal-core-migrations` if you need to edit them.

| Migration | Tables created |
|---|---|
| `create_users_table` | `users` (ULID id, `huwiya_id`, `name`, `phone`, `email`, `locale`, `zoneinfo`, `theme`, soft deletes, remember token, timestamps) + `sessions` |
| `create_permission_tables` | Spatie permission schema with ULID primary keys. Team columns are created when `permission.teams = true`; the same migration runs cleanly with teams disabled. |
| `create_media_table` | Spatie Media Library table with ULID morph target. |
| `create_notifications_table` | Laravel notifications table with ULID morph target. |

Projects that need additional User columns add their own follow-on migrations (`add_xxx_to_users_table`) rather than editing the published base.

### Ecosystem packages (hard-required)

Installing `haykal-core` pulls in:

- `hitaqnia/huwiya-laravel` — the Huwiya authentication SDK.
- `spatie/laravel-permission` — used by Role / Permission / `HasRoles`.
- `spatie/laravel-medialibrary` — used by `InteractsWithMedia` on `BaseHuwiyaUser`.
- `spatie/laravel-data`, `spatie/laravel-translatable` — used by every hitaqnia project's domain layer; required here so apps get them automatically.
- `laravel/horizon` — queue monitoring.
- `league/flysystem-aws-s3-v3` — S3 storage adapter.
- `predis/predis` — Redis client.

Configuration for every upstream package follows their own published documentation. `haykal-core` does not wrap or re-document them.

---

## Installation

```bash
composer require hitaqnia/haykal-core
```

Auto-discovered via `HaykalCoreServiceProvider`. The provider loads the package's migrations, publishes the migration tag, and registers the two middleware aliases.

---

## Configuration

Follow the upstream documentation for each ecosystem package:

- **Huwiya SDK** — set the env variables listed in the SDK's README and register the `huwiya-web` / `huwiya-api` guards in `config/auth.php`.
- **Spatie permission** — publish the Spatie config, set `models.role` / `models.permission` to haykal's ULID subclasses, and set `teams` to `true` or `false` per app requirements.
- **Spatie Media Library** — publish its config and configure your disks.
- **Horizon** — publish and configure per its docs.

Then publish the haykal-core migrations and run:

```bash
php artisan vendor:publish --tag=haykal-core-migrations
php artisan migrate
```

---

## Identity model — `BaseHuwiyaUser`

This is where the team's Huwiya defaults live. Subclass it once in your app and you're done.

### Creating your `User`

```php
namespace Domain\Identity\Models;

use Domain\Identity\Database\Factories\UserFactory;
use HiTaqnia\Haykal\Core\Identity\Models\BaseHuwiyaUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends BaseHuwiyaUser
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    // Add relations, observers, scopes, additional fillables, etc.
}
```

Point `config/auth.php`'s `users` provider at this class:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', Domain\Identity\Models\User::class),
    ],
],
```

### What `BaseHuwiyaUser` pre-wires

| Concern | Default |
|---|---|
| Authentication | Huwiya OAuth / JWT via the SDK's `InteractsWithHuwiya` trait. No local password column, no Sanctum tokens. |
| Primary key | ULID (`HasUlids`). |
| Soft deletes | On. |
| Permissions | Spatie `HasRoles` (use a ULID-keyed `Role` / `Permission` via haykal-core's subclasses). |
| Media attachments | Spatie Media Library `InteractsWithMedia`. |
| Fillables | `name`, `phone`, `email`, `locale`, `zoneinfo`, `theme`. (`huwiya_id` is added automatically by the SDK trait.) |
| Casts | `phone` → `PhoneNumberCast`. |
| Query builder | `UserQueryBuilder` — `wherePhoneNumber()` / `getByPhoneNumber()` are available on every subclass. |

### Huwiya SDK hook defaults

The Huwiya SDK exposes eight policy hooks on `InteractsWithHuwiya`. `BaseHuwiyaUser` fills in sensible team defaults for the claim-sync pair and leaves the rest at the SDK's defaults — override any of them on your subclass to change behavior.

| SDK hook | `BaseHuwiyaUser` default | Typical reason to override |
|---|---|---|
| `getHuwiyaIdentifierColumn()` | `'huwiya_id'` | Rename the column. |
| `shouldAutoRegister($claims)` | `true` (SDK default — accept every valid token) | Invite-only apps: check an invitation table. |
| `newHuwiyaQuery()` | `static::query()` (SDK default) | Apply tenancy scopes, eager loads, include trashed rows. |
| `huwiyaQueryForClaims($q, $claims)` | match by `huwiya_id` (SDK default) | Fall back to phone/email on first login, multi-tenant lookup. |
| `getHuwiyaCreateAttributes($claims)` | **Sync** `name`, `phone`, `email`, `locale`, `zoneinfo`, `theme` | Sync a different column set. |
| `getHuwiyaUpdateAttributes($claims)` | **Same as create** — columns stay in step on every re-login | Skip updates on re-login (return `[]`); freeze certain columns. |
| `getHuwiyaConflictColumns()` | `['phone', 'email']` (SDK default) | Add/remove recyclable columns. |
| `resolveHuwiyaConflict(...)` | Throw `HuwiyaConflictException` (SDK default) | Phone/email recycling: null the old row's column, soft-delete the old user, transfer ownership, etc. |

Example — invite-only app with email recycling:

```php
use Huwiya\TokenClaims;
use HiTaqnia\Haykal\Core\Identity\Models\BaseHuwiyaUser;

class User extends BaseHuwiyaUser
{
    public function shouldAutoRegister(?TokenClaims $claims = null): bool
    {
        return $claims !== null && Invitation::query()
            ->where('email', $claims->email)
            ->whereNull('accepted_at')
            ->exists();
    }

    public function resolveHuwiyaConflict(
        TokenClaims $claims,
        self $existingRow,
        string $conflictingColumn,
    ): void {
        // Old user keeps their account but loses the recycled contact.
        $existingRow->update([$conflictingColumn => null]);
    }
}
```

### Why abstract

`BaseHuwiyaUser` is abstract to force explicit ownership of the concrete User class in the consuming app. The application — not the package — names its auth model, wires its factory, and adds its relations. The package's job is to centralize the shared skeleton so every project starts from the same place.

When a future auth mode is needed (say, username/password without Huwiya), a parallel `BasePasswordUser` class ships alongside. Projects pick the base that matches; existing projects pinned to `BaseHuwiyaUser` are untouched.

---

## Usage

### Result pattern

```php
use HiTaqnia\Haykal\Core\ResultPattern\Error;
use HiTaqnia\Haykal\Core\ResultPattern\Result;

$result = Result::success($user);
// or
$result = Result::failure(Error::make(code: 4001, message: 'Booking overlap.'));

if ($result->isSuccess()) {
    $user = $result->getData();
} else {
    $error = $result->getError();
}
```

### Tenancy

```php
use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;

Tenancy::setTenantId($complex->id);

// Query scopes automatically filter to the active tenant.
Property::query()->get();

// Tenant-owned models declare their tenant relation + FK.
class Property extends Model
{
    use HasTenant;

    protected string $tenantModel = Complex::class;
    protected string $tenantForeignKey = 'complex_id';
}
```

### Phone numbers

```php
use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;

$phone = new PhoneNumber('07701234567');
$phone->getInternational();           // "+9647701234567"
$phone->getInternational(readable: true); // "+964 770 123 4567"
$phone->getNational();                // "07701234567"

// Validation rule
'phone' => ['required', new PhoneNumberRule],
```

The phone column on `BaseHuwiyaUser` is cast to `PhoneNumber` automatically — reading `$user->phone` returns the value object; writing accepts a string or VO.

### Middlewares

Assign in `bootstrap/app.php`:

```php
use HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware;
use HiTaqnia\Haykal\Core\Http\Middlewares\SetUserLocaleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', [SetUserLocaleMiddleware::class]);
        $middleware->appendToGroup('api', [SetUserLocaleMiddleware::class]);

        // After your tenancy resolver:
        $middleware->appendToGroup('web', [PermissionsTeamMiddleware::class]);
    })
    ->create();
```

Or via the aliases `haykal.user.locale` and `haykal.permissions.team`.

---

## Customization

### Publishable resources

| Tag | Contents |
|---|---|
| `haykal-core-migrations` | The four package migrations. |

### Overriding migrations

Publish, edit, and re-run:

```bash
php artisan vendor:publish --tag=haykal-core-migrations --force
php artisan migrate:fresh
```

The package's own migration copies continue to load via `loadMigrationsFrom` — so publishing is only needed when you want to customize. If you do publish, disable the package-loaded copy to prevent duplicate runs. (See Laravel's migration docs for this pattern.)

### Custom tenant foreign key per model

Declare `$tenantForeignKey` per model — `HasTenant` reads it through `getTenantForeignKey()`. Useful when different models belong to different tenant types (`agency_id` on `properties`, `developer_id` on `projects`).

---

## Testing

The monorepo ships `HiTaqnia\Haykal\Tests\Core\CoreTestCase` (Testbench-backed) and a `TestHuwiyaUser` / `TestHuwiyaUserFactory` fixture pair for exercising the abstract base. Consuming applications point their `auth.providers.users.model` at their own concrete `User` and run their own factories.

Run the monorepo suite:

```bash
composer test
```
