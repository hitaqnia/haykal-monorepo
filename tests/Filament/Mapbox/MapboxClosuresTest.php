<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\Mapbox;

use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationPicker;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationViewer;
use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;
use HiTaqnia\Haykal\Tests\Fixtures\TestSchemaLivewire;

/**
 * Closure support for the four Mapbox setters that take dynamic values
 * (`mapHeight`, `mapCenter`, `mapZoom`, `navigationControl`).
 *
 * The trait routes each one through `$this->evaluate(...)`, so the
 * closure participates in Filament's standard injection — the same
 * way `default()`, `hidden()`, `visible()` and friends do across the
 * rest of the framework.
 */
final class MapboxClosuresTest extends FilamentTestCase
{
    public function test_static_values_round_trip_unchanged_through_evaluate(): void
    {
        $picker = MapboxLocationPicker::make('location')
            ->mapHeight(700)
            ->mapCenter([10.0, 20.0])
            ->mapZoom(11)
            ->navigationControl(true);

        $this->assertSame(700, $picker->getMapHeight());
        $this->assertSame([10.0, 20.0], $picker->getMapCenter());
        $this->assertSame(11, $picker->getMapZoom());
        $this->assertTrue($picker->hasNavigationControl());
    }

    public function test_plain_closures_are_evaluated_on_every_getter_call(): void
    {
        $picker = MapboxLocationPicker::make('location')
            ->mapHeight(fn (): int => 555)
            ->mapCenter(fn (): array => [44.3661, 33.3152])
            ->mapZoom(fn (): int => 13)
            ->navigationControl(fn (): bool => true);

        $this->assertSame(555, $picker->getMapHeight());
        $this->assertSame([44.3661, 33.3152], $picker->getMapCenter());
        $this->assertSame(13, $picker->getMapZoom());
        $this->assertTrue($picker->hasNavigationControl());
    }

    public function test_closures_re_evaluate_when_external_state_changes(): void
    {
        $center = [1.0, 2.0];
        $zoom = 5;
        $height = 300;
        $nav = false;

        $picker = MapboxLocationPicker::make('location')
            ->mapHeight(function () use (&$height): int {
                return $height;
            })
            ->mapCenter(function () use (&$center): array {
                return $center;
            })
            ->mapZoom(function () use (&$zoom): int {
                return $zoom;
            })
            ->navigationControl(function () use (&$nav): bool {
                return $nav;
            });

        $this->assertSame(300, $picker->getMapHeight());
        $this->assertSame([1.0, 2.0], $picker->getMapCenter());
        $this->assertSame(5, $picker->getMapZoom());
        $this->assertFalse($picker->hasNavigationControl());

        // Mutating the captured-by-reference variables proves the getters
        // do not memoize — every render reflects the freshest state.
        $center = [99.0, 88.0];
        $zoom = 18;
        $height = 900;
        $nav = true;

        $this->assertSame(900, $picker->getMapHeight());
        $this->assertSame([99.0, 88.0], $picker->getMapCenter());
        $this->assertSame(18, $picker->getMapZoom());
        $this->assertTrue($picker->hasNavigationControl());
    }

    public function test_map_center_closure_can_read_a_sibling_field_via_get_injection(): void
    {
        // A schema with a sibling `origin` field that holds the live
        // coordinate state, plus a Mapbox picker whose `mapCenter`
        // closure pulls the value through `Get` — exactly the pattern
        // an app would use with a `live()` source field.
        $picker = MapboxLocationPicker::make('coordinates')
            ->mapCenter(fn (Get $get): array => $get('origin') ?? [0.0, 0.0]);

        $this->mountInSchema(
            $picker,
            ['origin' => [44.3661, 33.3152]],
            extraComponents: [Hidden::make('origin')],
        );

        $this->assertSame([44.3661, 33.3152], $picker->getMapCenter());
    }

    public function test_map_center_closure_reflects_sibling_state_after_it_changes(): void
    {
        $picker = MapboxLocationPicker::make('coordinates')
            ->mapCenter(fn (Get $get): array => $get('origin') ?? [0.0, 0.0]);

        $livewire = $this->mountInSchema(
            $picker,
            ['origin' => [10.0, 20.0]],
            extraComponents: [Hidden::make('origin')],
        );

        $this->assertSame([10.0, 20.0], $picker->getMapCenter());

        // Simulate the source field updating (e.g., a `live()` `Select`
        // that the user just changed). The picker must pick up the
        // new value on its next render.
        $livewire->data['origin'] = [55.0, 66.0];

        $this->assertSame([55.0, 66.0], $picker->getMapCenter());
    }

    public function test_map_center_closure_can_read_the_livewire_host_via_livewire_injection(): void
    {
        $picker = MapboxLocationPicker::make('coordinates')
            ->mapCenter(fn ($livewire): array => $livewire->data['origin']);

        $this->mountInSchema($picker, ['origin' => [12.34, 56.78]]);

        $this->assertSame([12.34, 56.78], $picker->getMapCenter());
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<\Filament\Schemas\Components\Component>  $extraComponents
     */
    private function mountInSchema(
        MapboxLocationPicker $picker,
        array $data = [],
        array $extraComponents = [],
    ): TestSchemaLivewire {
        $livewire = $this->makeLivewireHost($data);

        $schema = Schema::make($livewire)
            ->statePath('data')
            ->components([...$extraComponents, $picker]);

        // Force the schema to walk its children, which assigns the
        // container on each component (`$component->container($this)`)
        // — without that, `$get`, `$livewire`, and friends can't
        // resolve in any closure.
        $schema->getComponents();

        return $livewire;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makeLivewireHost(array $data = []): TestSchemaLivewire
    {
        $livewire = new TestSchemaLivewire;
        $livewire->data = $data;

        return $livewire;
    }

    public function test_get_mapbox_config_propagates_evaluated_closure_values(): void
    {
        config()->set('mapbox.token', 'pk.smoke');

        $picker = MapboxLocationPicker::make('location')
            ->mapStyle('mapbox://styles/mapbox/streets-v12')
            ->mapCenter(fn (): array => [44.3661, 33.3152])
            ->mapZoom(fn (): int => 14)
            ->navigationControl(fn (): bool => true);

        $this->assertSame([
            'token' => 'pk.smoke',
            'map' => [
                'container' => 'location',
                'style' => 'mapbox://styles/mapbox/streets-v12',
                'center' => [44.3661, 33.3152],
                'zoom' => 14,
                'controls' => ['navigation' => true],
            ],
        ], $picker->getMapboxConfig());
    }

    public function test_closures_work_on_infolist_entries_too(): void
    {
        $viewer = MapboxLocationViewer::make('location')
            ->mapHeight(fn (): int => 250)
            ->mapZoom(fn (): int => 8)
            ->navigationControl(fn (): bool => true);

        $this->assertSame(250, $viewer->getMapHeight());
        $this->assertSame(8, $viewer->getMapZoom());
        $this->assertTrue($viewer->hasNavigationControl());
    }
}
