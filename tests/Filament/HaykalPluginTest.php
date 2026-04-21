<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament;

use HiTaqnia\Haykal\Filament\HaykalPlugin;

final class HaykalPluginTest extends FilamentTestCase
{
    public function test_plugin_exposes_the_haykal_identifier(): void
    {
        $plugin = HaykalPlugin::make();

        $this->assertSame('haykal', $plugin->getId());
    }

    public function test_toggles_are_fluent_and_opt_in_per_feature(): void
    {
        $plugin = HaykalPlugin::make()
            ->withTranslatableTabs()
            ->withAccessChecking();

        $this->assertSame('haykal', $plugin->getId());
        $this->assertInstanceOf(HaykalPlugin::class, $plugin->withTranslatableTabs(false));
        $this->assertInstanceOf(HaykalPlugin::class, $plugin->withAccessChecking(false));
    }
}
