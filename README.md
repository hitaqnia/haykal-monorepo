# Haykal

HiTaqnia's internal Laravel boilerplate package suite.

Haykal consolidates the shared layer that every HiTaqnia Laravel project uses — API response scaffolding, Filament base classes, Identity model integration with the Huwiya IdP, tenancy utilities, domain concerns — into a maintained, versioned set of Composer packages under the `hitaqnia/` vendor namespace.

New projects bootstrap from [`hitaqnia/haykal-starter`](https://github.com/hitaqnia/haykal-starter), which installs the `hitaqnia/haykal` metapackage and wires up a Laravel 13 app with Huwiya auth, a Filament-ready base, and the shared Haykal middleware stack.

## Layout

```
haykal-monorepo/
├── composer.json                Monorepo root: path repositories + shared dev tooling.
├── Makefile                     Local dev commands (test, format, install).
├── packages/
│   ├── haykal-core/             hitaqnia/haykal-core — shared kernel (Result, tenancy, Identity, middlewares, migrations).
│   ├── haykal-api/              hitaqnia/haykal-api — API response envelope, Scramble extensions, ApiProvider base.
│   ├── haykal-filament/         hitaqnia/haykal-filament — Filament foundation (BasePanel, BaseResource, base theme, Mapbox + ViewerJS components).
│   └── haykal/                  hitaqnia/haykal — metapackage (api + filament).
├── scripts/
│   └── bootstrap-smoke.sh       Spin up a throwaway Laravel app for end-to-end validation.
├── tests/
│   ├── Core/                    haykal-core tests.
│   ├── Api/                     haykal-api tests.
│   ├── Filament/                haykal-filament tests.
│   └── Fixtures/                Shared fixtures (FakeHuwiyaIdP, etc.).
└── .github/workflows/
    ├── split-packages.yml       Tag-push → split + publish each package to its child repo.
    └── tests.yml                PHPUnit + Pint on every push / PR.
```

## Dependencies between packages

- `haykal-core` is the kernel. Every other haykal package depends on it.
- `haykal-api` depends on `haykal-core`.
- `haykal-filament` depends on `haykal-core`.
- `haykal` (metapackage) depends on `haykal-api` + `haykal-filament`.

`haykal-core` hard-requires the common ecosystem packages (Spatie permission / medialibrary / translatable / data, Huwiya SDK, Horizon, FCM, flysystem-s3, predis) so consuming apps get the full hitaqnia stack with a single `composer require hitaqnia/haykal`.

## Scope of each package

- **`haykal-core`** ships code every hitaqnia app needs: the Result pattern, tenancy scopes, base User / Role / Permission models with `InteractsWithHuwiya`, the Huwiya claim → locale middleware, the Media Library path generator, and the published users / permissions / media / notifications migrations.
- **`haykal-api`** is a pure **utility layer**. It gives every API project the same response envelope, exception-to-envelope translation, and Scramble plumbing, plus an abstract `ApiProvider` to subclass per API module. It ships no endpoints, no controllers, no routes, no concrete providers.
- **`haykal-filament`** ships the Filament foundation: `BasePanel`, `BaseResource`, base list/create/edit pages, the Huwiya-aware login redirect, the translatable tabs component, the access-checking middleware, the base theme, and the Mapbox + ViewerJS Filament components.
- **`haykal`** is a metapackage that installs `haykal-api` + `haykal-filament` for full-stack projects.

## Authentication

Huwiya is the sole authentication mechanism. Local phone+OTP auth, Sanctum tokens, and device tracking are not shipped. Follow the [`hitaqnia/huwiya-laravel`](https://github.com/hitaqnia/huwiya-laravel) SDK's documentation for auth setup.

## Local development

All packages are resolved via Composer path repositories declared in the root `composer.json` — each aliased to `1.0.0` so the published `^1.0` cross-dependencies between packages resolve against the in-tree source during development.

```bash
composer install      # Installs all packages + dev tooling.
composer test         # phpunit — full test suite across core / api / filament.
composer format       # Pint.
composer format:check # Pint --test.
```

## Publishing

`split-packages.yml` runs on tag push. Each `packages/<name>` subdirectory is mirrored to its own read-only child repo (`hitaqnia/haykal-core`, `hitaqnia/haykal-api`, `hitaqnia/haykal-filament`, `hitaqnia/haykal`) using `symplify/monorepo-split-github-action`. Packagist watches the child repos via the GitHub App and picks the tag up as a new release automatically.

Versions are synchronized across the four packages: one tag on the monorepo produces the same version on every child. Release:

```bash
git tag v1.x.y
git push origin v1.x.y
```

## Scope

- Haykal is for **new projects only**. Existing `laravel-boilerplate` and `hibayt-backend` remain untouched.
- Deployment scaffolding (Dockerfiles, supervisord, Jenkinsfiles) is owned by consuming applications, not by Haykal.
