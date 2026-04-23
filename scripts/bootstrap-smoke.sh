#!/usr/bin/env bash
# bootstrap-smoke.sh
#
# Scaffolds a disposable Laravel 13 application in ./smoke/ and wires
# the Haykal suite against it via local Composer path repositories. Used
# for end-to-end validation of the whole stack outside the Testbench
# unit suite — covers things the unit tests cannot: full HTTP request
# pipeline, Livewire interactions, Scramble UI rendering, and the real
# Filament panel → Huwiya OAuth redirect chain.
#
# Idempotent: removes ./smoke/ first and starts fresh every run.
# Re-scaffolding should take well under a minute once Composer has the
# Laravel skeleton cached.
#
# Usage:
#   ./scripts/bootstrap-smoke.sh
#
# Notes:
#   - Signature verification is disabled in the smoke .env so hand-crafted
#     JWTs can authenticate without a live Huwiya IdP.
#   - Serves on port 8765 so it doesn't collide with normal `php artisan serve`.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SMOKE_DIR="${ROOT}/smoke"

cd "${ROOT}"

echo "==> Tearing down any previous smoke app"
rm -rf "${SMOKE_DIR}"

echo "==> Scaffolding a fresh Laravel 13 skeleton"
COMPOSER_PROCESS_TIMEOUT=1800 composer create-project laravel/laravel "${SMOKE_DIR}" \
    --prefer-dist --no-interaction --quiet

cd "${SMOKE_DIR}"

echo "==> Replacing composer.json with Haykal-wired version"
cat > composer.json <<'JSON'
{
    "$schema": "https://getcomposer.org/schema.json",
    "name": "hitaqnia/haykal-smoke",
    "type": "project",
    "description": "Throwaway Laravel 13 app used to validate the Haykal suite end-to-end.",
    "license": "proprietary",
    "require": {
        "php": "^8.3",
        "hitaqnia/haykal": "@dev",
        "laravel/framework": "^13.0",
        "laravel/tinker": "^3.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pail": "^1.2.5",
        "laravel/pint": "^1.27",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.6",
        "phpunit/phpunit": "^12.5.12"
    },
    "repositories": [
        { "type": "path", "url": "../../huwiya/packages/huwiya-laravel", "options": { "symlink": true } },
        { "type": "path", "url": "../packages/haykal-core", "options": { "symlink": true } },
        { "type": "path", "url": "../packages/haykal-api", "options": { "symlink": true } },
        { "type": "path", "url": "../packages/haykal-filament", "options": { "symlink": true } },
        { "type": "path", "url": "../packages/haykal", "options": { "symlink": true } }
    ],
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": { "Tests\\": "tests/" }
    },
    "extra": { "laravel": { "dont-discover": [] } },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON

echo "==> Installing Haykal + transitive deps"
composer update --no-interaction --quiet

echo "==> Refreshing package manifest (Spatie / Filament / Huwiya / Haykal providers)"
php artisan package:discover --ansi >/dev/null 2>&1

echo "==> Removing conflicting default users migration + scaffolded User model"
rm -f database/migrations/0001_01_01_000000_create_users_table.php
rm -f app/Models/User.php

echo "==> Publishing configs and routes"
php artisan vendor:publish --tag=permission-config --force --quiet
php artisan vendor:publish --tag=medialibrary-config --force --quiet
php artisan vendor:publish --tag=huwiya-config --force --quiet
php artisan vendor:publish --tag=haykal-api-routes --force --quiet

echo "==> Configuring Spatie permission — haykal Role/Permission + teams"
sed -i.bak \
    -e 's|use Spatie\\Permission\\Models\\Permission;|use HiTaqnia\\Haykal\\Core\\Identity\\Models\\Permission;|' \
    -e 's|use Spatie\\Permission\\Models\\Role;|use HiTaqnia\\Haykal\\Core\\Identity\\Models\\Role;|' \
    -e "s|'teams' => false,|'teams' => true,|" \
    config/permission.php
rm -f config/permission.php.bak

echo "==> Configuring Media Library — Haykal CustomPathGenerator"
sed -i.bak \
    -e 's|use Spatie\\MediaLibrary\\Support\\PathGenerator\\DefaultPathGenerator;|use HiTaqnia\\Haykal\\Core\\MediaLibrary\\CustomPathGenerator;|' \
    -e 's|DefaultPathGenerator::class|CustomPathGenerator::class|g' \
    config/media-library.php
rm -f config/media-library.php.bak

echo "==> Pointing auth at haykal User + registering Huwiya guards"
cat > config/auth.php <<'PHP'
<?php

use HiTaqnia\Haykal\Core\Identity\Models\User;

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'huwiya-web',
            'provider' => 'users',
        ],
        'huwiya-api' => [
            'driver' => 'huwiya-api',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
PHP

echo "==> Enabling api routing + slotting Haykal middlewares"
cat > bootstrap/app.php <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            'haykal.user.locale',
            'haykal.permissions.team',
        ]);

        $middleware->appendToGroup('api', [
            'haykal.user.locale',
            'haykal.permissions.team',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
PHP

cat > routes/api.php <<'PHP'
<?php

declare(strict_types=1);

require __DIR__.'/api/identity-api.php';
PHP

cat > routes/web.php <<'PHP'
<?php

declare(strict_types=1);

// Intentionally empty — the smoke Filament panel owns `/`.
PHP

echo "==> Creating smoke Filament panel provider"
mkdir -p app/Providers/Panels
cat > app/Providers/Panels/SmokePanelProvider.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\Providers\Panels;

use Filament\Panel;
use HiTaqnia\Haykal\Filament\BasePanel;

final class SmokePanelProvider extends BasePanel
{
    protected function getId(): string
    {
        return 'smoke';
    }

    protected function customizePanel(Panel $panel): Panel
    {
        return $panel->brandName('Haykal Smoke');
    }
}
PHP

cat > bootstrap/providers.php <<'PHP'
<?php

use App\Providers\AppServiceProvider;
use App\Providers\Panels\SmokePanelProvider;
use HiTaqnia\Haykal\Api\Identity\IdentityApiProvider;

return [
    AppServiceProvider::class,
    IdentityApiProvider::class,
    SmokePanelProvider::class,
];
PHP

echo "==> Appending Huwiya smoke-env values"
cat >> .env <<'ENV'

# ============================================================
# Huwiya Identity Provider (smoke-test values, not for production)
# ============================================================
HUWIYA_URL=https://huwiya.test
HUWIYA_PROJECT_ID=haykal-smoke
HUWIYA_CLIENT_ID=smoke-client
HUWIYA_CLIENT_SECRET=smoke-secret
HUWIYA_REDIRECT_URI=http://localhost:8765/huwiya/callback
HUWIYA_VERIFY_SIGNATURE=false
ENV

echo "==> Running migrations"
rm -f database/database.sqlite
touch database/database.sqlite
php artisan migrate --force --quiet

echo ""
echo "==> Smoke app ready."
echo "    Serve: cd smoke && php artisan serve --port=8765"
echo "    Then try:"
echo "        curl -i http://127.0.0.1:8765/api/identity/me       # 401 envelope"
echo "        curl -i http://127.0.0.1:8765/docs/identity-api.json # Scramble spec"
echo "        curl -i http://127.0.0.1:8765/                       # redirect to /login → Huwiya"
