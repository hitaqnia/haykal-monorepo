<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\Mapbox;

use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationPicker;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsDrawer;
use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;

final class InteractsWithMapboxTest extends FilamentTestCase
{
    public function test_fluent_setters_round_trip_values_back_through_getters(): void
    {
        $picker = MapboxLocationPicker::make('location')
            ->mapContainer('custom-container')
            ->mapHeight(650)
            ->mapStyle('mapbox://styles/mapbox/satellite-v9')
            ->mapCenter([44.3661, 33.3152])
            ->mapZoom(14)
            ->navigationControl();

        $this->assertSame('custom-container', $picker->getMapContainer());
        $this->assertSame(650, $picker->getMapHeight());
        $this->assertSame('mapbox://styles/mapbox/satellite-v9', $picker->getMapStyle());
        $this->assertSame([44.3661, 33.3152], $picker->getMapCenter());
        $this->assertSame(14, $picker->getMapZoom());
        $this->assertTrue($picker->hasNavigationControl());
    }

    public function test_defaults_apply_when_fluent_configuration_is_omitted(): void
    {
        $picker = MapboxLocationPicker::make('location');

        $this->assertSame('location', $picker->getMapContainer(), 'Container defaults to the field name.');
        $this->assertSame(400, $picker->getMapHeight());
        $this->assertNull($picker->getMapStyle());
        $this->assertSame([-74.5, 40], $picker->getMapCenter());
        $this->assertSame(9, $picker->getMapZoom());
        $this->assertFalse($picker->hasNavigationControl());
    }

    public function test_get_mapbox_config_assembles_every_knob_into_a_single_structure(): void
    {
        config()->set('mapbox.token', 'pk.smoke');

        $picker = MapboxLocationPicker::make('location')
            ->mapContainer('my-map')
            ->mapStyle('mapbox://styles/mapbox/streets-v12')
            ->mapCenter([1.0, 2.0])
            ->mapZoom(10)
            ->navigationControl();

        $this->assertSame([
            'token' => 'pk.smoke',
            'map' => [
                'container' => 'my-map',
                'style' => 'mapbox://styles/mapbox/streets-v12',
                'center' => [1.0, 2.0],
                'zoom' => 10,
                'controls' => ['navigation' => true],
            ],
        ], $picker->getMapboxConfig());
    }

    public function test_get_mapbox_json_config_serializes_to_valid_json(): void
    {
        config()->set('mapbox.token', 'pk.smoke');

        $picker = MapboxLocationPicker::make('location');

        $json = $picker->getMapboxJsonConfig();

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('pk.smoke', $decoded['token']);
        $this->assertSame('location', $decoded['map']['container']);
    }

    public function test_polygons_drawer_appends_max_polygons_to_the_base_config(): void
    {
        config()->set('mapbox.token', 'pk.smoke');

        $drawer = MapboxPolygonsDrawer::make('fences')
            ->maxPolygons(3);

        $config = $drawer->getMapboxConfig();

        $this->assertSame(3, $config['maxPolygons']);
        $this->assertSame(3, $drawer->getMaxPolygons());
        $this->assertSame('pk.smoke', $config['token']);
    }

    public function test_polygons_drawer_max_defaults_to_unlimited_sentinel(): void
    {
        $drawer = MapboxPolygonsDrawer::make('fences');

        $this->assertSame(-1, $drawer->getMaxPolygons());
        $this->assertSame(-1, $drawer->getMapboxConfig()['maxPolygons']);
    }
}
