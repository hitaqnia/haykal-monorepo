# hitaqnia/haykal-api

API scaffolding for HiTaqnia Laravel applications.

`haykal-api` provides four things:

1. **A response envelope** — the `ApiResponse` factory and `ApiExceptionHandler` so every endpoint in the application returns a consistent JSON shape.
2. **Scramble integrations** — exception-to-response extensions that document the envelope in the generated OpenAPI spec.
3. **A provider-based API composition pattern** — an abstract `ApiProvider` that subclasses per API module to register it with Scramble, declare its security schemes, and expose its docs UI. Applications compose as many APIs as they need by defining one provider per module.
4. **The Identity API** — `GET /api/identity/me` for retrieving the authenticated Huwiya user, along with a ready-to-register `IdentityApiProvider`.

Controllers, Form Requests, and Resources are written per-project following the conventions documented below; Laravel already supplies the right primitives and `haykal-api` deliberately does not ship base classes for them.

---

## Table of contents

- [Requirements](#requirements)
- [What this package provides](#what-this-package-provides)
- [Installation](#installation)
- [Configuration](#configuration)
- [Defining APIs](#defining-apis)
- [Usage](#usage)
- [Conventions](#conventions)
- [Customization](#customization)
- [Testing](#testing)

---

## Requirements

- PHP 8.3 or later
- Laravel 13 or later
- `hitaqnia/haykal-core` (shared kernel)
- A running instance of the Huwiya Identity Provider

---

## What this package provides

### Response layer

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Api\Response\ApiResponse` | Static factory for the Haykal JSON envelope: `ok()`, `created()`, `accepted()`, `paginated()`, `noContent()`, plus the full 4xx and 5xx set. `businessError(Error)` surfaces a domain-level `ResultPattern\Error` to the client. |
| `HiTaqnia\Haykal\Api\Response\PaginatedResource` | Wraps a `LengthAwarePaginator` into `{ items, pagination }`. Consumed by `ApiResponse::paginated()`. |
| `HiTaqnia\Haykal\Api\Response\ApiExceptionHandler` | Translates common framework exceptions (validation, 404, authentication, authorization, throttle) into the envelope for any request matching `api/*`. |
| `HiTaqnia\Haykal\Api\Response\Concerns\InteractsWithResponseMaker` | Trait used by `ApiResponse` to centralize envelope construction. |

### Scramble integrations

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Api\Scramble\ValidationExceptionExtension` | Documents 422 responses in the Haykal envelope shape. |
| `HiTaqnia\Haykal\Api\Scramble\NotFoundExceptionExtension` | Documents 404 responses for both Eloquent and route-level not-found exceptions. |
| `HiTaqnia\Haykal\Api\Scramble\HuwiyaSecurityExtension` | Document transformer that adds the Huwiya JWT bearer scheme to the OpenAPI spec. |
| `HiTaqnia\Haykal\Api\Scramble\EnvelopeResponseSchema` | Helper builder used by both exception extensions so the envelope shape stays consistent. |

### API composition

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Api\ApiProvider` | Abstract base service provider for API modules. Registers the module with Scramble, installs the Huwiya bearer security scheme, and exposes the docs UI. |
| `HiTaqnia\Haykal\Api\Identity\IdentityApiProvider` | Concrete provider for the Identity API shipped by this package. Applications register it to enable the `GET /api/identity/me` endpoint and its Scramble docs. |

### Identity

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Api\Identity\Controllers\MeController` | Single-action controller that returns the authenticated Huwiya user. |
| `HiTaqnia\Haykal\Api\Identity\Resources\UserResource` | JSON representation of the user (id, name, phone, email, locale, zoneinfo, theme). |

### Routes

| File | Mount point |
|---|---|
| `routes/identity-api.stub.php` | Published to `routes/api/identity-api.php`. Registers `GET /api/identity/me` behind the `huwiya-api` guard. |

---

## Installation

Install alongside `haykal-core`. Once path repositories are declared for both packages (see `haykal-core`'s README), run:

```bash
composer require hitaqnia/haykal-api:@dev
```

---

## Configuration

### 1. Publish the routes stub

```bash
php artisan vendor:publish --tag=haykal-api-routes
```

This copies `routes/api/identity-api.php` into the application. Include it from `routes/api.php`:

```php
require __DIR__.'/api/identity-api.php';
```

### 2. Register the Identity API provider

Register `IdentityApiProvider` in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,

    // Haykal API modules
    HiTaqnia\Haykal\Api\Identity\IdentityApiProvider::class,

    // ... other application providers and API providers ...
];
```

This registers the Identity API with Scramble, installs the Huwiya bearer security scheme, and exposes the docs UI at `/docs/identity-api` with the JSON spec at `/docs/identity-api.json`.

See [Defining APIs](#defining-apis) below for the full pattern used to add additional API modules.

### 3. Register the `huwiya-api` guard

In `config/auth.php`:

```php
'guards' => [
    // ... existing guards ...

    'huwiya-api' => [
        'driver' => 'huwiya-api',
        'provider' => 'users',
    ],
],
```

### 4. Wire the exception handler

In `bootstrap/app.php`:

```php
use HiTaqnia\Haykal\Api\Response\ApiExceptionHandler;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(
        fn (Throwable $e, Request $request) => ApiExceptionHandler::handle($e, $request)
    );
})
```

`ApiExceptionHandler` only intervenes for requests matching `api/*`; other requests fall through to Laravel's defaults.

### 5. Confirm Scramble is configured

Publish Scramble's configuration if you have not already:

```bash
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider" --tag=scramble-config
```

No further action is required — `HaykalApiServiceProvider` registers the validation and 404 exception extensions automatically. Security schemes and per-API metadata are declared by each `ApiProvider` subclass (see [Defining APIs](#defining-apis)).

---

## Defining APIs

Every API module in a Haykal application is declared by a subclass of `HiTaqnia\Haykal\Api\ApiProvider`. The provider owns the module's Scramble registration, security schemes, and docs UI — applications compose as many providers as they need, one per API module.

### Anatomy of an API provider

Subclass `ApiProvider` and declare the four required identity hooks. Everything else is optional.

```php
namespace App\Providers\Apis;

use Dedoc\Scramble\Support\Generator\SecurityScheme;
use HiTaqnia\Haykal\Api\ApiProvider;

final class PropertiesApiProvider extends ApiProvider
{
    protected function name(): string
    {
        return 'properties-api';
    }

    protected function path(): string
    {
        return 'api/properties';
    }

    protected function title(): string
    {
        return 'Properties API';
    }

    protected function description(): string
    {
        return 'Property management endpoints for the admin dashboard.';
    }

    // Optional hooks below.

    protected function version(): string
    {
        return '1.2.0';
    }

    protected function logo(): ?string
    {
        return asset('logo.png');
    }

    protected function primaryColor(): ?string
    {
        return '#4432d2';
    }

    /**
     * Security schemes to register in addition to the Huwiya bearer scheme
     * (which is always installed). Typical additions are header-based
     * tenant or profile selectors.
     *
     * @return array<string, SecurityScheme>
     */
    protected function additionalSecuritySchemes(): array
    {
        return [
            'complex' => SecurityScheme::apiKey('header', 'X-Complex-Id'),
        ];
    }
}
```

Register the provider in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,

    HiTaqnia\Haykal\Api\Identity\IdentityApiProvider::class,
    App\Providers\Apis\PropertiesApiProvider::class,
];
```

### What the provider wires up

Registering the provider gives the application, automatically:

| Behavior | Derived from |
|---|---|
| Scramble discovers every route matching `api_path` and groups them under the module's OpenAPI spec. | `path()` |
| The spec's `info.version` and `info.description` are populated. | `version()`, `description()` |
| The Scramble docs UI is served at `docs/<name>` (default) with the JSON spec at `docs/<name>.json`. | `docsPath()` — override to move it. |
| The docs UI is titled and optionally branded with a logo and primary color. | `title()`, `logo()`, `primaryColor()` |
| The `bearer` security scheme for Huwiya JWTs is added to the spec as the default requirement for every operation. | Always applied. |
| Any additional schemes declared by the provider are merged into the spec. | `additionalSecuritySchemes()` |

### Route files

Route files remain conventional Laravel — the provider does not manage routing. Create `routes/api/properties-api.php` and include it from `routes/api.php`:

```php
// routes/api.php
require __DIR__.'/api/properties-api.php';
```

```php
// routes/api/properties-api.php
use App\Apis\Properties\Controllers\CreatePropertyController;
use App\Apis\Properties\Controllers\ListPropertiesController;
use Illuminate\Support\Facades\Route;

Route::prefix('properties')
    ->middleware(['auth:huwiya-api'])
    ->group(function () {
        Route::get('/', ListPropertiesController::class);
        Route::post('/', CreatePropertyController::class);
    });
```

Scramble matches the `api_path` declared on the provider (`api/properties`) against the routes defined here and groups them under the `properties-api` spec.

### The shipped Identity API

`IdentityApiProvider` is a concrete `ApiProvider` shipped by this package. It exposes the Identity module at:

| Path | Purpose |
|---|---|
| `api/identity/me` | Authenticated user profile. |
| `docs/identity-api` | Scramble UI for the Identity module. |
| `docs/identity-api.json` | Raw OpenAPI spec. |

Subclass `IdentityApiProvider` (or write a new one extending `ApiProvider` directly) if the module needs a different URL prefix, title, or security scheme set.

---

## Usage

### Success responses

```php
use HiTaqnia\Haykal\Api\Response\ApiResponse;

return ApiResponse::ok(message: 'Profile retrieved.', data: new UserResource($user));
return ApiResponse::created(message: 'Reservation booked.', data: $reservation);
return ApiResponse::noContent();
```

### Paginated responses

```php
return ApiResponse::paginated(
    message: 'Units retrieved.',
    data: Unit::query()->paginate($request->integer('per_page', 15)),
);
```

The response body payload becomes:

```json
{
    "success": 1,
    "code": 200,
    "message": "Units retrieved.",
    "data": {
        "items": [ ... ],
        "pagination": { "page": 1, "per_page": 15, "total": 42 }
    },
    "errors": null
}
```

### Error responses

```php
return ApiResponse::notFound();
return ApiResponse::forbidden('You may not access this complex.');
return ApiResponse::validationError(errors: $validator->errors());
```

### Business errors

`ApiResponse::businessError()` accepts any `HiTaqnia\Haykal\Core\ResultPattern\Error` and emits it through the envelope. Codes above 999 are surfaced as `code` in the envelope while the HTTP status is mapped to 409 Conflict so the transport layer stays HTTP-valid.

```php
use HiTaqnia\Haykal\Core\ResultPattern\Error;

return ApiResponse::businessError(
    Error::make(code: 4001, message: 'Booking overlaps an existing reservation.'),
);
```

---

## Conventions

Laravel already provides first-class abstractions for Controllers, Form Requests, and Resources. `haykal-api` does not ship base classes for them; the conventions below are the ones every HiTaqnia project follows.

### Controllers

Controllers are single-action or per-resource classes that delegate to Actions or services. They return through `ApiResponse` exclusively.

```php
namespace App\Apis\Properties\Controllers;

use App\Apis\Properties\Requests\CreatePropertyRequest;
use App\Apis\Properties\Resources\PropertyResource;
use App\Domain\Properties\Actions\CreatePropertyAction;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Http\JsonResponse;

final class CreatePropertyController
{
    public function __construct(
        private readonly CreatePropertyAction $createProperty,
    ) {}

    /**
     * Create Property
     *
     * Register a new property in the active tenant.
     */
    public function __invoke(CreatePropertyRequest $request): JsonResponse
    {
        $result = $this->createProperty->execute($request->validated());

        if ($result->isFailure()) {
            return ApiResponse::businessError($result->getError());
        }

        return ApiResponse::created(
            message: 'Property created.',
            data: new PropertyResource($result->getData()),
        );
    }
}
```

### Form Requests

Form Requests carry validation rules, authorization, and any input transformations. `authorize()` should return `true` only when the check is cheap and always required — finer-grained policy checks belong in the controller or action.

```php
namespace App\Apis\Properties\Requests;

use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

final class CreatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('properties.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_phone' => ['required', new PhoneNumberRule],
        ];
    }
}
```

### Resources

Resources transform a model into a JSON representation. Annotate every key with a PHPDoc line so Scramble generates a complete schema.

```php
namespace App\Apis\Properties\Resources;

use App\Domain\Properties\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Property $resource
 */
final class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // The property's unique identifier.
            // @var string
            // @format ULID
            'id' => $this->id,

            // The property's display name.
            'name' => $this->name,

            // ISO-8601 timestamp of when the property was registered.
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

### Module layout

Each API module lives under `app/Apis/<ModuleName>/` and contains its own `Controllers/`, `Requests/`, and `Resources/`. Routes are registered under `routes/api/<module-name>-api.php` and included from `routes/api.php`.

```
app/Apis/Properties/
    Controllers/
        CreatePropertyController.php
        ListPropertiesController.php
    Requests/
        CreatePropertyRequest.php
    Resources/
        PropertyResource.php

routes/api/properties-api.php
```

---

## Customization

### Publishable resources

| Tag | Contents |
|---|---|
| `haykal-api-routes` | The `identity-api.stub.php` routes file. |

### Middleware considerations

The published routes file is guarded by `auth:huwiya-api`. Replace the middleware stack at the call site if the application wires authentication differently — the route file is just a stub once published.

### Scramble

`HaykalApiServiceProvider` registers the shipped Scramble extensions. Applications add their own exception-to-response extensions by calling `Scramble::registerExtension(...)` in their own service provider's `boot()`.

### Custom response envelopes

Prefer composing new factories on `ApiResponse` rather than subclassing. Any factory that funnels through `InteractsWithResponseMaker::make()` inherits the envelope shape automatically.

---

## Testing

The `FakeHuwiyaIdP` fixture (shared across the monorepo under `tests/Fixtures/`) issues RS256-signed JWTs accepted by the real SDK. For tests that do not require a token round-trip, authenticate directly:

```php
use Huwiya\Facades\Huwiya;

Huwiya::actingAs($user, 'huwiya-api');

$this->getJson('/api/identity/me')->assertOk();
```

Run the monorepo suite from the repository root:

```bash
composer test
```
