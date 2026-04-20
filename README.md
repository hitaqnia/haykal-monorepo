# Haykal

Hitaqnia's internal Laravel boilerplate package suite.

Haykal consolidates the shared layer that every Hitaqnia Laravel project uses — API response scaffolding, Filament base classes, Identity integration with the Huwiya IdP, tenancy utilities, domain concerns — into a maintained, versioned set of Composer packages under the `hitaqnia/` vendor namespace.

## Layout

```
haykal/
├── composer.json          Monorepo root: path repositories + shared dev tooling.
├── Makefile               Local dev commands (test, format, install).
├── packages/
│   ├── haykal-core/       hitaqnia/haykal-core — shared kernel (Result, tenancy, Identity, middlewares).
│   ├── haykal-api/        hitaqnia/haykal-api — API response layer, Scramble extensions, /me endpoint.
│   ├── haykal-filament/   hitaqnia/haykal-filament — Filament foundation (BasePanel, BaseResource, base theme).
│   └── haykal/            hitaqnia/haykal — metapackage (api + filament).
├── boilerplate/           New project template (Stage 5).
├── smoke/                 Throwaway Laravel app used to validate installs end-to-end (Stage 4).
└── tests/
    ├── Core/              haykal-core tests.
    ├── Api/               haykal-api tests.
    ├── Filament/          haykal-filament tests.
    └── Fixtures/          Shared fixtures (FakeHuwiyaIdP, etc.).
```

Standalone Filament plugins (Mapbox, ViewerJS) live outside this monorepo in `/Users/mahdi/Work/filament-plugins/` — each with its own git repo and release cadence. They are not part of Haykal.

## Dependencies between packages

- `haykal-core` is the kernel. Every other haykal package depends on it.
- `haykal-api` depends on `haykal-core`.
- `haykal-filament` depends on `haykal-core`.
- `haykal` (metapackage) depends on `haykal-api` + `haykal-filament`.
- The `filament-mapbox` and `filament-viewerjs` plugins live in their own repos outside this monorepo.

`haykal-core` hard-requires the common ecosystem packages (Spatie permission / medialibrary / translatable / data, Huwiya SDK, Horizon, FCM, flysystem-s3, predis, geophp) so consuming apps get the full hitaqnia stack with a single `composer require`.

## Authentication

Huwiya is the sole authentication mechanism. Local phone+OTP auth, Sanctum tokens, and device tracking are not shipped. Follow the [`hitaqnia/huwiya-laravel`](https://github.com/hitaqnia/huwiya-laravel) SDK's documentation for auth setup.

## Local development

All packages are resolved via Composer path repositories declared in the root `composer.json`. No publishing is configured; everything runs locally.

```bash
composer install      # Installs all packages + dev tooling.
make test             # Runs the full test suite.
make format           # Formats code with Pint.
```

## Stages

Implementation proceeds in six stages:

1. **Stage 0** — Monorepo scaffolding. (current)
2. **Stage 1** — `haykal-core` (kernel).
3. **Stage 2** — `haykal-api`.
4. **Stage 3** — `haykal-filament`.
5. **Stage 4** — `haykal` metapackage + local validation.
6. **Stage 5** — `boilerplate/` new project template.

The standalone Filament plugins (`filament-mapbox`, `filament-viewerjs`) are tracked separately in `/Users/mahdi/Work/filament-plugins/` and have their own roadmap.

See `/Users/mahdi/.claude/plans/in-the-current-directory-wild-rain.md` for the full plan.

## Scope

- Haykal is for **new projects only**. Existing `laravel-boilerplate` and `hibayt-backend` remain untouched.
- Deployment scaffolding (Dockerfiles, supervisord, Jenkinsfiles) is deferred.
- Any form of publishing (Packagist, public GitHub) is deferred.
