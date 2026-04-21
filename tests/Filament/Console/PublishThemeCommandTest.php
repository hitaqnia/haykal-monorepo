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

    public function test_scaffolds_a_panel_theme_file_importing_the_base_theme_from_vendor(): void
    {
        $this->artisan('haykal:publish-theme', ['panel' => 'management'])
            ->assertSuccessful();

        $panelTheme = resource_path('css/filament/management/theme.css');

        $this->assertTrue(File::exists($panelTheme), 'Panel theme should be scaffolded.');

        $contents = File::get($panelTheme);

        $this->assertStringContainsString(
            "@import '../../../../vendor/hitaqnia/haykal-filament/resources/css/base-theme.css'",
            $contents,
            'Base theme should be imported directly from the vendor path so updates propagate via composer update.',
        );
        $this->assertStringContainsString(
            "@import '../../../../vendor/filament/filament/resources/css/theme.css'",
            $contents,
        );
        $this->assertStringContainsString('Management panel overrides', $contents);
        $this->assertStringContainsString("@source '../../../../app/Panels/Management/**/*.php'", $contents);
        $this->assertStringContainsString(
            "@source '../../../../resources/views/filament/management/**/*.blade.php'",
            $contents,
        );
    }

    public function test_base_theme_is_not_copied_into_the_application(): void
    {
        $this->artisan('haykal:publish-theme', ['panel' => 'management'])
            ->assertSuccessful();

        $copiedBase = resource_path('css/haykal/base-theme.css');

        $this->assertFalse(
            File::exists($copiedBase),
            'The base theme should remain in vendor so updates propagate via composer update.',
        );
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
