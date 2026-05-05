<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament;

use BladeUI\Icons\Factory as BladeIconsFactory;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use ToneGabes\Filament\Icons\PhosphorIconsServiceProvider;

/**
 * Pin the contract that the Phosphor icon set is shipped *with* the
 * package — apps that install `hitaqnia/haykal-filament` should not
 * have to add `codeat3/blade-phosphor-icons` (or anything else) to
 * make the icon aliases declared in `haykal-filament-icons.php` resolve.
 */
final class PhosphorIconsTest extends FilamentTestCase
{
    public function test_phosphor_enum_class_is_autoloaded_via_haykal_filament(): void
    {
        $this->assertTrue(class_exists(Phosphor::class));
    }

    public function test_phosphor_iconset_service_provider_is_registered(): void
    {
        $this->assertNotNull($this->app->getProvider(PhosphorIconsServiceProvider::class));
    }

    public function test_blade_icons_factory_resolves_a_phosphor_icon(): void
    {
        /** @var BladeIconsFactory $factory */
        $factory = $this->app->make(BladeIconsFactory::class);

        $svg = $factory->svg('phosphor-trash-duotone');

        $this->assertNotEmpty($svg->contents(), 'Phosphor icons must resolve out of the box.');
    }

    public function test_haykal_filament_icons_config_uses_phosphor_aliases(): void
    {
        $icons = config('filament.icons');

        $this->assertSame('phosphor-trash-duotone', $icons['actions::delete-action']);
        $this->assertSame('phosphor-eye-duotone', $icons['actions::view-action']);
    }
}
