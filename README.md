# hitaqnia/haykal

Metapackage for full-stack HiTaqnia Laravel applications.

`hitaqnia/haykal` is a convenience entry point that installs the two runtime packages in the Haykal suite at once: `hitaqnia/haykal-api` (API layer, Scramble integration, `ApiProvider` composition) and `hitaqnia/haykal-filament` (Filament base classes, Huwiya login, shared theme). Both packages transitively install `hitaqnia/haykal-core`, so a single `composer require hitaqnia/haykal` brings up the entire stack.

This package contains no PHP code of its own.

---

## When to require this package

Require `hitaqnia/haykal` when the application exposes **both** an HTTP API and one or more Filament panels — the typical HiTaqnia project shape.

Applications that only expose an API (headless services, microservices without admin panels) should require `hitaqnia/haykal-api` directly. Applications that only expose Filament panels (internal admin tools without a public API surface) should require `hitaqnia/haykal-filament` directly. Both flavors pull `haykal-core` transitively.

---

## Installation

```bash
composer require hitaqnia/haykal:@dev
```

After installation, follow each sub-package's own configuration guide — the metapackage does not add configuration of its own:

1. **[`haykal-core`](../haykal-core/README.md)** — remove Laravel's default users migration, wire the auth provider to Haykal's `User`, publish Spatie permission / Media Library configs, configure Huwiya, slot middlewares, run migrations.
2. **[`haykal-api`](../haykal-api/README.md)** — publish the routes stub, register the Identity API provider, register the `huwiya-api` guard.
3. **[`haykal-filament`](../haykal-filament/README.md)** — register the Huwiya web guard, scaffold a panel theme with `php artisan haykal:publish-theme <panel>`, define one panel provider per tenant type.

---

## Requirements

- PHP 8.3 or later
- Laravel 13 or later
- Filament 5.5 or later
- A running instance of the Huwiya Identity Provider

---

## Versioning

The metapackage tracks the Haykal suite's major version and pins its dependencies to the matching minor range so `composer require hitaqnia/haykal:^1.0` resolves consistent versions of the underlying packages. Pin to a concrete version when a stable major lands; while the suite is in `@dev`, every release bumps together.
