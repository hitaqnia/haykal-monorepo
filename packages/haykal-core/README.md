# hitaqnia/haykal-core

Shared utilities for HiTaqnia Laravel applications.

`haykal-core` is a **pure utility package**. It carries the cross-project primitives every hitaqnia app leans on and nothing else — no models, no migrations, no auth scaffolding. Schema and identity code (User, Role, Permission, migrations, factories) belong in the consuming application.

## What this package provides

| Namespace | Description |
|---|---|
| `HiTaqnia\Haykal\Core\ResultPattern\Result`, `Error` | Typed `Result<T>` outcome and companion `Error` value object. Use in place of exceptions for expected, recoverable failures. |
| `HiTaqnia\Haykal\Core\Tenancy\Tenancy` | Process-local tenant context resolver. Exposes `setTenantId()`, `getTenantId()`, `clear()`. |
| `HiTaqnia\Haykal\Core\Tenancy\TenantScope` | Global Eloquent scope that constrains queries to the active tenant. Rows with a `NULL` tenant remain visible as shared records. |
| `HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant` | Trait that marks an Eloquent model as tenant-owned. Applies `TenantScope`, auto-populates the tenant FK on `creating`, and declares the `tenant()` relation. Override `$tenantModel` / `$tenantForeignKey` per model. |
| `HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber` | Iraqi phone number in E.164 canonical form with readable and compact formatters. |
| `HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast` | Eloquent cast between `PhoneNumber` and its E.164 string form. |
| `HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule` | Validation rule for the input shapes the value object accepts. |
| `HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware` | Forwards the active tenant into Spatie's `setPermissionsTeamId()`. Registered under the alias `haykal.permissions.team`. |

## What this package deliberately does **not** ship

- **No User / Role / Permission models.** Each consuming app owns its auth model and its role/permission subclasses (typically extending Spatie's with `HasUlids`).
- **No migrations.** The `users`, Spatie permission tables, media, and notifications tables are owned by the application. See `hitaqnia/haykal-starter` for the canonical hitaqnia schema.
- **No factories, seeders, routes, or publishable config.** Pure library code.

## Requirements

- PHP 8.3 or later
- Laravel 13 or later
- `spatie/laravel-permission` ^6.21 (required — `PermissionsTeamMiddleware` calls `setPermissionsTeamId()`)

## Installation

```bash
composer require hitaqnia/haykal-core
```

Auto-discovered via `HaykalCoreServiceProvider`, which registers the middleware alias on boot. Nothing else is wired up — the package adds no migrations, no routes, no config files.

## Usage

### Result pattern

```php
use HiTaqnia\Haykal\Core\ResultPattern\Error;
use HiTaqnia\Haykal\Core\ResultPattern\Result;

$result = Result::success($order);
// or
$result = Result::failure(Error::make(code: 4001, message: 'Booking overlap.'));

if ($result->isSuccess()) {
    $order = $result->getData();
} else {
    $error = $result->getError();
}
```

### Tenancy

```php
use HiTaqnia\Haykal\Core\Tenancy\Concerns\HasTenant;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;

// Set the active tenant for the current request / process.
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

`$tenantForeignKey` defaults to `tenant_id` — override per model when different models belong to different tenant types (`agency_id` on `properties`, `developer_id` on `projects`, …).

### Phone numbers

```php
use HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast;
use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;

// In your User model (or any model with a phone column):
protected function casts(): array
{
    return ['phone' => PhoneNumberCast::class];
}

// In a Form Request:
'phone' => ['required', new PhoneNumberRule],

// Using the VO directly:
$phone = new PhoneNumber('07701234567');
$phone->getInternational();                // "+9647701234567"
$phone->getInternational(readable: true);  // "+964 770 123 4567"
$phone->getNational();                     // "07701234567"
```

Currently only Iraqi numbers (`+964`, mobile) are accepted.

### Middleware

Assign globally or per route group in `bootstrap/app.php`:

```php
use HiTaqnia\Haykal\Core\Http\Middlewares\PermissionsTeamMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        // Must run after the middleware that resolves the active tenant:
        $middleware->appendToGroup('web', [PermissionsTeamMiddleware::class]);
    })
    ->create();
```

Or via the alias: `haykal.permissions.team`.

## Testing

The package has no runtime scaffolding to test in isolation. The monorepo's tests exercise these utilities in combination with test fixtures that mirror a real consuming application's shape — see `tests/Fixtures/` and `tests/Core/` in the monorepo repository.
