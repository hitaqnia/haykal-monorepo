<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament;

final class BaseThemeTest extends FilamentTestCase
{
    private string $themePath = __DIR__.'/../../packages/haykal-filament/resources/css/base-theme.css';

    public function test_base_theme_ships_with_the_package(): void
    {
        $this->assertFileExists($this->themePath);
    }

    public function test_base_theme_declares_the_documented_token_block(): void
    {
        // The README's variable reference table is anchored on these
        // tokens — if they get renamed or dropped, downstream apps that
        // import the theme break silently. The asserts below pin the
        // contract until the next intentional rename.
        $contents = (string) file_get_contents($this->themePath);

        $expectedTokens = [
            // Shell
            '--filament-shell',
            '--filament-shell-soft',
            '--filament-shell-elevated',
            '--filament-shell-line',
            '--filament-shell-line-strong',
            '--filament-shell-text',
            '--filament-shell-muted',
            '--filament-shell-dim',
            // Canvas
            '--filament-canvas',
            '--filament-surface',
            '--filament-surface-soft',
            '--filament-line',
            '--filament-line-strong',
            '--filament-ink',
            '--filament-ink-soft',
            '--filament-ink-muted',
            // Radius
            '--filament-radius-sm',
            '--filament-radius',
            '--filament-radius-lg',
            // Elevation
            '--filament-shadow-none',
            '--filament-shadow-overlay',
            // Motion
            '--filament-ease',
        ];

        foreach ($expectedTokens as $token) {
            $this->assertStringContainsString(
                $token,
                $contents,
                "Base theme must declare {$token} (referenced in the README variable table).",
            );
        }
    }
}
