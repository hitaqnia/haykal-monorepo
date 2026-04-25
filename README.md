# Haykal

HiTaqnia's internal Laravel boilerplate package suite.

Haykal is a set of **utility packages** shared across every HiTaqnia Laravel project — API response scaffolding, Filament base classes, tenancy primitives, phone-number VO, Result pattern, permissions-team middleware. Domain code, identity models, and migrations live in the consuming application, not in these packages.

New projects bootstrap from [`hitaqnia/haykal-starter`](https://github.com/hitaqnia/haykal-starter), which installs the `hitaqnia/haykal` metapackage on top of a Laravel 13 skeleton and ships the canonical hitaqnia User / Role / Permission models, the four migrations (users, Spatie permission tables, media, notifications), and the Huwiya auth wiring — all of which the application owns and edits directly.

## Layout

```
haykal-monorepo/
├── composer.json                Monorepo root: path repositories + shared dev tooling.
├── Makefile                     Local dev commands (test, format, install).
├── packages/
│   ├── haykal-core/             hitaqnia/haykal-core — utilities (Result pattern, tenancy, PhoneNumber VO + cast + rule, permissions-team middleware).
│   ├── haykal-api/              hitaqnia/haykal-api — API response envelope, Scramble extensions, ApiProvider base.
│   ├── haykal-filament/         hitaqnia/haykal-filament — Filament foundation (BasePanel, BaseResource, base theme, Mapbox + ViewerJS components).
│   └── haykal/                  hitaqnia/haykal — metapackage (api + filament).
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

- `haykal-core` is the utility root. Every other haykal package depends on it.
- `haykal-api` depends on `haykal-core`.
- `haykal-filament` depends on `haykal-core`.
- `haykal` (metapackage) depends on `haykal-api` + `haykal-filament`.

`haykal-core` has exactly one hard dep beyond Illuminate: `spatie/laravel-permission` (required by `PermissionsTeamMiddleware`). Heavy ecosystem packages — Huwiya SDK, Spatie media-library / translatable / data, Horizon, Flysystem-S3, Predis — are pulled in by the **consuming application**, typically via `hitaqnia/haykal-starter`.

## Scope of each package

- **`haykal-core`** is a pure utility package: the Result pattern, tenancy primitives, the Iraqi phone-number VO + Eloquent cast + validation rule, and the `haykal.permissions.team` HTTP middleware. Ships no models, no migrations, no auth scaffolding.
- **`haykal-api`** is a utility layer. Same response envelope, exception-to-envelope translation, and Scramble plumbing across every API project, plus an abstract `ApiProvider` to subclass per API module. Ships no endpoints, no controllers, no routes, no concrete providers.
- **`haykal-filament`** ships the Filament foundation: `BasePanel`, `BaseResource`, base list/create/edit pages, the Huwiya-aware login redirect, the translatable tabs component, the access-checking middleware, the base theme, and the Mapbox + ViewerJS Filament components.
- **`haykal`** is a metapackage that installs `haykal-api` + `haykal-filament` for full-stack projects.

Auth models (User + Role + Permission), migrations, and factories live in the consuming app — see `hitaqnia/haykal-starter` for the canonical hitaqnia wiring.

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
