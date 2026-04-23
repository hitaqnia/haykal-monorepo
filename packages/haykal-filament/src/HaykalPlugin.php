<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use HiTaqnia\Haykal\Filament\Http\Middlewares\AccessCheckingMiddleware;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

/**
 * Per-panel plugin that installs optional Haykal Filament features.
 *
 * `BasePanel` ships sensible defaults for most applications; apps that
 * want finer-grained control can register `HaykalPlugin` on specific
 * panels and toggle individual features via the fluent API.
 *
 * Current toggles:
 *
 *   - `withTranslatableTabs()` — registers the Spatie translatable plugin
 *     preconfigured with the application's supported locales.
 *   - `withAccessChecking()` — appends the `<panel-id>.access` Gate
 *     middleware to the panel's authentication stack.
 *
 * Defaults disable every feature; enable only what the panel needs.
 *
 *     $panel->plugin(
 *         HaykalPlugin::make()
 *             ->withTranslatableTabs()
 *             ->withAccessChecking(),
 *     );
 */
final class HaykalPlugin implements Plugin
{
    private bool $translatableTabs = false;

    private bool $accessChecking = false;

    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'haykal';
    }

    public function register(Panel $panel): void
    {
        if ($this->translatableTabs) {
            $panel->plugin(
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(config('app.supported_locales', [config('app.locale')]))
                    ->persist(),
            );
        }

        if ($this->accessChecking) {
            $panel->authMiddleware([AccessCheckingMiddleware::class], isPersistent: true);
        }
    }

    public function boot(Panel $panel): void {}

    public function withTranslatableTabs(bool $enabled = true): self
    {
        $this->translatableTabs = $enabled;

        return $this;
    }

    public function withAccessChecking(bool $enabled = true): self
    {
        $this->accessChecking = $enabled;

        return $this;
    }
}
