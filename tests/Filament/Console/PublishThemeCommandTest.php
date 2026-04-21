<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\Console;

use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;
use Illuminate\Support\Facades\File;

final class PublishThemeCommandTest extends FilamentTestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(resource_path('css'));
        parent::tearDown();
    }

    public function test_scaffolds_base_theme_and_panel_theme_files(): void
    {
        $this->artisan('haykal:publish-theme', ['panel' => 'management'])
            ->assertSuccessful();

        $baseTheme = resource_path('css/haykal/base-theme.css');
        $panelTheme = resource_path('css/filament/management/theme.css');

        $this->assertTrue(File::exists($baseTheme), 'Base theme should be copied.');
        $this->assertTrue(File::exists($panelTheme), 'Panel theme should be scaffolded.');

        $panelContents = File::get($panelTheme);

        $this->assertStringContainsString('Management panel overrides', $panelContents);
        $this->assertStringContainsString('@source \'../../../../app/Panels/Management/**/*.php\'', $panelContents);
        $this->assertStringContainsString('@source \'../../../../resources/views/filament/management/**/*.blade.php\'', $panelContents);
    }

    public function test_existing_panel_theme_is_preserved_without_force(): void
    {
        $panelTheme = resource_path('css/filament/management/theme.css');
        File::ensureDirectoryExists(dirname($panelTheme));
        File::put($panelTheme, '/* existing contents */');

        $this->artisan('haykal:publish-theme', ['panel' => 'management'])
            ->assertSuccessful();

        $this->assertSame('/* existing contents */', File::get($panelTheme));
    }

    public function test_force_flag_overwrites_existing_panel_theme(): void
    {
        $panelTheme = resource_path('css/filament/management/theme.css');
        File::ensureDirectoryExists(dirname($panelTheme));
        File::put($panelTheme, '/* existing contents */');

        $this->artisan('haykal:publish-theme', ['panel' => 'management', '--force' => true])
            ->assertSuccessful();

        $this->assertStringContainsString('Management panel overrides', File::get($panelTheme));
    }

    public function test_panel_identifier_is_normalized_to_kebab_case(): void
    {
        $this->artisan('haykal:publish-theme', ['panel' => 'PropertyManagement'])
            ->assertSuccessful();

        $panelTheme = resource_path('css/filament/property-management/theme.css');

        $this->assertTrue(File::exists($panelTheme));
        $this->assertStringContainsString('PropertyManagement panel overrides', File::get($panelTheme));
    }
}
