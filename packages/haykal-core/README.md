# haykal-core

The shared kernel for every HiTaqnia Laravel app.

`hitaqnia/haykal-core` ships the classes, migrations, and ecosystem wiring that every project uses: the Result pattern, tenancy utilities, the middleware that ties Spatie roles/permissions to the active tenant, Spatie Media Library path generation, and the canonical User / Role / Permission models integrated with the Huwiya Identity Provider.

Installing haykal-core into a fresh Laravel app replaces Laravel's default authentication scaffolding end-to-end. The sections below spell out exactly what the package provides, what it requires you to configure, and which default migrations to delete so nothing collides.

---

## What this package provides

### Classes

| Namespace | What it is |
|---|---|
| `HiTaqnia\Haykal\Core\ResultPattern\Result`, `Error` | Typed `Result<T>` outcome + `Error` value object. Use instead of throwing for expected/recoverable failures. |
| `HiTaqnia\Haykal\Core\Tenancy\Tenancy` | Process-local tenant context resolver (`setTenantId`, `getTenantId`, `clear`). |
| `HiTaqnia\Haykal\Core\Tenancy\TenantScope` | Eloquent scope that filters queries to the active tenant; `NULL` tenant rows are treated as shared. |
| `HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware` | Forwards the active Haykal tenant into Spatie's `setPermissionsTeamId()`. Registered under the alias `haykal.permissions.team`. |
| `HiTaqnia\Haykal\Core\MediaLibrary\CustomPathGenerator` | Spatie Media Library path generator: user-owned media → `identity/<uuid>/`, everything else → `<tenantId>/<uuid>/`. |
| `HiTaqnia\Haykal\Core\Identity\Models\User` | Authenticatable model with `Huwiya\InteractsWithHuwiya` + Spatie `HasRoles` + Media Library + soft deletes. Profile fields sync from Huwiya claims on every login. |
| `HiTaqnia\Haykal\Core\Identity\Models\Role`, `Permission` | Spatie Role/Permission extended with `HasUlids`. `Role` is tenant-scoped via `TenantScope`. |
| `HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber` | Iraqi phone number in E.164 canonical form with readable/compact formatters. |
| `HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast` | Eloquent cast that stores phones as E.164 and returns `PhoneNumber` objects. |
| `HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule` | Validation rule that accepts the same shapes the value object normalizes. |
| `HiTaqnia\Haykal\Core\Identity\QueryBuilders\UserQueryBuilder` | Adds `wherePhoneNumber()` / `getByPhoneNumber()` to `User::query()`. |

### Migrations

haykal-core **publishes its own migrations** via `HaykalCoreServiceProvider`. They are also auto-loaded from the package directory so `php artisan migrate` runs them without any publishing step. The shipped migrations are:

- `create_users_table` — ULID `id`, unique `huwiya_id` (Huwiya subject), `name`, unique nullable `phone` + `email`, `locale` / `zoneinfo` / `theme` (preference columns synced from Huwiya claims), `remember_token`, timestamps, soft deletes, plus `sessions` table keyed by `user_id`.
- `create_permission_tables` — Spatie schema with ULID primary keys, teams enabled (tenant-scoped roles).
- `create_media_table` — Spatie Media Library schema with ULID morphs.
- `create_notifications_table` — standard Laravel notifications with ULID morph target.

The `users` migration **does not** include `password`, `is_admin`, MFA columns, Sanctum tokens, or device tracking. Authentication is handled entirely by Huwiya; none of those columns apply.

### Ecosystem packages installed

Installing `hitaqnia/haykal-core` pulls in:

