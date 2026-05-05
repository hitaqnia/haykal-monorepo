<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament;

use Filament\FontProviders\BunnyFontProvider;
use Filament\Panel;
use HiTaqnia\Haykal\Filament\BasePanel;

final class BasePanelFontsTest extends FilamentTestCase
{
    public function test_default_font_for_english_is_outfit(): void
    {
        $this->app->setLocale('en');

        $panel = $this->buildPanel(new TestFontPanelProvider($this->app));

        $this->assertSame('Outfit', $panel->getFontFamily());
    }

    public function test_default_font_for_arabic_is_tajawal(): void
    {
        $this->app->setLocale('ar');

        $panel = $this->buildPanel(new TestFontPanelProvider($this->app));

        $this->assertSame('Tajawal', $panel->getFontFamily());
    }

    public function test_default_font_for_kurdish_is_noto_sans_arabic(): void
    {
        $this->app->setLocale('ku');

        $panel = $this->buildPanel(new TestFontPanelProvider($this->app));

        $this->assertSame('Noto Sans Arabic', $panel->getFontFamily());
    }

    public function test_unknown_locale_falls_back_to_the_default_entry(): void
    {
        $this->app->setLocale('fr');

        $panel = $this->buildPanel(new TestFontPanelProvider($this->app));

        $this->assertSame('Outfit', $panel->getFontFamily());
    }

    public function test_panel_uses_bunny_font_provider_by_default(): void
    {
        $this->app->setLocale('en');

        $panel = $this->buildPanel(new TestFontPanelProvider($this->app));

        $this->assertSame(BunnyFontProvider::class, $panel->getFontProvider());
    }

    public function test_subclass_can_override_the_locale_to_font_map(): void
    {
        $this->app->setLocale('ar');

        $panel = $this->buildPanel(new OverriddenFontPanelProvider($this->app));

        $this->assertSame('Cairo', $panel->getFontFamily());
    }

    public function test_subclass_override_still_falls_back_through_the_default_key(): void
    {
        $this->app->setLocale('ku');

        $panel = $this->buildPanel(new OverriddenFontPanelProvider($this->app));

        // OverriddenFontPanelProvider only declares `default` and `ar`,
        // so Kurdish must fall through to the default entry.
        $this->assertSame('Inter', $panel->getFontFamily());
    }

    private function buildPanel(BasePanel $provider): Panel
    {
        return $provider->panel(Panel::make());
    }
}

/**
 * Minimal concrete panel for the test — every abstract hook returns a
 * trivial value so the panel boots without touching a real application.
 */
final class TestFontPanelProvider extends BasePanel
{
    protected function getId(): string
    {
        return 'test-fonts';
    }

    protected function customizePanel(Panel $panel): Panel
    {
        return $panel;
    }
}

/**
 * Demonstrates the documented extension point: subclass and override
 * `fontFamiliesByLocale()` to swap the per-locale font map.
 */
final class OverriddenFontPanelProvider extends BasePanel
{
    protected function getId(): string
    {
        return 'test-fonts-overridden';
    }

    protected function customizePanel(Panel $panel): Panel
    {
        return $panel;
    }

    protected function fontFamiliesByLocale(): array
    {
        return [
            'default' => 'Inter',
            'ar' => 'Cairo',
        ];
    }
}
