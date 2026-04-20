# haykal-core

The shared kernel for every HiTaqnia Laravel app.

`hitaqnia/haykal-core` consolidates the bits every project uses — the Result pattern, tenancy utilities, the HTTP middleware that ties roles/permissions to the active tenant, Spatie Media Library path generation, and the canonical User/Identity model integrated with the Huwiya SDK. It also pulls in the common ecosystem packages so a single `composer require` sets up most of the stack.

## What ships

| Area | Classes |
|---|---|
| Result pattern | `HiTaqnia\Haykal\Core\Result\Result`, `Error` |
| Tenancy | `HiTaqnia\Haykal\Core\Tenancy\Tenancy`, `TenantScope` |
| HTTP middleware | `HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware` (alias: `haykal.permissions.team`) |
| Media Library | `HiTaqnia\Haykal\Core\MediaLibrary\CustomPathGenerator` |
| Identity — User | `HiTaqnia\Haykal\Core\Identity\Models\User` (uses `Huwiya\InteractsWithHuwiya`) |
| Identity — Role/Permission | `HiTaqnia\Haykal\Core\Identity\Models\Role`, `Permission` (ULID-keyed) |
| Identity — Phone | `ValueObjects\PhoneNumber`, `Casts\PhoneNumberCast`, `Rules\PhoneNumberRule` |
| Identity — Query | `QueryBuilders\UserQueryBuilder` |

Migrations ship under `database/migrations/`:

- `create_users_table` (ULID, `huwiya_id` column, no password/MFA)
- `create_permission_tables` (Spatie schema, ULID-keyed)
- `create_media_table` (Spatie schema, ULID morphs)
- `create_notifications_table`

Migrations are auto-loaded via `loadMigrationsFrom()` — apps don't need to publish unless they want to customize.

## Hard dependencies

Installing haykal-core brings in:

- [`hitaqnia/huwiya-laravel`](https://github.com/hitaqnia/huwiya-laravel) — sole authentication mechanism.
- [`spatie/laravel-data`](https://spatie.be/docs/laravel-data) — DTOs.
- [`spatie/laravel-permission`](https://spatie.be/docs/laravel-permission) — roles/permissions (teams enabled per-tenant).
- [`spatie/laravel-medialibrary`](https://spatie.be/docs/laravel-medialibrary) — attachments.
- [`spatie/laravel-translatable`](https://spatie.be/docs/laravel-translatable) — model translations.
- [`laravel/horizon`](https://laravel.com/docs/horizon) — queue monitoring.
- [`laravel-notification-channels/fcm`](https://github.com/laravel-notification-channels/fcm) — push notifications.
- [`league/flysystem-aws-s3-v3`](https://flysystem.thephpleague.com/docs/adapter/aws-s3-v3/) + [`predis/predis`](https://github.com/predis/predis).

Configuration for each follows its own upstream documentation — haykal-core does not wrap their config files.

## Install

Add a Composer path repository pointing at your local haykal monorepo:

```json
{
    "repositories": [
        { "type": "path", "url": "/path/to/haykal/packages/haykal-core" },
        { "type": "path", "url": "/path/to/huwiya/packages/huwiya-laravel" }
    ]
}
```

Then require the package:

```bash
composer require hitaqnia/haykal-core:@dev
```

### Publish upstream configs and migrations

Each upstream package exposes its own publish tags. Run the ones you need:

```bash
# Spatie permission: config + custom migration
php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"

# Spatie Media Library: config
php artisan vendor:publish --provider="Spatie\\MediaLibrary\\MediaLibraryServiceProvider" --tag=medialibrary-config

# Huwiya SDK: config
php artisan vendor:publish --tag=huwiya-config
```

Then point the Spatie permission config at haykal's Role/Permission models (edit `config/permission.php`):

```php
'models' => [
    'permission' => HiTaqnia\Haykal\Core\Identity\Models\Permission::class,
    'role'       => HiTaqnia\Haykal\Core\Identity\Models\Role::class,
],
'teams' => true,
```

### Remove Laravel's default users migration

Fresh Laravel apps ship a default users migration that conflicts with haykal's. Delete it:

```bash
rm database/migrations/0001_01_01_000000_create_users_table.php
```

### Set the default auth user model

In `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => HiTaqnia\Haykal\Core\Identity\Models\User::class,
    ],
],
```

### Configure Huwiya

Follow the [`huwiya-laravel` docs](https://github.com/hitaqnia/huwiya-laravel) to set `HUWIYA_URL`, `HUWIYA_CLIENT_ID`, `HUWIYA_CLIENT_SECRET`, `HUWIYA_PROJECT_ID`, `HUWIYA_REDIRECT_URI` in your `.env`.

### Run migrations

```bash
php artisan migrate
```

## Usage notes

### Result pattern

```php
use HiTaqnia\Haykal\Core\Result\Result;
use HiTaqnia\Haykal\Core\Result\Error;

return $user !== null
    ? Result::success($user)
    : Result::failure(Error::make(code: 404, message: 'User not found.'));
```

### Tenancy

Set the active tenant early in the request lifecycle (typically via a middleware that reads the tenant from a Filament panel, subdomain, or header):

```php
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;

Tenancy::setTenantId($tenant->getKey());
```

Tenant-scoped models should declare the scope:

```php
use HiTaqnia\Haykal\Core\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy(TenantScope::class)]
class Complex extends Model {}
```

Add the `PermissionsTeamMiddleware` alias after your tenancy-setting middleware so Spatie picks up the tenant as the active permissions team:

```php
// in bootstrap/app.php or the kernel
->appendToGroup('web', 'haykal.permissions.team')
```

### User model

Use `HiTaqnia\Haykal\Core\Identity\Models\User` directly, or extend it:

```php
use HiTaqnia\Haykal\Core\Identity\Models\User as HaykalUser;

class User extends HaykalUser
{
    // add relations, Filament traits, etc.
}
```

Override the Huwiya hook methods (`getHuwiyaCreateAttributes`, `getHuwiyaUpdateAttributes`, `resolveHuwiyaConflict`, etc.) on your subclass to customize how claims sync into the DB — see the [Huwiya SDK docs](https://github.com/hitaqnia/huwiya-laravel).

### Media paths

`CustomPathGenerator` files user-owned media under `identity/<uuid>/` and everything else under `<tenantId>/<uuid>/`. Point Spatie at it via `config/media-library.php`:

```php
'path_generator' => HiTaqnia\Haykal\Core\MediaLibrary\CustomPathGenerator::class,
```

## Testing

```bash
composer test
```

Tests use Orchestra Testbench with sqlite in-memory. The `FakeHuwiyaIdP` fixture (in the monorepo's `tests/Fixtures/`) issues RS256-signed JWTs accepted by the real Huwiya SDK for scenarios that need a full auth flow.