- [`hitaqnia/huwiya-laravel`](https://github.com/hitaqnia/huwiya-laravel) — sole authentication mechanism (OAuth2 web guard + JWT API guard).
- [`spatie/laravel-data`](https://spatie.be/docs/laravel-data) — DTOs.
- [`spatie/laravel-permission`](https://spatie.be/docs/laravel-permission) — roles/permissions (teams enabled).
- [`spatie/laravel-medialibrary`](https://spatie.be/docs/laravel-medialibrary) — attachments.
- [`spatie/laravel-translatable`](https://spatie.be/docs/laravel-translatable) — model translations.
- [`laravel/horizon`](https://laravel.com/docs/horizon) — queue monitoring.
- [`laravel-notification-channels/fcm`](https://github.com/laravel-notification-channels/fcm) — push notifications.
- [`league/flysystem-aws-s3-v3`](https://flysystem.thephpleague.com/docs/adapter/aws-s3-v3/) + [`predis/predis`](https://github.com/predis/predis) — storage + Redis.

Configuration for each of these follows its own upstream documentation. **haykal-core does not wrap their config files** — read the package docs, publish their configs, edit them in your app.

---

## Install

Until the suite is published, consume the package via a Composer path repository. In your app's `composer.json`:

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

Then:

```bash
composer require hitaqnia/haykal-core:@dev
```

---

## Configuration

Everything in this section is required. Each step points at the upstream documentation for the corresponding package.

### 1. Remove Laravel's default migrations that collide with haykal's

Fresh Laravel apps ship a users migration and a few other schema migrations that overlap or conflict with what haykal-core provides. **Delete them before running `php artisan migrate`:**

```bash
rm database/migrations/0001_01_01_000000_create_users_table.php
```

haykal-core's own migration recreates `users`, `sessions`, and includes the `huwiya_id`, `locale`, `zoneinfo`, and `theme` columns.

Leave these Laravel defaults in place — they don't collide with anything haykal ships and are still useful:

- `0001_01_01_000001_create_cache_table.php`
- `0001_01_01_000002_create_jobs_table.php`

### 2. Point Laravel's auth provider at the haykal User

Edit `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => HiTaqnia\Haykal\Core\Identity\Models\User::class,
    ],
],
```

If your app needs extra relations or Filament-specific contracts on the User, extend the haykal class:

```php
use HiTaqnia\Haykal\Core\Identity\Models\User as HaykalUser;

class User extends HaykalUser
{
    // app-specific relations, Filament traits, canAccessPanel(), etc.
}
```

Then point `config/auth.php` at your subclass instead.

### 3. Publish the Spatie Permission config and wire haykal's Role / Permission models

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag=permission-config
```

Edit `config/permission.php`:

```php
'models' => [
    'permission' => HiTaqnia\Haykal\Core\Identity\Models\Permission::class,
    'role'       => HiTaqnia\Haykal\Core\Identity\Models\Role::class,
],

'teams' => true,
```

Teams must be enabled — haykal scopes roles per-tenant via Spatie's team support.

**Do not publish Spatie's migration.** haykal-core ships a ULID-keyed variant of the permission schema that will run automatically.

### 4. Publish the Media Library config and wire haykal's path generator

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag=medialibrary-config
```

Edit `config/media-library.php`:

```php
'path_generator' => HiTaqnia\Haykal\Core\MediaLibrary\CustomPathGenerator::class,
```

**Do not publish Spatie Media Library's migration.** haykal-core ships a ULID-morph variant that runs automatically.

### 5. Configure Huwiya

Follow the [`huwiya-laravel` documentation](https://github.com/hitaqnia/huwiya-laravel) to publish the config and set the required environment variables. At minimum you need:

```
HUWIYA_URL=https://idp.example.com
HUWIYA_PROJECT_ID=your-project-id
HUWIYA_CLIENT_ID=...
HUWIYA_CLIENT_SECRET=...
HUWIYA_REDIRECT_URI=https://your-app.example.com/huwiya/callback
```

Add the Huwiya guard drivers to `config/auth.php` per the SDK docs (`huwiya-web` for session-based Filament panels, `huwiya-api` for stateless APIs).

### 6. Wire the permissions-team middleware

Add the `haykal.permissions.team` middleware **after** whichever middleware resolves the active tenant. In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', 'haykal.permissions.team');
    // Likewise for 'api' if API routes are tenant-scoped.
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

Set the active tenant early in the request lifecycle — typically via a middleware that reads the tenant from a Filament panel, subdomain, or header:

```php
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;

Tenancy::setTenantId($tenant->getKey());
```

Scope tenant-owned models:

```php
use HiTaqnia\Haykal\Core\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy(TenantScope::class)]
class Complex extends Model {}
```

Models need a `tenant_id` column (the scope's `FOREIGN_KEY` constant); rows with a `NULL` tenant are treated as shared across all tenants.

### Huwiya claim sync

The default User model syncs the following fields from `TokenClaims` on **both** account creation and every re-login:

- `name`
- `phone`
- `email`
- `locale`
- `zoneinfo`
- `theme`

Override `attributesFromClaims(TokenClaims $claims): array` on your User subclass to add or remove fields.

For more intrusive changes (conditional auto-registration, phone/email recycling policy, multi-tenant query scoping), override the hook methods on `Huwiya\InteractsWithHuwiya` — see the [Huwiya SDK docs](https://github.com/hitaqnia/huwiya-laravel).

### Phone numbers

```php
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;

$phone = new PhoneNumber('07701234567');
$phone->getInternational();                  // +9647701234567
$phone->getInternational(readable: true);    // +964 770 123 4567
$phone->getNational();                       // 07701234567
```

Accepts `+964...`, `00964...`, `0...`, or bare `7XXXXXXXXX`. Rejects non-Iraqi inputs.

### User query helpers

```php
User::query()->wherePhoneNumber('07701234567')->first();
User::query()->getByPhoneNumber('+9647701234567');
```

---

## Testing

```bash
composer test
```

Tests use Orchestra Testbench with sqlite in-memory. The monorepo's `FakeHuwiyaIdP` fixture (`tests/Fixtures/`) issues RS256-signed JWTs accepted by the real Huwiya SDK for scenarios that exercise the full auth flow end-to-end.
